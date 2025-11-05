/**
 * Platform Engines Entry Point
 *
 * Centralized exports for all platform engines
 */

// Export base class
export { BasePlatformEngine } from './BasePlatformEngine';
export type { PlatformSchema, SchemaResponse } from './BasePlatformEngine';

// Export platform engines (classes only - use factory to create instances)
export { default as LinkedInEngine } from './LinkedInEngine';

// Export factory (recommended way to create engines)
export { PlatformEngineFactory, createPlatformEngine } from './PlatformEngineFactory';
export type { SupportedPlatform } from './PlatformEngineFactory';
