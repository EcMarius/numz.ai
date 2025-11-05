/**
 * Extension Configuration
 *
 * IMPORTANT: This file contains production configuration.
 * DO NOT expose API URL configuration to end users!
 */

export const config = {
  /**
   * Production API Base URL
   * This is hardcoded and should NOT be user-configurable
   */
  API_BASE_URL: 'https://evenleads.com',

  /**
   * Extension Version
   */
  VERSION: '1.0.0',

  /**
   * OAuth Client ID
   */
  OAUTH_CLIENT_ID: 'browser-extension',

  /**
   * OAuth Scopes
   */
  OAUTH_SCOPES: 'read write campaigns leads',
} as const;

/**
 * Get the API base URL
 * In production: Always returns https://evenleads.com
 * In development: Can be overridden with VITE_API_URL environment variable
 */
export function getApiBaseUrl(): string {
  // For development only - allow override via environment variable
  if (import.meta.env.DEV && import.meta.env.VITE_API_URL) {
    return import.meta.env.VITE_API_URL;
  }

  // Production: Always use the hardcoded URL
  return config.API_BASE_URL;
}
