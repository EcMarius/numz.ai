/**
 * Platform Engine Factory
 *
 * Factory pattern for creating platform-specific engines
 */

import type { BasePlatformEngine } from './BasePlatformEngine';
import { LinkedInEngine } from './LinkedInEngine';

export type SupportedPlatform = 'linkedin' | 'reddit' | 'x' | 'facebook';

export class PlatformEngineFactory {
  /**
   * Create a platform engine instance
   *
   * @param platform - Platform name (linkedin, reddit, x, facebook)
   * @returns Platform engine instance
   * @throws Error if platform is not supported
   */
  static create(platform: SupportedPlatform): BasePlatformEngine {
    switch (platform.toLowerCase()) {
      case 'linkedin':
        return new LinkedInEngine();

      case 'reddit':
        // TODO: Implement RedditEngine
        throw new Error('Reddit engine not implemented yet');

      case 'x':
      case 'twitter':
        // TODO: Implement XEngine
        throw new Error('X/Twitter engine not implemented yet');

      case 'facebook':
        // TODO: Implement FacebookEngine
        throw new Error('Facebook engine not implemented yet');

      default:
        throw new Error(`Unsupported platform: ${platform}`);
    }
  }

  /**
   * Get list of supported platforms
   */
  static getSupportedPlatforms(): SupportedPlatform[] {
    return ['linkedin', 'reddit', 'x', 'facebook'];
  }

  /**
   * Check if platform is supported
   */
  static isSupported(platform: string): boolean {
    return this.getSupportedPlatforms().includes(platform.toLowerCase() as SupportedPlatform);
  }
}

// Export convenience function
export function createPlatformEngine(platform: SupportedPlatform): BasePlatformEngine {
  return PlatformEngineFactory.create(platform);
}

export default PlatformEngineFactory;
