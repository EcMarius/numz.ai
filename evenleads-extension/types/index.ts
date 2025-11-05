export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  role_id?: number;
  roles?: string[]; // Array of role names (e.g., ['admin', 'editor'])
}

export interface Plan {
  id: number;
  name: string;
  features: string[];
  campaigns_limit: number;
  leads_per_sync: number;
  manual_syncs_limit: number;
  ai_replies_limit: number;
  crm_contacts_limit: number;
  leads_limit: number;
  active: boolean;
}

export interface Subscription {
  id: number;
  user_id: number;
  plan_id: number;
  status: string;
  trial_ends_at?: string;
  ends_at?: string;
  plan?: Plan;
  // Usage data
  used_campaigns: number;
  used_manual_syncs: number;
  used_ai_replies: number;
  used_leads: number;
  used_crm_contacts: number;
}

export interface AuthState {
  isAuthenticated: boolean;
  user: User | null;
  token: string | null;
  subscription: Subscription | null;
}

export interface Campaign {
  id: number;
  name: string;
  platforms: string[];
  keywords: string[];
  facebook_groups?: string[];
  reddit_subreddits?: string[];
  linkedin_groups?: string[];
  twitter_communities?: string[];
  status: string;
}

export interface Lead {
  platform: string;
  platform_id: string;
  title: string;
  description: string;
  url: string;
  author: string;
  matched_keywords: string[];
  confidence_score: number;
  // Platform-specific fields
  facebook_group?: string;
  subreddit?: string;
  linkedin_group?: string;
  twitter_community?: string;
  fiverr_gig_id?: string;
  upwork_job_id?: string;
}

export type Platform =
  // Social Media
  | 'facebook' | 'linkedin' | 'twitter' | 'instagram' | 'tiktok' | 'pinterest'
  | 'snapchat' | 'youtube' | 'twitch' | 'discord' | 'telegram' | 'whatsapp'
  // Professional Networks
  | 'github' | 'gitlab' | 'stackoverflow' | 'dev_to' | 'hashnode' | 'medium'
  | 'behance' | 'dribbble'
  // Freelance & Jobs
  | 'upwork' | 'fiverr' | 'freelancer' | 'toptal' | 'guru' | 'designs_99'
  | 'peopleperhour' | 'flexjobs' | 'remote_co' | 'weworkremotely' | 'angel_list'
  | 'indeed' | 'glassdoor' | 'monster' | 'ziprecruiter'
  // Forums & Communities
  | 'reddit' | 'hackernews' | 'quora' | 'producthunt' | 'indiehackers'
  | 'betalist' | 'crunchbase' | 'f6s'
  // Marketplaces
  | 'amazon' | 'etsy' | 'ebay' | 'alibaba' | 'shopify'
  // Business & Reviews
  | 'yelp' | 'google_business' | 'trustpilot' | 'clutch' | 'g2' | 'capterra'
  | 'bbb' | 'angies_list'
  // Developer Communities
  | 'replit' | 'codepen' | 'codesandbox' | 'glitch' | 'hackster_io' | 'hackerrank'
  // Content Platforms
  | 'substack' | 'ghost' | 'wordpress' | 'blogger'
  // Event & Crowdfunding
  | 'meetup' | 'eventbrite' | 'kickstarter' | 'patreon' | 'gofundme'
  // Sales & CRM
  | 'apollo_io' | 'hunter_io' | 'lusha' | 'zoominfo' | 'salesforce'
  // Other
  | 'slack_communities' | 'other';

export interface PlatformConfig {
  name: Platform;
  displayName: string;
  color: string;
  domains: string[];
  requiresManualSubmission?: boolean;
}

export const PLATFORMS: Record<Platform, PlatformConfig> = {
  facebook: {
    name: 'facebook',
    displayName: 'Facebook',
    color: '#1877F2',
    domains: ['facebook.com', 'fb.com'],
    requiresManualSubmission: true, // Can't scrape Facebook due to TOS
  },
  linkedin: {
    name: 'linkedin',
    displayName: 'LinkedIn',
    color: '#0A66C2',
    domains: ['linkedin.com'],
  },
  reddit: {
    name: 'reddit',
    displayName: 'Reddit',
    color: '#FF4500',
    domains: ['reddit.com'],
  },
  fiverr: {
    name: 'fiverr',
    displayName: 'Fiverr',
    color: '#1DBF73',
    domains: ['fiverr.com'],
  },
  upwork: {
    name: 'upwork',
    displayName: 'Upwork',
    color: '#14A800',
    domains: ['upwork.com'],
  },
};

