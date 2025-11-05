import React, { useEffect, useState, useCallback } from 'react';
import { generateSelectors, getElementPreview } from '../../utils/selectorGenerator';
import type { SelectorResult } from '../../utils/selectorGenerator';

interface ElementInspectorProps {
  isActive: boolean;
  onElementSelected: (element: Element, selectors: SelectorResult) => void;
}

export default function ElementInspector({ isActive, onElementSelected }: ElementInspectorProps) {
  const [hoveredElement, setHoveredElement] = useState<Element | null>(null);
  const [highlightBox, setHighlightBox] = useState<DOMRect | null>(null);
  const [hoveredSelectors, setHoveredSelectors] = useState<SelectorResult | null>(null);
  const [modifierKeyHeld, setModifierKeyHeld] = useState(false);

  const handleMouseMove = useCallback((e: MouseEvent) => {
    if (!isActive) return;

    // Track modifier key state
    const isModifierHeld = e.ctrlKey || e.altKey;
    setModifierKeyHeld(isModifierHeld);

    // Get the element under the cursor (excluding our own overlay)
    const target = e.target as Element;

    // Ignore our own DevMode elements
    if (target.closest('#evenleads-devmode-panel') ||
        target.closest('#evenleads-element-highlight') ||
        target.closest('#evenleads-xpath-badge')) {
      return;
    }

    setHoveredElement(target);
    setHighlightBox(target.getBoundingClientRect());

    // Generate selectors for tooltip display
    const selectors = generateSelectors(target);
    setHoveredSelectors(selectors);
  }, [isActive]);

  const handleClick = useCallback((e: MouseEvent) => {
    if (!isActive || !hoveredElement) return;

    // CRITICAL: Only intercept if Ctrl or Alt key is held
    // This allows normal page clicks to work without ANY interference
    if (!e.ctrlKey && !e.altKey) {
      console.log('[ElementInspector] Click without modifier key - letting event pass through');
      return; // Let the event propagate normally
    }

    const target = e.target as Element;

    // Only select if clicking on the hovered element or its children
    if (hoveredElement.contains(target) || hoveredElement === target) {
      console.log('[ElementInspector] Modifier+Click detected - selecting element');

      // NOW we can prevent default since user explicitly wants to select
      e.preventDefault();
      e.stopPropagation();

      // Generate selectors for the clicked element
      const selectors = generateSelectors(hoveredElement);
      onElementSelected(hoveredElement, selectors);

      console.log('[ElementInspector] Element selected:', {
        tag: hoveredElement.tagName,
        selectors: selectors
      });
    }
  }, [isActive, hoveredElement, onElementSelected]);

  const handleKeyDown = useCallback((e: KeyboardEvent) => {
    if (!isActive) return;

    // Update modifier key state
    if (e.ctrlKey || e.altKey) {
      setModifierKeyHeld(true);
    }

    // ESC to cancel
    if (e.key === 'Escape') {
      setHoveredElement(null);
      setHighlightBox(null);
      setHoveredSelectors(null);
    }

    // ⌘↑ (Mac) or Ctrl↑ (Windows/Linux) to select parent element
    if ((e.metaKey || e.ctrlKey) && e.key === 'ArrowUp') {
      e.preventDefault();
      e.stopPropagation();

      if (hoveredElement?.parentElement) {
        const parent = hoveredElement.parentElement;

        // Ignore our own DevMode elements
        if (parent.closest('#evenleads-devmode-panel') ||
            parent.closest('#evenleads-element-highlight') ||
            parent.closest('#evenleads-xpath-badge')) {
          return;
        }

        // Update to parent element
        setHoveredElement(parent);
        setHighlightBox(parent.getBoundingClientRect());

        // Generate selectors for parent
        const selectors = generateSelectors(parent);
        setHoveredSelectors(selectors);
      }
    }
  }, [isActive, hoveredElement]);

  const handleKeyUp = useCallback((e: KeyboardEvent) => {
    // Update modifier key state
    if (!e.ctrlKey && !e.altKey) {
      setModifierKeyHeld(false);
    }
  }, []);

  useEffect(() => {
    if (!isActive) {
      setHoveredElement(null);
      setHighlightBox(null);
      setHoveredSelectors(null);
      setModifierKeyHeld(false);
      return;
    }

    // CRITICAL CHANGE: Use BUBBLING phase, not capture
    // This lets page events (LinkedIn React) handle clicks first
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('click', handleClick, { passive: false }); // NO CAPTURE!
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('keyup', handleKeyUp);

    // Change cursor to crosshair
    document.body.style.cursor = 'crosshair';

    return () => {
      document.removeEventListener('mousemove', handleMouseMove);
      document.removeEventListener('click', handleClick);
      document.removeEventListener('keydown', handleKeyDown);
      document.removeEventListener('keyup', handleKeyUp);
      document.body.style.cursor = '';
    };
  }, [isActive, handleMouseMove, handleClick, handleKeyDown, handleKeyUp]);

  if (!isActive || !highlightBox) return null;

  const preview = hoveredElement ? getElementPreview(hoveredElement) : null;

  // Change highlight color when modifier key is held
  const highlightColor = modifierKeyHeld ? '#10b981' : '#3b82f6'; // Green when ready to select
  const badgeColor = modifierKeyHeld ? '#059669' : '#8b5cf6';

  return (
    <>
      {/* Highlight overlay */}
      <div
        id="evenleads-element-highlight"
        style={{
          position: 'fixed',
          top: highlightBox.top + 'px',
          left: highlightBox.left + 'px',
          width: highlightBox.width + 'px',
          height: highlightBox.height + 'px',
          border: `2px solid ${highlightColor}`,
          backgroundColor: modifierKeyHeld ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.1)',
          pointerEvents: 'none',
          zIndex: 999998,
          boxShadow: '0 0 0 9999px rgba(0, 0, 0, 0.3)',
          transition: 'border-color 0.15s, background-color 0.15s',
        }}
      />

      {/* XPath badge above element */}
      {hoveredSelectors && (
        <div
          id="evenleads-xpath-badge"
          style={{
            position: 'fixed',
            top: Math.max(10, highlightBox.top - 35) + 'px',
            left: highlightBox.left + 'px',
            backgroundColor: badgeColor,
            color: '#ffffff',
            padding: '6px 12px',
            borderRadius: '4px',
            fontSize: '11px',
            fontFamily: 'monospace',
            fontWeight: '600',
            zIndex: 999999,
            pointerEvents: 'none',
            maxWidth: Math.min(600, window.innerWidth - 40) + 'px',
            overflow: 'hidden',
            textOverflow: 'ellipsis',
            whiteSpace: 'nowrap',
            boxShadow: '0 2px 8px rgba(139, 92, 246, 0.4)',
            border: '1px solid rgba(255, 255, 255, 0.2)',
            transition: 'background-color 0.15s',
          }}
        >
          XPath: {hoveredSelectors.xpathSelector}
        </div>
      )}

      {/* Info tooltip */}
      {preview && (
        <div
          style={{
            position: 'fixed',
            top: (highlightBox.top - 80) + 'px',
            left: highlightBox.left + 'px',
            backgroundColor: modifierKeyHeld ? '#065f46' : '#1f2937',
            color: '#f3f4f6',
            padding: '8px 12px',
            borderRadius: '6px',
            fontSize: '12px',
            fontFamily: 'monospace',
            zIndex: 999999,
            pointerEvents: 'none',
            maxWidth: '400px',
            boxShadow: '0 4px 6px rgba(0, 0, 0, 0.3)',
            transition: 'background-color 0.15s',
          }}
        >
          <div style={{ fontWeight: 'bold', marginBottom: '4px' }}>
            &lt;{preview.tag}&gt;
          </div>
          {preview.text && (
            <div style={{
              fontSize: '11px',
              color: '#d1d5db',
              overflow: 'hidden',
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap',
            }}>
              "{preview.text}"
            </div>
          )}
          <div style={{ fontSize: '10px', color: modifierKeyHeld ? '#d1fae5' : '#9ca3af', marginTop: '4px', fontWeight: modifierKeyHeld ? 'bold' : 'normal' }}>
            {modifierKeyHeld ? '✅ Click to select!' : 'Hold Ctrl/Alt + Click to select'} • ESC to cancel • {navigator.platform.includes('Mac') ? '⌘↑' : 'Ctrl↑'} for parent
          </div>
        </div>
      )}
    </>
  );
}
