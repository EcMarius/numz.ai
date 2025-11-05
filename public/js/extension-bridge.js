/**
 * EvenLeads Extension Communication Bridge
 *
 * This script facilitates communication between the web dashboard and the browser extension.
 * It uses custom events dispatched on the document to send messages to the extension's content script.
 */

window.EvenLeadsExtension = {
    /**
     * Check if the extension is installed and active
     * @returns {Promise<boolean>}
     */
    async isInstalled() {
        return new Promise((resolve) => {
            const timeout = setTimeout(() => resolve(false), 1000);

            const checkListener = (event) => {
                if (event.detail && event.detail.type === 'EXTENSION_PONG') {
                    clearTimeout(timeout);
                    document.removeEventListener('evenleads-extension-response', checkListener);
                    resolve(true);
                }
            };

            document.addEventListener('evenleads-extension-response', checkListener);

            // Send ping message
            document.dispatchEvent(new CustomEvent('evenleads-dashboard-message', {
                detail: {
                    type: 'EXTENSION_PING',
                    timestamp: Date.now()
                }
            }));
        });
    },

    /**
     * Open the extension sidebar and optionally trigger a sync
     * @param {number} campaignId - The campaign ID to sync
     * @param {boolean} autoSync - Whether to automatically start syncing
     * @returns {Promise<boolean>}
     */
    async openSidebarAndSync(campaignId, autoSync = false) {
        const installed = await this.isInstalled();

        if (!installed) {
            console.warn('EvenLeads extension is not installed');
            return false;
        }

        return new Promise((resolve) => {
            const timeout = setTimeout(() => resolve(false), 3000);

            const responseListener = (event) => {
                if (event.detail && event.detail.type === 'SIDEBAR_OPENED') {
                    clearTimeout(timeout);
                    document.removeEventListener('evenleads-extension-response', responseListener);
                    resolve(true);
                }
            };

            document.addEventListener('evenleads-extension-response', responseListener);

            // Send message to open sidebar
            document.dispatchEvent(new CustomEvent('evenleads-dashboard-message', {
                detail: {
                    type: 'OPEN_SIDEBAR',
                    campaignId: campaignId,
                    autoSync: autoSync,
                    timestamp: Date.now()
                }
            }));
        });
    },

    /**
     * Trigger a sync for a specific campaign via extension
     * @param {number} campaignId - The campaign ID to sync
     * @returns {Promise<boolean>}
     */
    async triggerSync(campaignId) {
        const installed = await this.isInstalled();

        if (!installed) {
            console.warn('EvenLeads extension is not installed');
            return false;
        }

        return new Promise((resolve) => {
            const timeout = setTimeout(() => resolve(false), 3000);

            const responseListener = (event) => {
                if (event.detail && event.detail.type === 'SYNC_STARTED') {
                    clearTimeout(timeout);
                    document.removeEventListener('evenleads-extension-response', responseListener);
                    resolve(true);
                }
            };

            document.addEventListener('evenleads-extension-response', responseListener);

            // Send message to trigger sync
            document.dispatchEvent(new CustomEvent('evenleads-dashboard-message', {
                detail: {
                    type: 'TRIGGER_SYNC',
                    campaignId: campaignId,
                    timestamp: Date.now()
                }
            }));
        });
    },

    /**
     * Get authentication state from extension
     * @returns {Promise<Object|null>}
     */
    async getAuthState() {
        const installed = await this.isInstalled();

        if (!installed) {
            return null;
        }

        return new Promise((resolve) => {
            const timeout = setTimeout(() => resolve(null), 2000);

            const responseListener = (event) => {
                if (event.detail && event.detail.type === 'AUTH_STATE') {
                    clearTimeout(timeout);
                    document.removeEventListener('evenleads-extension-response', responseListener);
                    resolve(event.detail.authState);
                }
            };

            document.addEventListener('evenleads-extension-response', responseListener);

            // Request auth state from extension
            document.dispatchEvent(new CustomEvent('evenleads-dashboard-message', {
                detail: {
                    type: 'GET_AUTH_STATE',
                    timestamp: Date.now()
                }
            }));
        });
    },

    /**
     * Show a notification prompting the user to install the extension
     */
    showInstallPrompt() {
        // This will be implemented by the dashboard UI (Filament notifications)
        if (window.Livewire) {
            window.Livewire.dispatch('show-extension-install-prompt');
        } else {
            alert('Please install the EvenLeads browser extension to sync this campaign.');
        }
    }
};

// Make it available globally
window.EvenLeadsExtension = window.EvenLeadsExtension || {};

console.log('EvenLeads Extension Bridge loaded');
