<x-filament-panels::page>
    {{ $this->form }}

    @push('styles')
    <style>
        /* Glassmorphism AI Edit UI Styles */
        .ai-edit-popup {
            position: fixed;
            z-index: 9999;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
            min-width: 400px;
            max-width: 500px;
            animation: slideIn 0.2s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Light mode styles */
        html:not(.dark) .ai-edit-popup {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        /* Dark mode styles */
        html.dark .ai-edit-popup {
            background: rgba(17, 24, 39, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .ai-edit-input-wrapper {
            position: relative;
            margin-bottom: 12px;
        }

        .ai-edit-label {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }

        html:not(.dark) .ai-edit-label {
            color: #374151;
        }

        html.dark .ai-edit-label {
            color: #d1d5db;
        }

        .ai-edit-input-container {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ai-edit-input {
            flex: 1;
            padding: 10px 14px;
            padding-right: 50px;
            border-radius: 8px;
            font-size: 0.875rem;
            outline: none;
            transition: all 0.2s;
        }

        html:not(.dark) .ai-edit-input {
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #d1d5db;
        }

        html.dark .ai-edit-input {
            background: #1f2937;
            color: #f3f4f6;
            border: 1px solid #374151;
        }

        html:not(.dark) .ai-edit-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        html.dark .ai-edit-input:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
        }

        .ai-edit-input::placeholder {
            opacity: 0.5;
        }

        .ai-edit-submit-btn {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            border-radius: 6px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        html:not(.dark) .ai-edit-submit-btn {
            background: #1f2937;
            color: #ffffff;
        }

        html.dark .ai-edit-submit-btn {
            background: #f3f4f6;
            color: #1f2937;
        }

        html:not(.dark) .ai-edit-submit-btn:hover {
            background: #111827;
            transform: translateY(-50%) scale(1.05);
        }

        html.dark .ai-edit-submit-btn:hover {
            background: #ffffff;
            transform: translateY(-50%) scale(1.05);
        }

        .ai-edit-submit-btn:active {
            transform: translateY(-50%) scale(0.95);
        }

        .ai-edit-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .ai-edit-action-btn {
            flex: 1;
            min-width: 70px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        html:not(.dark) .ai-edit-action-btn {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        html.dark .ai-edit-action-btn {
            background: #374151;
            color: #d1d5db;
            border: 1px solid #4b5563;
        }

        html:not(.dark) .ai-edit-action-btn:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }

        html.dark .ai-edit-action-btn:hover {
            background: #4b5563;
            border-color: #6b7280;
        }

        .ai-edit-action-btn:active {
            transform: scale(0.98);
        }

        .ai-edit-loading {
            display: none;
            text-align: center;
            padding: 8px;
            font-size: 0.875rem;
        }

        .ai-edit-loading.active {
            display: block;
        }

        html:not(.dark) .ai-edit-loading {
            color: #6b7280;
        }

        html.dark .ai-edit-loading {
            color: #9ca3af;
        }

        .ai-edit-close-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        html:not(.dark) .ai-edit-close-btn {
            background: rgba(0, 0, 0, 0.05);
            color: #6b7280;
        }

        html.dark .ai-edit-close-btn {
            background: rgba(255, 255, 255, 0.1);
            color: #9ca3af;
        }

        .ai-edit-close-btn:hover {
            transform: scale(1.1);
        }

        html:not(.dark) .ai-edit-close-btn:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        html.dark .ai-edit-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let aiEditPopup = null;
            let selectedText = '';
            let selectionRange = null;
            let savedSelection = null;
            let isProcessing = false;

            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Save current selection
            function saveSelection() {
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    savedSelection = {
                        range: selection.getRangeAt(0).cloneRange(),
                        text: selection.toString()
                    };
                }
            }

            // Restore saved selection
            function restoreSelection() {
                if (savedSelection && savedSelection.range) {
                    const selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(savedSelection.range);
                }
            }

            // Create and show AI edit popup
            function showAIEditPopup(rect) {
                if (isProcessing) return;

                // Save selection before removing popup
                saveSelection();

                // Remove existing popup if any
                removeAIEditPopup();

                // Create popup
                aiEditPopup = document.createElement('div');
                aiEditPopup.className = 'ai-edit-popup';

                aiEditPopup.innerHTML = `
                    <button class="ai-edit-close-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div class="ai-edit-input-wrapper">
                        <label class="ai-edit-label">Edit with AI, type anything you want</label>
                        <div class="ai-edit-input-container">
                            <input type="text" class="ai-edit-input" placeholder="e.g., Make this more engaging..." id="ai-edit-custom-input">
                            <button class="ai-edit-submit-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="ai-edit-actions">
                        <button class="ai-edit-action-btn" data-action="shorter">Shorter</button>
                        <button class="ai-edit-action-btn" data-action="longer">Longer</button>
                        <button class="ai-edit-action-btn" data-action="seo">SEO optimize</button>
                        <button class="ai-edit-action-btn" data-action="reword">Reword</button>
                    </div>
                    <div class="ai-edit-loading">
                        <svg class="inline w-4 h-4 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    </div>
                `;

                document.body.appendChild(aiEditPopup);

                // Position the popup
                const popupRect = aiEditPopup.getBoundingClientRect();
                const gap = 12;

                let top = rect.bottom + window.scrollY + gap;
                let left = rect.left + window.scrollX + (rect.width / 2) - (popupRect.width / 2);

                // Check if there's room below
                if (rect.bottom + popupRect.height + gap > window.innerHeight) {
                    // Position above instead
                    top = rect.top + window.scrollY - popupRect.height - gap;
                }

                // Keep within viewport horizontally
                const maxLeft = window.innerWidth - popupRect.width - 20;
                left = Math.max(20, Math.min(left, maxLeft));

                aiEditPopup.style.left = `${left}px`;
                aiEditPopup.style.top = `${top}px`;

                // Restore selection after positioning
                setTimeout(() => {
                    restoreSelection();
                }, 10);

                // Set up event listeners
                const closeBtn = aiEditPopup.querySelector('.ai-edit-close-btn');
                closeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    removeAIEditPopup();
                });

                const input = aiEditPopup.querySelector('#ai-edit-custom-input');
                const submitBtn = aiEditPopup.querySelector('.ai-edit-submit-btn');

                submitBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    handleCustomEdit();
                });

                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        handleCustomEdit();
                    }
                });

                // Quick action buttons
                const actionBtns = aiEditPopup.querySelectorAll('.ai-edit-action-btn');
                actionBtns.forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const action = btn.getAttribute('data-action');
                        handleQuickAction(action);
                    });
                });

                // Prevent popup interactions from clearing selection
                aiEditPopup.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });

                // Focus input
                setTimeout(() => {
                    input.focus();
                }, 100);
            }

            // Remove AI edit popup
            window.removeAIEditPopup = function() {
                if (aiEditPopup) {
                    aiEditPopup.remove();
                    aiEditPopup = null;
                }
            };

            // Handle custom edit
            async function handleCustomEdit() {
                const input = document.getElementById('ai-edit-custom-input');
                const instruction = input?.value.trim();

                if (!instruction) {
                    showNotification('Please enter an instruction', 'error');
                    return;
                }

                await performAIEdit('/admin/blog/ai/edit', { text: selectedText, instruction });
            }

            // Handle quick actions
            async function handleQuickAction(action) {
                const endpoints = {
                    'shorter': '/admin/blog/ai/shorter',
                    'longer': '/admin/blog/ai/longer',
                    'seo': '/admin/blog/ai/seo',
                    'reword': '/admin/blog/ai/reword'
                };

                const endpoint = endpoints[action];
                if (!endpoint) return;

                const payload = { text: selectedText };

                // Add context for SEO optimization
                if (action === 'seo') {
                    payload.context = {
                        post_title: document.querySelector('[name="title"]')?.value || '',
                        keywords: document.querySelector('[name="meta_keywords"]')?.value || ''
                    };
                }

                await performAIEdit(endpoint, payload);
            }

            // Perform AI edit request
            async function performAIEdit(endpoint, payload) {
                if (isProcessing) return;

                isProcessing = true;
                const loadingEl = aiEditPopup?.querySelector('.ai-edit-loading');
                if (loadingEl) {
                    loadingEl.classList.add('active');
                }

                // Disable all buttons
                const buttons = aiEditPopup?.querySelectorAll('button');
                buttons?.forEach(btn => btn.disabled = true);

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();

                    if (data.success && data.content) {
                        // Replace selected text with AI-edited content
                        replaceSelectedText(data.content);
                        removeAIEditPopup();

                        // Show success notification
                        showNotification('Content edited successfully!', 'success');
                    } else {
                        throw new Error(data.message || 'Failed to edit content');
                    }
                } catch (error) {
                    console.error('AI edit error:', error);
                    showNotification(error.message || 'Failed to edit content', 'error');
                } finally {
                    if (loadingEl) {
                        loadingEl.classList.remove('active');
                    }
                    // Re-enable all buttons
                    buttons?.forEach(btn => btn.disabled = false);
                    isProcessing = false;
                }
            }

            // Replace selected text
            function replaceSelectedText(newText) {
                if (!savedSelection || !savedSelection.range) return;

                try {
                    const range = savedSelection.range;

                    // Delete the old content
                    range.deleteContents();

                    // Insert new content
                    const fragment = range.createContextualFragment(newText);
                    range.insertNode(fragment);

                    // Clear saved selection
                    savedSelection = null;
                    selectionRange = null;
                } catch (error) {
                    console.error('Error replacing text:', error);
                    showNotification('Failed to replace text', 'error');
                }
            }

            // Show notification
            function showNotification(message, type = 'success') {
                // Using Filament's notification system if available
                if (window.Filament && window.Filament.notifications) {
                    window.Filament.notifications.send({
                        message: message,
                        type: type
                    });
                } else {
                    alert(message);
                }
            }

            // Debounce timer for selection
            let selectionTimeout = null;

            // Listen for text selection
            document.addEventListener('mouseup', function(e) {
                // Don't trigger if clicking on the popup itself
                if (aiEditPopup && aiEditPopup.contains(e.target)) {
                    return;
                }

                // Clear previous timeout
                if (selectionTimeout) {
                    clearTimeout(selectionTimeout);
                }

                // Debounce to prevent multiple rapid calls
                selectionTimeout = setTimeout(() => {
                    const selection = window.getSelection();

                    if (!selection || selection.rangeCount === 0) {
                        removeAIEditPopup();
                        return;
                    }

                    const text = selection.toString().trim();

                    // Only show popup if there's selected text and it's within a rich editor
                    if (text && text.length > 3) { // At least 3 characters
                        const range = selection.getRangeAt(0);
                        const container = range.commonAncestorContainer;
                        const element = container.nodeType === 3 ? container.parentElement : container;
                        const isInRichEditor = element?.closest('.tiptap') !== null;

                        if (isInRichEditor && !isProcessing) {
                            selectedText = text;
                            selectionRange = range.cloneRange();

                            const rect = range.getBoundingClientRect();

                            // Only show if rect has valid dimensions
                            if (rect.width > 0 && rect.height > 0) {
                                showAIEditPopup(rect);
                            }
                        }
                    } else {
                        // Remove popup if no substantial text selected
                        removeAIEditPopup();
                    }
                }, 150);
            });

            // Close popup when clicking outside
            document.addEventListener('click', function(e) {
                if (aiEditPopup && !aiEditPopup.contains(e.target)) {
                    // Don't close immediately if clicking to make selection
                    setTimeout(() => {
                        const selection = window.getSelection();
                        if (!selection.toString().trim()) {
                            removeAIEditPopup();
                        }
                    }, 100);
                }
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