// DEV Mode types
export type ElementType =
  // Wrappers
  | 'post_wrapper'
  | 'person_wrapper'
  | 'group_wrapper'
  | 'comment_wrapper'
  | 'message_wrapper'
  | 'conversation_wrapper'
  | 'list_wrapper'
  | 'form_wrapper'
  | 'modal_wrapper'
  // Post elements
  | 'post_title'
  | 'post_description'
  | 'post_content'
  | 'post_url'
  | 'post_image'
  // Person elements
  | 'person_name'
  | 'person_headline'
  | 'person_bio'
  | 'person_url'
  | 'person_avatar'
  | 'person_location'
  | 'person_company'
  | 'person_job_title'
  | 'person_email'
  | 'person_phone'
  | 'person_website'
  | 'person_social_links'
  | 'person_connections_count'
  // Group elements
  | 'group_name'
  | 'group_description'
  | 'group_url'
  | 'group_member_count'
  | 'group_category'
  // Meta elements
  | 'author_name'
  | 'author_url'
  | 'author_avatar'
  | 'timestamp'
  // Engagement metrics
  | 'like_count'
  | 'comment_count'
  | 'share_count'
  | 'view_count'
  | 'follower_count'
  | 'connection_count'
  // Comment elements
  | 'comment_text'
  | 'comment_author'
  | 'comment_time'
  | 'comment_reply_button'
  | 'comment_like_button'
  // Messaging & Chat elements
  | 'message_input'
  | 'message_send_button'
  | 'message_text'
  | 'message_author'
  | 'message_timestamp'
  | 'message_attachment_button'
  | 'message_area'
  | 'conversation_title'
  | 'conversation_participants'
  | 'unread_indicator'
  | 'typing_indicator'
  // Form input elements
  | 'text_input'
  | 'email_input'
  | 'password_input'
  | 'textarea'
  | 'dropdown_select'
  | 'checkbox'
  | 'radio_button'
  | 'file_upload_input'
  | 'date_picker'
  | 'time_picker'
  | 'number_input'
  | 'url_input'
  | 'phone_input'
  | 'search_input'
  // Button elements
  | 'submit_button'
  | 'cancel_button'
  | 'like_button'
  | 'share_button'
  | 'reply_button'
  | 'edit_button'
  | 'delete_button'
  | 'save_button'
  | 'follow_button'
  | 'unfollow_button'
  | 'connect_request_button'
  | 'accept_button'
  | 'decline_button'
  | 'more_options_button'
  | 'search_button'
  | 'next_page_button'
  | 'load_more_button'
  | 'back_button'
  | 'close_button'
  | 'refresh_button'
  | 'download_button'
  | 'upload_button'
  // Navigation elements
  | 'nav_menu'
  | 'nav_menu_item'
  | 'breadcrumb'
  | 'tab_button'
  | 'home_button'
  | 'profile_menu_button'
  | 'notifications_button'
  | 'messages_button'
  | 'settings_button'
  | 'logout_button'
  | 'login_button'
  | 'signup_button'
  // Content display
  | 'image'
  | 'video'
  | 'video_play_button'
  | 'video_pause_button'
  | 'link'
  | 'tag'
  | 'badge'
  | 'status_indicator'
  | 'tooltip'
  | 'icon'
  | 'emoji_reaction'
  // Lists & tables
  | 'list_item'
  | 'table'
  | 'table_row'
  | 'table_cell'
  | 'table_header'
  | 'grid_item'
  // Modals & overlays
  | 'modal_close_button'
  | 'modal_title'
  | 'modal_content'
  | 'overlay_backdrop'
  | 'dropdown_menu'
  | 'dropdown_menu_item'
  | 'popup'
  | 'notification_banner'
  // Form validation
  | 'form_error_message'
  | 'form_success_message'
  | 'loading_indicator'
  | 'progress_bar';

export type PageType = 'general' | 'search_list' | 'post_page' | 'profile' | 'group' | 'person_feed' | 'feed_page';

export interface SchemaElement {
  name?: string; // Human-readable name (e.g., "Main Search Bar", "Send Message Button")
  page_type?: PageType; // Which page this element belongs to (general, search_list, etc.)
  element_type: ElementType;
  css_selector: string;
  xpath_selector: string;
  is_required: boolean;
  fallback_value?: string;
  parent_element?: string; // e.g., "post_wrapper" - selector is relative to this parent
  multiple: boolean;
  description?: string;
  is_wrapper?: boolean; // True for post_wrapper, person_wrapper, etc.
  relative_to_wrapper?: boolean; // If true, selector is relative (starts with . or descendant)

  // Wait configuration for automation
  wait_for?: {
    type: 'element' | 'time';
    // If type = 'element': wait for another element to appear
    element_selector?: string;           // CSS or XPath selector
    element_selector_type?: 'css' | 'xpath';
    timeout_ms?: number;                 // Max wait time (default 10000ms)
    // If type = 'time': wait for duration
    duration_ms?: number;                // How long to wait in milliseconds
  };
}

export interface PlatformSchema {
  platform: string;
  page_type: PageType;
  version: string;
  elements: SchemaElement[];
}

export interface DevModeState {
  enabled: boolean;
  selectedElement: Element | null;
  schemaElements: SchemaElement[];
  currentPlatform: Platform | null;
  currentPageType: PageType | null;
}
