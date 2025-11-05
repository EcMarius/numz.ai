/**
 * Page Detection Utility
 * Detects the current platform and page type
 */

export type Platform = 'linkedin' | 'reddit' | 'facebook' | 'x' | 'fiverr' | 'upwork' | '';
export type PageType = 'search_list' | 'post_page' | 'profile' | 'group' | 'person_feed' | null;

export interface PageInfo {
  platform: Platform;
  type: PageType;
  url: string;
}

/**
 * Detect the current page type and platform
 */
export function detectPageType(): PageInfo {
  const url = window.location.href;
  const hostname = window.location.hostname;

  // LinkedIn Detection
  if (hostname.includes('linkedin.com')) {
    // Groups detection (check first, most specific)
    if (url.includes('/groups/')) {
      return { platform: 'linkedin', type: 'group', url };
    }
    // Profile detection
    if (url.includes('/in/') && !url.includes('/posts/')) {
      return { platform: 'linkedin', type: 'profile', url };
    }
    // Single post page
    if (url.match(/\/posts?\//) || url.includes('/feed/update/')) {
      return { platform: 'linkedin', type: 'post_page', url };
    }
    // Search results or feed
    if (url.includes('/search/results/') || url.includes('/feed/')) {
      return { platform: 'linkedin', type: 'search_list', url };
    }
  }

  // Reddit Detection
  if (hostname.includes('reddit.com')) {
    // Single post page
    if (url.match(/\/r\/[\w-]+\/comments\/[\w-]+/)) {
      return { platform: 'reddit', type: 'post_page', url };
    }
    // User profile
    if (url.match(/\/user\/[\w-]+\/?$/) || url.match(/\/u\/[\w-]+\/?$/)) {
      return { platform: 'reddit', type: 'profile', url };
    }
    // Subreddit/search list (multiple posts)
    if (url.match(/\/r\/[\w-]+/) || url.includes('/search/')) {
      return { platform: 'reddit', type: 'search_list', url };
    }
  }

  // Facebook Detection
  if (hostname.includes('facebook.com')) {
    // Group detection (check first)
    if (url.includes('/groups/')) {
      return { platform: 'facebook', type: 'group', url };
    }
    // Single post page
    if (
      url.includes('/posts/') ||
      url.includes('/permalink/') ||
      url.match(/\/photo\.php/) ||
      url.match(/\/story\.php/) ||
      url.match(/fbid=\d+/)
    ) {
      return { platform: 'facebook', type: 'post_page', url };
    }
    // Profile page
    if (
      url.match(/facebook\.com\/profile\.php\?id=\d+/) ||
      url.match(/facebook\.com\/[\w.]+\/?$/) ||
      url.match(/facebook\.com\/people\/[\w-]+/)
    ) {
      return { platform: 'facebook', type: 'profile', url };
    }
    // Search results or feed
    if (url.includes('/search/') || url.includes('?sk=h_chr')) {
      return { platform: 'facebook', type: 'search_list', url };
    }
  }

  // X (Twitter) Detection
  if (hostname.includes('x.com') || hostname.includes('twitter.com')) {
    // Single post/tweet page
    if (url.match(/\/([\w]+)\/status\/(\d+)/)) {
      return { platform: 'x', type: 'post_page', url };
    }
    // Profile page
    if (url.match(/\/([\w]+)\/?$/) && !url.includes('/status/') && !url.includes('/search')) {
      return { platform: 'x', type: 'profile', url };
    }
    // Search results or home feed
    if (url.includes('/search') || url.includes('/home')) {
      return { platform: 'x', type: 'search_list', url };
    }
  }

  // Fiverr Detection
  if (hostname.includes('fiverr.com')) {
    // Search results
    if (url.includes('/search/gigs') || url.includes('/categories/')) {
      return { platform: 'fiverr', type: 'search_list', url };
    }
    // Single gig page
    if (url.match(/\/gigs?\/[\w-]+/)) {
      return { platform: 'fiverr', type: 'post_page', url };
    }
    // Profile page
    if (url.match(/\/[\w-]+\/?$/) && !url.includes('/gig')) {
      return { platform: 'fiverr', type: 'profile', url };
    }
  }

  // Upwork Detection
  if (hostname.includes('upwork.com')) {
    // Search results
    if (url.includes('/nx/search/jobs') || url.includes('/jobs/search/')) {
      return { platform: 'upwork', type: 'search_list', url };
    }
    // Single job page
    if (url.match(/\/jobs\/[\w-]+/)) {
      return { platform: 'upwork', type: 'post_page', url };
    }
    // Profile page
    if (url.includes('/freelancers/') || url.match(/\/~[\w]+/)) {
      return { platform: 'upwork', type: 'profile', url };
    }
  }

  return { platform: '', type: null, url };
}

/**
 * Detect just the current platform
 */
export function detectCurrentPlatform(): Platform {
  const { platform } = detectPageType();
  return platform;
}

/**
 * Check if current page is a supported platform
 */
export function isSupportedPage(): boolean {
  const { platform, type } = detectPageType();
  return platform !== '' && type !== null;
}

/**
 * Get platform icon color
 */
export function getPlatformColor(platform: Platform): string {
  const colors: Record<Platform, string> = {
    linkedin: '#0A66C2',
    reddit: '#FF4500',
    facebook: '#1877F2',
    x: '#000000',
    fiverr: '#1DBF73',
    upwork: '#14A800',
    '': '#6B7280',
  };
  return colors[platform] || '#6B7280';
}

/**
 * Get page type display name
 */
export function getPageTypeLabel(type: PageType): string {
  if (!type) return '';

  const labels: Record<string, string> = {
    search_list: 'Search List',
    post_page: 'Post Page',
    profile: 'Profile',
    group: 'Group',
    person_feed: 'Person Feed',
  };

  return labels[type] || '';
}

/**
 * Extract platform ID from URL
 */
export function extractPlatformId(url: string, platform: Platform, type: PageType): string {
  try {
    switch (platform) {
      case 'linkedin':
        if (type === 'profile') {
          const profileMatch = url.match(/\/in\/([\w-]+)/);
          return profileMatch ? profileMatch[1] : '';
        }
        if (type === 'post_page') {
          const postMatch = url.match(/activity-(\d+)/);
          return postMatch ? postMatch[1] : '';
        }
        if (type === 'group') {
          const groupMatch = url.match(/\/groups\/([\w-]+)/);
          return groupMatch ? groupMatch[1] : '';
        }
        if (type === 'search_list' || type === 'person_feed') {
          return 'feed_' + Date.now();
        }
        break;

      case 'reddit':
        if (type === 'profile') {
          const userMatch = url.match(/\/u(?:ser)?\/([\w-]+)/);
          return userMatch ? userMatch[1] : '';
        }
        if (type === 'post_page') {
          const postMatch = url.match(/\/comments\/([\w-]+)/);
          return postMatch ? postMatch[1] : '';
        }
        if (type === 'search_list' || type === 'group') {
          const subMatch = url.match(/\/r\/([\w-]+)/);
          return subMatch ? subMatch[1] : 'search_' + Date.now();
        }
        break;

      case 'facebook':
        if (type === 'profile') {
          const idMatch = url.match(/id=(\d+)/);
          if (idMatch) return idMatch[1];
          const usernameMatch = url.match(/facebook\.com\/([\w.]+)/);
          return usernameMatch ? usernameMatch[1] : '';
        }
        if (type === 'post_page') {
          const postIdMatch = url.match(/\/posts\/(\d+)|fbid=(\d+)|\/permalink\/(\d+)/);
          if (postIdMatch) {
            return postIdMatch[1] || postIdMatch[2] || postIdMatch[3] || '';
          }
        }
        if (type === 'group') {
          const groupMatch = url.match(/\/groups\/([\w-]+)/);
          return groupMatch ? groupMatch[1] : '';
        }
        if (type === 'search_list') {
          return 'search_' + Date.now();
        }
        break;

      case 'x':
        if (type === 'post_page') {
          const tweetMatch = url.match(/\/status\/(\d+)/);
          return tweetMatch ? tweetMatch[1] : '';
        }
        if (type === 'profile') {
          const userMatch = url.match(/x\.com\/([\w]+)/);
          return userMatch ? userMatch[1] : '';
        }
        if (type === 'search_list') {
          return 'search_' + Date.now();
        }
        break;

      case 'fiverr':
        const gigMatch = url.match(/\/gigs?\/([\w-]+)/);
        return gigMatch ? gigMatch[1] : '';

      case 'upwork':
        const jobMatch = url.match(/\/jobs?\/([\w-]+)/);
        return jobMatch ? jobMatch[1] : '';
    }
  } catch (error) {
    console.error('Error extracting platform ID:', error);
  }

  return `manual_${Date.now()}`;
}
