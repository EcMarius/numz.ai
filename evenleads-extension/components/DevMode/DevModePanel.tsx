import React, { useState, useEffect, useRef } from 'react';
import { X, Download, Trash2, Eye, EyeOff, Code, Upload, Edit2, Copy, Check, FileJson, Save, MessageSquare, Play, Send, BarChart3 } from 'lucide-react';
import ElementInspector from './ElementInspector';
import SchemaBuilder from './SchemaBuilder';
import DebugDashboard from './DebugDashboard';
import type { SchemaElement, Platform, PageType, PlatformSchema } from '../../types';
import type { SelectorResult } from '../../utils/selectorGenerator';
import { devModeStorage } from '../../utils/storage';
import { detectCurrentPlatform, detectPageType } from '../../utils/pageDetection';
import { createPlatformEngine } from '../../utils/engines';

interface DevModePanelProps {
  isOpen: boolean;
  onClose: () => void;
  isLinkedIn?: boolean;
}

export default function DevModePanel({ isOpen, onClose, isLinkedIn }: DevModePanelProps) {
  const [inspecting, setInspecting] = useState(false);
  const [selectedElement, setSelectedElement] = useState<Element | null>(null);
  const [selectors, setSelectors] = useState<SelectorResult | null>(null);
  const [schemaElements, setSchemaElements] = useState<SchemaElement[]>([]);
  const [platform, setPlatform] = useState<Platform | null>(null);
  const [pageType, setPageType] = useState<PageType>('general'); // Default to general
  const [showElementList, setShowElementList] = useState(true);
  const [editingIndex, setEditingIndex] = useState<number | null>(null);
  const [showImportModal, setShowImportModal] = useState(false);
  const [importJson, setImportJson] = useState('');
  const [copied, setCopied] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Simple test state
  const [testSectionExpanded, setTestSectionExpanded] = useState(false);
  const [testProfileUrl, setTestProfileUrl] = useState<string>('');
  const [testMessage, setTestMessage] = useState<string>('Hello! I saw your post about looking for web development services. I have 8+ years of experience building scalable SaaS platforms with React, Node.js, and TypeScript. Would love to chat about how I can help with your project!');

  // Debug Dashboard state
  const [debugDashboardExpanded, setDebugDashboardExpanded] = useState(false);

  // Load test section expanded state from storage
  useEffect(() => {
    const loadExpandedState = async () => {
      const expanded = await devModeStorage.getTestSectionExpanded();
      setTestSectionExpanded(expanded);
    };

    if (isOpen) {
      loadExpandedState();
    }
  }, [isOpen]);

  // Save test section expanded state when changed
  useEffect(() => {
    if (isOpen) {
      devModeStorage.setTestSectionExpanded(testSectionExpanded);
    }
  }, [testSectionExpanded, isOpen]);

  // All old test state management removed

  // Draggable panel state - will be loaded from storage
  const [position, setPosition] = useState({ x: 20, y: 100 });
  const [isDragging, setIsDragging] = useState(false);
  const [dragOffset, setDragOffset] = useState({ x: 0, y: 0 });
  const panelRef = useRef<HTMLDivElement>(null);

  // Load saved position from storage on mount
  useEffect(() => {
    const loadPosition = async () => {
      const savedPosition = await devModeStorage.getPanelPosition();
      if (savedPosition) {
        // Use saved position
        setPosition(savedPosition);
      } else {
        // Default to bottom-left if no saved position
        const margin = 20;
        const panelHeight = 600;
        setPosition({
          x: margin,
          y: Math.max(margin, window.innerHeight - panelHeight - margin)
        });
      }
    };

    if (isOpen) {
      loadPosition();
    }
  }, [isOpen]);

  // Load schema elements and state from storage on mount
  useEffect(() => {
    loadSchemaElements();
    loadDevModeState();
  }, []);

  async function loadDevModeState() {
    // Load saved platform
    const savedPlatform = await devModeStorage.getCurrentPlatform();
    if (savedPlatform) {
      setPlatform(savedPlatform);
    } else {
      // Detect platform if not saved
      const detectedPlatform = detectCurrentPlatform();
      setPlatform(detectedPlatform);
      if (detectedPlatform) {
        await devModeStorage.setCurrentPlatform(detectedPlatform);
      }
    }

    // Load saved page type
    const savedPageType = await devModeStorage.getCurrentPageType();
    if (savedPageType) {
      setPageType(savedPageType);
    } else {
      // Detect page type if not saved
      const detected = detectPageType();
      if (detected.type) {
        setPageType(detected.type as PageType);
        await devModeStorage.setCurrentPageType(detected.type as PageType);
      }
    }
  }

  // Mouse event handlers for dragging
  const handleMouseDown = (e: React.MouseEvent) => {
    if (panelRef.current) {
      setIsDragging(true);
      setDragOffset({
        x: e.clientX - position.x,
        y: e.clientY - position.y,
      });
    }
  };

  useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => {
      if (isDragging) {
        setPosition({
          x: e.clientX - dragOffset.x,
          y: e.clientY - dragOffset.y,
        });
      }
    };

    const handleMouseUp = async () => {
      setIsDragging(false);
      // Save position when drag ends
      await devModeStorage.setPanelPosition(position);
    };

    if (isDragging) {
      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
    }

    return () => {
      document.removeEventListener('mousemove', handleMouseMove);
      document.removeEventListener('mouseup', handleMouseUp);
    };
  }, [isDragging, dragOffset, position]);

  async function loadSchemaElements() {
    const elements = await devModeStorage.getSchemaElements();
    setSchemaElements(elements);
  }

  async function handlePageTypeChange(newPageType: PageType) {
    setPageType(newPageType);
    await devModeStorage.setCurrentPageType(newPageType);
  }

  const handleStartInspecting = () => {
    setInspecting(true);
    setSelectedElement(null);
    setSelectors(null);
    setEditingIndex(null);
  };

  const handleStopInspecting = () => {
    setInspecting(false);
    setSelectedElement(null);
    setSelectors(null);
  };

  const handleElementSelected = (element: Element, selectorResult: SelectorResult) => {
    setSelectedElement(element);
    setSelectors(selectorResult);
    setInspecting(false);
  };

  const handleAddElement = async (element: SchemaElement) => {
    // Add metadata to element
    const isWrapper = element.element_type.endsWith('_wrapper');
    element.is_wrapper = isWrapper;
    element.page_type = pageType; // Save current page type with element

    if (editingIndex !== null) {
      // Update existing element
      const newElements = [...schemaElements];
      newElements[editingIndex] = element;
      setSchemaElements(newElements);
      await devModeStorage.setSchemaElements(newElements);
      setEditingIndex(null);
    } else {
      // Add new element
      const newElements = [...schemaElements, element];
      setSchemaElements(newElements);
      await devModeStorage.setSchemaElements(newElements);
    }

    // Reset selection
    setSelectedElement(null);
    setSelectors(null);
  };

  const handleEditElement = (index: number) => {
    const element = schemaElements[index];
    setEditingIndex(index);
    // Create a mock SelectorResult from the element
    setSelectors({
      cssSelector: element.css_selector,
      xpathSelector: element.xpath_selector,
      confidence: 'medium',
      warnings: [],
    });
    setSelectedElement(document.body); // Placeholder
  };

  const handleRemoveElement = async (index: number) => {
    const newElements = schemaElements.filter((_, i) => i !== index);
    setSchemaElements(newElements);
    await devModeStorage.setSchemaElements(newElements);
  };

  const handleCopySchema = async () => {
    if (!platform) return;

    // Use complete schema export (organized by page type)
    const schemaJson = await devModeStorage.exportCompleteSchema(platform);
    await navigator.clipboard.writeText(schemaJson);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handleDownloadSchema = async () => {
    if (!platform) return;

    // Use complete schema export (organized by page type)
    const schemaJson = await devModeStorage.exportCompleteSchema(platform);
    const blob = new Blob([schemaJson], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `schema-${platform}-complete.json`;
    a.click();
    URL.revokeObjectURL(url);
  };

  const handleImportSchema = async () => {
    try {
      const schemaData: PlatformSchema = JSON.parse(importJson);

      if (!schemaData.platform || !schemaData.page_type || !schemaData.elements) {
        alert('Invalid schema format. Must include platform, page_type, and elements.');
        return;
      }

      // Set platform and page type from imported schema
      setPlatform(schemaData.platform as Platform);
      setPageType(schemaData.page_type);

      // Import elements
      setSchemaElements(schemaData.elements);
      await devModeStorage.setSchemaElements(schemaData.elements);

      setShowImportModal(false);
      setImportJson('');
      alert(`Successfully imported ${schemaData.elements.length} schema elements!`);
    } catch (e) {
      alert('Failed to import schema: ' + (e instanceof Error ? e.message : 'Invalid JSON'));
    }
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
      const content = event.target?.result as string;
      setImportJson(content);
      setShowImportModal(true);
    };
    reader.readAsText(file);
  };

  const handleClearSchema = async () => {
    if (confirm('Are you sure you want to clear all schema elements?')) {
      setSchemaElements([]);
      await devModeStorage.clearSchema();
    }
  };

  const handleCancelSelection = () => {
    setSelectedElement(null);
    setSelectors(null);
    setInspecting(false);
    setEditingIndex(null);
  };

  const handleSelectAnother = () => {
    // Clear current selection but return to inspect mode
    setSelectedElement(null);
    setSelectors(null);
    setEditingIndex(null);
    setInspecting(true); // Re-enable inspecting
  };


  // Removed old complex test handler - now using simple tab opening

  // Group elements by wrapper
  const groupedElements = schemaElements.reduce((acc, element, index) => {
    if (element.is_wrapper) {
      if (!acc[element.element_type]) {
        acc[element.element_type] = { wrapper: { element, index }, children: [] };
      }
    } else if (element.parent_element) {
      if (!acc[element.parent_element]) {
        acc[element.parent_element] = { wrapper: null, children: [] };
      }
      acc[element.parent_element].children.push({ element, index });
    } else {
      // Standalone elements
      if (!acc['_standalone']) {
        acc['_standalone'] = { wrapper: null, children: [] };
      }
      acc['_standalone'].children.push({ element, index });
    }
    return acc;
  }, {} as Record<string, { wrapper: { element: SchemaElement; index: number } | null; children: Array<{ element: SchemaElement; index: number }> }>);

  // Only render when dev mode is enabled
  if (!isOpen) return null;

  return (
    <>
      <ElementInspector
        isActive={inspecting}
        onElementSelected={handleElementSelected}
      />

      {/* Import Modal */}
      {showImportModal && (
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          backgroundColor: 'rgba(0, 0, 0, 0.5)',
          zIndex: 9999999,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          padding: '20px',
        }}>
          <div style={{
            backgroundColor: '#ffffff',
            borderRadius: '12px',
            padding: '24px',
            maxWidth: '600px',
            width: '100%',
            maxHeight: '80vh',
            overflow: 'auto',
          }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
              <h3 style={{ margin: 0, fontSize: '18px', fontWeight: '600' }}>Import Schema</h3>
              <button onClick={() => { setShowImportModal(false); setImportJson(''); }} style={{ background: 'none', border: 'none', cursor: 'pointer' }}>
                <X size={20} />
              </button>
            </div>

            <textarea
              value={importJson}
              onChange={(e) => setImportJson(e.target.value)}
              placeholder="Paste schema JSON here..."
              rows={15}
              style={{
                width: '100%',
                padding: '12px',
                borderRadius: '6px',
                border: '1px solid #d1d5db',
                fontSize: '12px',
                fontFamily: 'monospace',
                marginBottom: '16px',
              }}
            />

            <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
              <button
                onClick={() => { setShowImportModal(false); setImportJson(''); }}
                style={{
                  padding: '8px 16px',
                  backgroundColor: '#f3f4f6',
                  color: '#374151',
                  border: 'none',
                  borderRadius: '6px',
                  fontSize: '13px',
                  fontWeight: '500',
                  cursor: 'pointer',
                }}
              >
                Cancel
              </button>
              <button
                onClick={handleImportSchema}
                style={{
                  padding: '8px 16px',
                  backgroundColor: '#3b82f6',
                  color: '#ffffff',
                  border: 'none',
                  borderRadius: '6px',
                  fontSize: '13px',
                  fontWeight: '500',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px',
                }}
              >
                <Upload size={16} />
                Import
              </button>
            </div>
          </div>
        </div>
      )}

      <div
        ref={panelRef}
        id="evenleads-devmode-panel"
        style={{
          position: 'fixed',
          left: `${position.x}px`,
          top: `${position.y}px`,
          width: '450px',
          maxHeight: '90vh',
          backgroundColor: '#ffffff',
          boxShadow: '0 10px 25px rgba(0, 0, 0, 0.2)',
          borderRadius: '12px',
          zIndex: 999999,
          display: 'flex',
          flexDirection: 'column',
          overflow: 'hidden',
        }}
      >
        {/* Header - Draggable */}
        <div
          onMouseDown={handleMouseDown}
          style={{
            padding: '16px',
            backgroundColor: '#3b82f6',
            color: '#ffffff',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            cursor: isDragging ? 'grabbing' : 'grab',
            userSelect: 'none',
          }}>
          <div>
            <div style={{ fontSize: '16px', fontWeight: '600', marginBottom: '2px' }}>
              DEV Mode - Schema Builder
            </div>
            <div style={{ fontSize: '11px', opacity: 0.9 }}>
              {platform ? platform.toUpperCase() : 'Unknown'} • {pageType}
            </div>
          </div>
          <button
            onClick={onClose}
            style={{
              background: 'rgba(255, 255, 255, 0.2)',
              border: 'none',
              borderRadius: '6px',
              padding: '6px',
              cursor: 'pointer',
              color: '#ffffff',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <X size={20} />
          </button>
        </div>

        {/* Content */}
        <div style={{
          flex: 1,
          overflowY: 'auto',
          padding: '16px',
        }}>
          {/* LinkedIn Warning */}
          {isLinkedIn && (
            <div style={{
              marginBottom: '16px',
              padding: '12px',
              backgroundColor: '#fef3c7',
              border: '2px solid #f59e0b',
              borderRadius: '6px',
              fontSize: '11px',
              color: '#78350f',
              lineHeight: '1.5',
            }}>
              <strong>⚠️ Warning:</strong> DevMode on LinkedIn may cause invalid URL requests. If you experience issues, please disable DevMode or use it on other platforms (Reddit, Facebook, etc.).
            </div>
          )}

          {/* Debug Dashboard (LinkedIn only) */}
          {isLinkedIn && (
            <div style={{
              marginBottom: '16px',
              backgroundColor: '#1f2937',
              borderRadius: '8px',
              overflow: 'hidden',
            }}>
              <div
                onClick={() => setDebugDashboardExpanded(!debugDashboardExpanded)}
                style={{
                  padding: '12px 16px',
                  backgroundColor: '#374151',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  userSelect: 'none',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#ffffff' }}>
                  <BarChart3 size={16} />
                  <span style={{ fontSize: '14px', fontWeight: '600' }}>📊 Debug Dashboard</span>
                </div>
                <span style={{ fontSize: '12px', color: '#9ca3af' }}>
                  {debugDashboardExpanded ? '▼' : '▶'}
                </span>
              </div>

              {debugDashboardExpanded && (
                <div style={{ height: '500px' }}>
                  <DebugDashboard />
                </div>
              )}
            </div>
          )}

          {/* Page Type Selector */}
          <div style={{ marginBottom: '16px' }}>
            <label style={{ display: 'block', fontSize: '12px', fontWeight: '500', marginBottom: '6px' }}>
              Page Type
            </label>
            <select
              value={pageType}
              onChange={(e) => handlePageTypeChange(e.target.value as PageType)}
              style={{
                width: '100%',
                padding: '8px',
                borderRadius: '6px',
                border: '1px solid #d1d5db',
                fontSize: '13px',
              }}
            >
              <option value="general">General (all pages) - Navigation, Search, Common UI</option>
              <option value="search_list">Search List (multiple posts)</option>
              <option value="post_page">Post Page (single post)</option>
              <option value="profile">Profile (person page)</option>
              <option value="group">Group</option>
              <option value="person_feed">Person Feed</option>
              <option value="feed_page">Feed Page</option>
            </select>
            {pageType === 'general' && (
              <div style={{
                marginTop: '8px',
                padding: '8px 10px',
                backgroundColor: '#dbeafe',
                borderRadius: '4px',
                fontSize: '11px',
                color: '#1e40af',
                lineHeight: '1.4',
              }}>
                <strong>📍 General Page:</strong> Select elements that appear across ALL pages (search bars, navigation menus, login buttons, site-wide UI elements)
              </div>
            )}
          </div>

          {/* Schema Builder (when element selected) */}
          {selectedElement && selectors && (
            <SchemaBuilder
              selectedElement={selectedElement}
              selectors={selectors}
              schemaElements={schemaElements}
              onAddElement={handleAddElement}
              onCancel={handleCancelSelection}
              onSelectAnother={handleSelectAnother}
              existingElement={editingIndex !== null ? schemaElements[editingIndex] : undefined}
            />
          )}

          {/* Actions (when not building) */}
          {!selectedElement && (
            <>
              {/* Simple Manual Test */}
              <div style={{
                marginBottom: '16px',
                padding: '16px',
                backgroundColor: '#f0fdf4',
                border: '2px solid #10b981',
                borderRadius: '8px',
              }}>
                {/* Collapsible Header */}
                <div
                  onClick={() => setTestSectionExpanded(!testSectionExpanded)}
                  style={{
                    fontSize: '14px',
                    fontWeight: '600',
                    marginBottom: testSectionExpanded ? '12px' : '0',
                    color: '#065f46',
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    cursor: 'pointer',
                    userSelect: 'none',
                  }}
                >
                  <span>🧪 Quick Test (Open in New Tab)</span>
                  <span style={{ fontSize: '12px' }}>{testSectionExpanded ? '▼' : '▶'}</span>
                </div>

                {/* Simple Test Content */}
                {testSectionExpanded && (
                  <>
                    <div style={{ fontSize: '11px', color: '#065f46', marginBottom: '12px', lineHeight: '1.5' }}>
                      Test a single profile manually. Paste a profile URL, enter your message, and click "Open & Test" to open it in a new tab.
                    </div>

                    {/* Profile URL Input */}
                    <div style={{ marginBottom: '12px' }}>
                      <label style={{ display: 'block', fontSize: '11px', fontWeight: '500', marginBottom: '4px', color: '#374151' }}>
                        Profile URL to Test
                      </label>
                      <input
                        type="text"
                        value={testProfileUrl}
                        onChange={(e) => setTestProfileUrl(e.target.value)}
                        placeholder="https://linkedin.com/in/john-doe"
                        style={{
                          width: '100%',
                          padding: '8px',
                          borderRadius: '6px',
                          border: '1px solid #d1d5db',
                          fontSize: '11px',
                          fontFamily: 'monospace',
                        }}
                      />
                    </div>

                    {/* Test Message Input */}
                    <div style={{ marginBottom: '12px' }}>
                      <label style={{ display: 'block', fontSize: '11px', fontWeight: '500', marginBottom: '4px', color: '#374151' }}>
                        Test Message
                      </label>
                      <textarea
                        value={testMessage}
                        onChange={(e) => setTestMessage(e.target.value)}
                        rows={4}
                        style={{
                          width: '100%',
                          padding: '8px',
                          borderRadius: '6px',
                          border: '1px solid #d1d5db',
                          fontSize: '11px',
                          lineHeight: '1.5',
                          resize: 'vertical',
                        }}
                      />
                      <div style={{ fontSize: '10px', color: '#6b7280', marginTop: '4px' }}>
                        {testMessage.length} characters
                      </div>
                    </div>

                    {/* Test on Current Page Button */}
                    <button
                      onClick={async () => {
                        if (!testMessage.trim()) {
                          alert('Please enter a test message');
                          return;
                        }

                        try {
                          // Import and run the test directly on current page
                          const { LinkedInEngine } = await import('../../utils/engines/LinkedInEngine');
                          console.log('[DevMode] Starting messaging test on current page...');

                          const engine = new LinkedInEngine();

                          // Just send the message - don't navigate!
                          const result = await engine.sendMessage(testMessage);

                          console.log('[DevMode] Test completed:', result);
                          alert(result.success ? `✓ Success: ${result.message}` : `✗ Failed: ${result.message}`);
                        } catch (error) {
                          console.error('[DevMode] Error during test:', error);
                          alert('Error: ' + (error as Error).message);
                        }
                      }}
                      style={{
                        width: '100%',
                        padding: '12px',
                        backgroundColor: '#3b82f6',
                        color: '#ffffff',
                        border: 'none',
                        borderRadius: '6px',
                        fontSize: '13px',
                        fontWeight: '600',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '8px',
                        marginBottom: '8px',
                      }}
                    >
                      <Send size={16} />
                      Test on Current Page
                    </button>

                    {/* Open & Test Button */}
                    <button
                      onClick={async () => {
                        if (!testProfileUrl.trim()) {
                          alert('Please enter a profile URL');
                          return;
                        }

                        if (!testMessage.trim()) {
                          alert('Please enter a test message');
                          return;
                        }

                        if (!platform) {
                          alert('Platform not detected. Please make sure you are on a supported platform.');
                          return;
                        }

                        try {
                          await chrome.runtime.sendMessage({
                            type: 'START_MESSAGING_TEST',
                            payload: {
                              profileUrl: testProfileUrl,
                              testMessage: testMessage,
                              platform: platform,
                              openDevMode: true,
                            }
                          });

                          console.log('[DevMode] Messaging test started successfully');
                        } catch (error) {
                          console.error('[DevMode] Error starting messaging test:', error);
                          alert('Error starting test: ' + (error as Error).message);
                        }
                      }}
                      style={{
                        width: '100%',
                        padding: '12px',
                        backgroundColor: '#10b981',
                        color: '#ffffff',
                        border: 'none',
                        borderRadius: '6px',
                        fontSize: '13px',
                        fontWeight: '600',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '8px',
                      }}
                    >
                      <Play size={16} />
                      Open & Test in New Tab
                    </button>

                    <div style={{
                      marginTop: '8px',
                      padding: '8px',
                      backgroundColor: '#e0f2fe',
                      borderRadius: '4px',
                      fontSize: '10px',
                      color: '#075985',
                    }}>
                      💡 The new tab will automatically test the messaging when it loads. Check the console for results.
                    </div>
                  </>
                )}
              </div>

              {/* Action Buttons */}
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px', marginBottom: '12px' }}>
                <button
                  onClick={inspecting ? handleStopInspecting : handleStartInspecting}
                  style={{
                    padding: '10px',
                    backgroundColor: inspecting ? '#ef4444' : '#10b981',
                    color: '#ffffff',
                    border: 'none',
                    borderRadius: '6px',
                    fontSize: '13px',
                    fontWeight: '500',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                  }}
                >
                  <Code size={16} />
                  {inspecting ? 'Stop inspecting' : 'Inspect'}
                </button>

                <button
                  onClick={() => {
                    // Trigger manual input mode
                    setSelectedElement(document.body); // Placeholder
                    setSelectors({
                      cssSelector: '',
                      xpathSelector: '',
                      confidence: 'medium',
                      warnings: ['Manual input mode - enter selectors below'],
                    });
                    setInspecting(false);
                  }}
                  style={{
                    padding: '10px',
                    backgroundColor: '#0ea5e9',
                    color: '#ffffff',
                    border: 'none',
                    borderRadius: '6px',
                    fontSize: '13px',
                    fontWeight: '500',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                  }}
                >
                  <Edit2 size={16} />
                  Manual Input
                </button>

                <button
                  onClick={() => setShowImportModal(true)}
                  style={{
                    padding: '10px',
                    backgroundColor: '#6366f1',
                    color: '#ffffff',
                    border: 'none',
                    borderRadius: '6px',
                    fontSize: '13px',
                    fontWeight: '500',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                  }}
                >
                  <Upload size={16} />
                  Import
                </button>

                <input
                  ref={fileInputRef}
                  type="file"
                  accept=".json"
                  onChange={handleFileUpload}
                  style={{ display: 'none' }}
                />

                <button
                  onClick={() => fileInputRef.current?.click()}
                  style={{
                    padding: '10px',
                    backgroundColor: '#8b5cf6',
                    color: '#ffffff',
                    border: 'none',
                    borderRadius: '6px',
                    fontSize: '13px',
                    fontWeight: '500',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                  }}
                >
                  <FileJson size={16} />
                  Upload
                </button>

                <button
                  onClick={handleClearSchema}
                  disabled={schemaElements.length === 0}
                  style={{
                    padding: '10px',
                    backgroundColor: schemaElements.length === 0 ? '#e5e7eb' : '#ef4444',
                    color: '#ffffff',
                    border: 'none',
                    borderRadius: '6px',
                    fontSize: '13px',
                    fontWeight: '500',
                    cursor: schemaElements.length === 0 ? 'not-allowed' : 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                  }}
                >
                  <Trash2 size={16} />
                  Clear
                </button>
              </div>

              {/* Element List with Wrapper Hierarchy */}
              <div style={{
                border: '1px solid #e5e7eb',
                borderRadius: '8px',
                marginBottom: '12px',
              }}>
                <div style={{
                  padding: '12px',
                  borderBottom: '1px solid #e5e7eb',
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  backgroundColor: '#f9fafb',
                }}>
                  <span style={{ fontSize: '13px', fontWeight: '500' }}>
                    Schema Elements ({schemaElements.length})
                  </span>
                  <button
                    onClick={() => setShowElementList(!showElementList)}
                    style={{
                      background: 'none',
                      border: 'none',
                      cursor: 'pointer',
                      padding: '4px',
                      display: 'flex',
                      alignItems: 'center',
                    }}
                  >
                    {showElementList ? <EyeOff size={16} /> : <Eye size={16} />}
                  </button>
                </div>

                {showElementList && (
                  <div style={{
                    maxHeight: '300px',
                    overflowY: 'auto',
                  }}>
                    {schemaElements.length === 0 ? (
                      <div style={{
                        padding: '24px',
                        textAlign: 'center',
                        color: '#6b7280',
                        fontSize: '13px',
                      }}>
                        No elements yet. Start by inspecting a wrapper element (post_wrapper, person_wrapper, etc.)
                      </div>
                    ) : (
                      Object.entries(groupedElements).map(([wrapperType, group]) => (
                        <div key={wrapperType} style={{ borderBottom: '1px solid #f3f4f6' }}>
                          {/* Wrapper Element */}
                          {group.wrapper && (
                            <div style={{
                              padding: '12px',
                              backgroundColor: '#fef3c7',
                              borderBottom: '1px solid #fde68a',
                              display: 'flex',
                              justifyContent: 'space-between',
                              alignItems: 'flex-start',
                              gap: '12px',
                            }}>
                              <div style={{ flex: 1 }}>
                                <div style={{ fontSize: '13px', fontWeight: '600', marginBottom: '4px', color: '#92400e', display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap' }}>
                                  📦 {group.wrapper.element.name || group.wrapper.element.element_type.replace(/_/g, ' ').toUpperCase()}
                                  {group.wrapper.element.page_type && (
                                    <span style={{
                                      fontSize: '9px',
                                      fontWeight: '500',
                                      padding: '2px 6px',
                                      borderRadius: '3px',
                                      backgroundColor: group.wrapper.element.page_type === 'general' ? '#14b8a6' : '#6366f1',
                                      color: '#ffffff',
                                    }}>
                                      {group.wrapper.element.page_type === 'general' ? '🌐 GENERAL' : `📄 ${group.wrapper.element.page_type}`}
                                    </span>
                                  )}
                                </div>
                                <div style={{
                                  fontSize: '10px',
                                  fontFamily: 'monospace',
                                  color: '#78350f',
                                  overflow: 'hidden',
                                  textOverflow: 'ellipsis',
                                  whiteSpace: 'nowrap',
                                }}>
                                  {group.wrapper.element.css_selector}
                                </div>
                              </div>
                              <div style={{ display: 'flex', gap: '4px' }}>
                                <button
                                  onClick={() => handleEditElement(group.wrapper!.index)}
                                  style={{
                                    background: 'none',
                                    border: 'none',
                                    cursor: 'pointer',
                                    padding: '4px',
                                    color: '#3b82f6',
                                  }}
                                >
                                  <Edit2 size={14} />
                                </button>
                                <button
                                  onClick={() => handleRemoveElement(group.wrapper!.index)}
                                  style={{
                                    background: 'none',
                                    border: 'none',
                                    cursor: 'pointer',
                                    padding: '4px',
                                    color: '#ef4444',
                                  }}
                                >
                                  <Trash2 size={14} />
                                </button>
                              </div>
                            </div>
                          )}

                          {/* Child Elements */}
                          {group.children.map(({ element, index }) => (
                            <div
                              key={index}
                              style={{
                                padding: '10px 12px 10px 28px',
                                borderBottom: '1px solid #f9fafb',
                                display: 'flex',
                                justifyContent: 'space-between',
                                alignItems: 'flex-start',
                                gap: '12px',
                              }}
                            >
                              <div style={{ flex: 1 }}>
                                <div style={{ fontSize: '12px', fontWeight: '500', marginBottom: '4px', color: '#374151', display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap' }}>
                                  {group.wrapper ? '└─ ' : ''}{element.name || element.element_type.replace(/_/g, ' ')}
                                  {element.page_type && (
                                    <span style={{
                                      fontSize: '8px',
                                      fontWeight: '500',
                                      padding: '2px 5px',
                                      borderRadius: '3px',
                                      backgroundColor: element.page_type === 'general' ? '#14b8a6' : '#6366f1',
                                      color: '#ffffff',
                                    }}>
                                      {element.page_type === 'general' ? '🌐 GENERAL' : `📄 ${element.page_type}`}
                                    </span>
                                  )}
                                </div>
                                <div style={{
                                  fontSize: '10px',
                                  fontFamily: 'monospace',
                                  color: '#6b7280',
                                  overflow: 'hidden',
                                  textOverflow: 'ellipsis',
                                  whiteSpace: 'nowrap',
                                }}>
                                  {element.css_selector}
                                </div>
                                {element.description && (
                                  <div style={{ fontSize: '10px', color: '#9ca3af', marginTop: '2px' }}>
                                    {element.description}
                                  </div>
                                )}
                              </div>
                              <div style={{ display: 'flex', gap: '4px' }}>
                                <button
                                  onClick={() => handleEditElement(index)}
                                  style={{
                                    background: 'none',
                                    border: 'none',
                                    cursor: 'pointer',
                                    padding: '4px',
                                    color: '#3b82f6',
                                  }}
                                >
                                  <Edit2 size={12} />
                                </button>
                                <button
                                  onClick={() => handleRemoveElement(index)}
                                  style={{
                                    background: 'none',
                                    border: 'none',
                                    cursor: 'pointer',
                                    padding: '4px',
                                    color: '#ef4444',
                                  }}
                                >
                                  <Trash2 size={12} />
                                </button>
                              </div>
                            </div>
                          ))}
                        </div>
                      ))
                    )}
                  </div>
                )}
              </div>

              {/* Export Actions */}
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px', marginBottom: '16px' }}>
                <button
                  onClick={handleCopySchema}
                  disabled={schemaElements.length === 0}
                  style={{
                    padding: '10px',
                    backgroundColor: schemaElements.length === 0 ? '#e5e7eb' : (copied ? '#10b981' : '#3b82f6'),
                    color: schemaElements.length === 0 ? '#9ca3af' : '#ffffff',
                    border: 'none',
                    borderRadius: '6px',
                    fontSize: '13px',
                    fontWeight: '500',
                    cursor: schemaElements.length === 0 ? 'not-allowed' : 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                  }}
                >
                  {copied ? <Check size={16} /> : <Copy size={16} />}
                  {copied ? 'Copied!' : 'Copy JSON'}
                </button>
                <button
                  onClick={handleDownloadSchema}
                  disabled={schemaElements.length === 0}
                  style={{
                    padding: '10px',
                    backgroundColor: schemaElements.length === 0 ? '#e5e7eb' : '#3b82f6',
                    color: schemaElements.length === 0 ? '#9ca3af' : '#ffffff',
                    border: 'none',
                    borderRadius: '6px',
                    fontSize: '13px',
                    fontWeight: '500',
                    cursor: schemaElements.length === 0 ? 'not-allowed' : 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                  }}
                >
                  <Download size={16} />
                  Download
                </button>
              </div>

              {/* Instructions */}
              <div style={{
                padding: '12px',
                backgroundColor: '#f0f9ff',
                borderRadius: '6px',
                fontSize: '11px',
                color: '#0369a1',
              }}>
                <strong>Workflow:</strong>
                <ol style={{ margin: '6px 0 0 0', paddingLeft: '16px', lineHeight: '1.6' }}>
                  <li><strong>Select wrapper first</strong> (post_wrapper, person_wrapper, etc.)</li>
                  <li>Then select child elements <strong>relative to wrapper</strong></li>
                  <li>Use relative XPath (starts with <code>.</code>) or CSS for children</li>
                  <li>Import existing schemas to edit</li>
                  <li>Copy/Download JSON → Import in admin panel</li>
                </ol>
              </div>
            </>
          )}
        </div>
      </div>
    </>
  );
}
