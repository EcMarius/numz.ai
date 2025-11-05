import React, { useState, useEffect, useCallback } from 'react';
import { Check, X, Copy, AlertCircle, Trash2 } from 'lucide-react';
import type { SelectorResult } from '../../utils/selectorGenerator';
import type { ElementType, SchemaElement } from '../../types';
import { testCSSSelector, testXPath } from '../../utils/selectorGenerator';

interface SchemaBuilderProps {
  selectedElement: Element | null;
  selectors: SelectorResult | null;
  schemaElements: SchemaElement[]; // All current schema elements (for detecting parent wrappers)
  onAddElement: (element: SchemaElement) => void;
  onCancel: () => void;
  onSelectAnother: () => void; // Return to inspect mode
  existingElement?: SchemaElement; // For editing
}

const ELEMENT_TYPES: { value: ElementType; label: string; category: string }[] = [
  // Wrappers
  { value: 'post_wrapper', label: 'Post Wrapper', category: 'Wrappers' },
  { value: 'person_wrapper', label: 'Person Wrapper', category: 'Wrappers' },
  { value: 'group_wrapper', label: 'Group Wrapper', category: 'Wrappers' },
  { value: 'comment_wrapper', label: 'Comment Wrapper', category: 'Wrappers' },
  { value: 'message_wrapper', label: 'Message Wrapper', category: 'Wrappers' },
  { value: 'conversation_wrapper', label: 'Conversation Wrapper', category: 'Wrappers' },
  { value: 'list_wrapper', label: 'List Wrapper', category: 'Wrappers' },
  { value: 'form_wrapper', label: 'Form Wrapper', category: 'Wrappers' },
  { value: 'modal_wrapper', label: 'Modal Wrapper', category: 'Wrappers' },
  // Post Content
  { value: 'post_title', label: 'Post Title', category: 'Post Content' },
  { value: 'post_description', label: 'Post Description', category: 'Post Content' },
  { value: 'post_content', label: 'Post Content', category: 'Post Content' },
  { value: 'post_url', label: 'Post URL', category: 'Post Content' },
  { value: 'post_image', label: 'Post Image', category: 'Post Content' },
  // Person/Profile
  { value: 'person_name', label: 'Person Name', category: 'Person/Profile' },
  { value: 'person_headline', label: 'Person Headline', category: 'Person/Profile' },
  { value: 'person_bio', label: 'Person Bio', category: 'Person/Profile' },
  { value: 'person_url', label: 'Person URL', category: 'Person/Profile' },
  { value: 'person_avatar', label: 'Person Avatar', category: 'Person/Profile' },
  { value: 'person_location', label: 'Person Location', category: 'Person/Profile' },
  { value: 'person_company', label: 'Person Company', category: 'Person/Profile' },
  { value: 'person_job_title', label: 'Person Job Title', category: 'Person/Profile' },
  { value: 'person_email', label: 'Person Email', category: 'Person/Profile' },
  { value: 'person_phone', label: 'Person Phone', category: 'Person/Profile' },
  { value: 'person_website', label: 'Person Website', category: 'Person/Profile' },
  { value: 'person_social_links', label: 'Person Social Links', category: 'Person/Profile' },
  { value: 'person_connections_count', label: 'Connections Count', category: 'Person/Profile' },
  // Groups
  { value: 'group_name', label: 'Group Name', category: 'Groups' },
  { value: 'group_description', label: 'Group Description', category: 'Groups' },
  { value: 'group_url', label: 'Group URL', category: 'Groups' },
  { value: 'group_member_count', label: 'Group Member Count', category: 'Groups' },
  { value: 'group_category', label: 'Group Category', category: 'Groups' },
  // Meta & Authors
  { value: 'author_name', label: 'Author Name', category: 'Meta & Authors' },
  { value: 'author_url', label: 'Author URL', category: 'Meta & Authors' },
  { value: 'author_avatar', label: 'Author Avatar', category: 'Meta & Authors' },
  { value: 'timestamp', label: 'Timestamp', category: 'Meta & Authors' },
  // Engagement
  { value: 'like_count', label: 'Like Count', category: 'Engagement' },
  { value: 'comment_count', label: 'Comment Count', category: 'Engagement' },
  { value: 'share_count', label: 'Share Count', category: 'Engagement' },
  { value: 'view_count', label: 'View Count', category: 'Engagement' },
  { value: 'follower_count', label: 'Follower Count', category: 'Engagement' },
  { value: 'connection_count', label: 'Connection Count', category: 'Engagement' },
  // Comments
  { value: 'comment_text', label: 'Comment Text', category: 'Comments' },
  { value: 'comment_author', label: 'Comment Author', category: 'Comments' },
  { value: 'comment_time', label: 'Comment Time', category: 'Comments' },
  { value: 'comment_reply_button', label: 'Comment Reply Button', category: 'Comments' },
  { value: 'comment_like_button', label: 'Comment Like Button', category: 'Comments' },
  // Messaging & Chat
  { value: 'message_input', label: 'Message Input', category: 'Messaging & Chat' },
  { value: 'message_send_button', label: 'Message Send Button', category: 'Messaging & Chat' },
  { value: 'message_text', label: 'Message Text', category: 'Messaging & Chat' },
  { value: 'message_author', label: 'Message Author', category: 'Messaging & Chat' },
  { value: 'message_timestamp', label: 'Message Timestamp', category: 'Messaging & Chat' },
  { value: 'message_attachment_button', label: 'Attachment Button', category: 'Messaging & Chat' },
  { value: 'message_area', label: 'Message Area', category: 'Messaging & Chat' },
  { value: 'conversation_title', label: 'Conversation Title', category: 'Messaging & Chat' },
  { value: 'conversation_participants', label: 'Participants', category: 'Messaging & Chat' },
  { value: 'unread_indicator', label: 'Unread Indicator', category: 'Messaging & Chat' },
  { value: 'typing_indicator', label: 'Typing Indicator', category: 'Messaging & Chat' },
  // Form Inputs
  { value: 'text_input', label: 'Text Input', category: 'Form Inputs' },
  { value: 'email_input', label: 'Email Input', category: 'Form Inputs' },
  { value: 'password_input', label: 'Password Input', category: 'Form Inputs' },
  { value: 'textarea', label: 'Text Area', category: 'Form Inputs' },
  { value: 'dropdown_select', label: 'Dropdown Select', category: 'Form Inputs' },
  { value: 'checkbox', label: 'Checkbox', category: 'Form Inputs' },
  { value: 'radio_button', label: 'Radio Button', category: 'Form Inputs' },
  { value: 'file_upload_input', label: 'File Upload', category: 'Form Inputs' },
  { value: 'date_picker', label: 'Date Picker', category: 'Form Inputs' },
  { value: 'time_picker', label: 'Time Picker', category: 'Form Inputs' },
  { value: 'number_input', label: 'Number Input', category: 'Form Inputs' },
  { value: 'url_input', label: 'URL Input', category: 'Form Inputs' },
  { value: 'phone_input', label: 'Phone Input', category: 'Form Inputs' },
  { value: 'search_input', label: 'Search Input', category: 'Form Inputs' },
  // Action Buttons
  { value: 'submit_button', label: 'Submit Button', category: 'Action Buttons' },
  { value: 'cancel_button', label: 'Cancel Button', category: 'Action Buttons' },
  { value: 'like_button', label: 'Like Button', category: 'Action Buttons' },
  { value: 'share_button', label: 'Share Button', category: 'Action Buttons' },
  { value: 'reply_button', label: 'Reply Button', category: 'Action Buttons' },
  { value: 'edit_button', label: 'Edit Button', category: 'Action Buttons' },
  { value: 'delete_button', label: 'Delete Button', category: 'Action Buttons' },
  { value: 'save_button', label: 'Save Button', category: 'Action Buttons' },
  { value: 'follow_button', label: 'Follow Button', category: 'Action Buttons' },
  { value: 'unfollow_button', label: 'Unfollow Button', category: 'Action Buttons' },
  { value: 'connect_request_button', label: 'Connect Request Button', category: 'Action Buttons' },
  { value: 'accept_button', label: 'Accept Button', category: 'Action Buttons' },
  { value: 'decline_button', label: 'Decline Button', category: 'Action Buttons' },
  { value: 'more_options_button', label: 'More Options Button', category: 'Action Buttons' },
  { value: 'search_button', label: 'Search Button', category: 'Action Buttons' },
  { value: 'next_page_button', label: 'Next Page Button', category: 'Action Buttons' },
  { value: 'load_more_button', label: 'Load More Button', category: 'Action Buttons' },
  { value: 'back_button', label: 'Back Button', category: 'Action Buttons' },
  { value: 'close_button', label: 'Close Button', category: 'Action Buttons' },
  { value: 'refresh_button', label: 'Refresh Button', category: 'Action Buttons' },
  { value: 'download_button', label: 'Download Button', category: 'Action Buttons' },
  { value: 'upload_button', label: 'Upload Button', category: 'Action Buttons' },
  // Navigation
  { value: 'nav_menu', label: 'Navigation Menu', category: 'Navigation' },
  { value: 'nav_menu_item', label: 'Nav Menu Item', category: 'Navigation' },
  { value: 'breadcrumb', label: 'Breadcrumb', category: 'Navigation' },
  { value: 'tab_button', label: 'Tab Button', category: 'Navigation' },
  { value: 'home_button', label: 'Home Button', category: 'Navigation' },
  { value: 'profile_menu_button', label: 'Profile Menu Button', category: 'Navigation' },
  { value: 'notifications_button', label: 'Notifications Button', category: 'Navigation' },
  { value: 'messages_button', label: 'Messages Button', category: 'Navigation' },
  { value: 'settings_button', label: 'Settings Button', category: 'Navigation' },
  { value: 'logout_button', label: 'Logout Button', category: 'Navigation' },
  { value: 'login_button', label: 'Login Button', category: 'Navigation' },
  { value: 'signup_button', label: 'Signup Button', category: 'Navigation' },
  // Content Display
  { value: 'image', label: 'Image', category: 'Content Display' },
  { value: 'video', label: 'Video', category: 'Content Display' },
  { value: 'video_play_button', label: 'Video Play Button', category: 'Content Display' },
  { value: 'video_pause_button', label: 'Video Pause Button', category: 'Content Display' },
  { value: 'link', label: 'Link', category: 'Content Display' },
  { value: 'tag', label: 'Tag', category: 'Content Display' },
  { value: 'badge', label: 'Badge', category: 'Content Display' },
  { value: 'status_indicator', label: 'Status Indicator', category: 'Content Display' },
  { value: 'tooltip', label: 'Tooltip', category: 'Content Display' },
  { value: 'icon', label: 'Icon', category: 'Content Display' },
  { value: 'emoji_reaction', label: 'Emoji Reaction', category: 'Content Display' },
  // Lists & Tables
  { value: 'list_item', label: 'List Item', category: 'Lists & Tables' },
  { value: 'table', label: 'Table', category: 'Lists & Tables' },
  { value: 'table_row', label: 'Table Row', category: 'Lists & Tables' },
  { value: 'table_cell', label: 'Table Cell', category: 'Lists & Tables' },
  { value: 'table_header', label: 'Table Header', category: 'Lists & Tables' },
  { value: 'grid_item', label: 'Grid Item', category: 'Lists & Tables' },
  // Modals & Overlays
  { value: 'modal_close_button', label: 'Modal Close Button', category: 'Modals & Overlays' },
  { value: 'modal_title', label: 'Modal Title', category: 'Modals & Overlays' },
  { value: 'modal_content', label: 'Modal Content', category: 'Modals & Overlays' },
  { value: 'overlay_backdrop', label: 'Overlay Backdrop', category: 'Modals & Overlays' },
  { value: 'dropdown_menu', label: 'Dropdown Menu', category: 'Modals & Overlays' },
  { value: 'dropdown_menu_item', label: 'Dropdown Menu Item', category: 'Modals & Overlays' },
  { value: 'popup', label: 'Popup', category: 'Modals & Overlays' },
  { value: 'notification_banner', label: 'Notification Banner', category: 'Modals & Overlays' },
  // Form Messages
  { value: 'form_error_message', label: 'Form Error Message', category: 'Form Messages' },
  { value: 'form_success_message', label: 'Form Success Message', category: 'Form Messages' },
  { value: 'loading_indicator', label: 'Loading Indicator', category: 'Form Messages' },
  { value: 'progress_bar', label: 'Progress Bar', category: 'Form Messages' },
];

const ELEMENT_DESCRIPTIONS: Record<ElementType, string> = {
  // Wrappers
  'post_wrapper': 'Container element that wraps each individual post in a list',
  'person_wrapper': 'Container element that wraps each person/profile in a list',
  'group_wrapper': 'Container element that wraps each group in a list',
  'comment_wrapper': 'Container element that wraps each comment',
  'message_wrapper': 'Container for each individual message in a chat',
  'conversation_wrapper': 'Container for each conversation/thread in a list',
  'list_wrapper': 'Generic list container element',
  'form_wrapper': 'Form container element',
  'modal_wrapper': 'Modal/dialog container element',
  // Post elements
  'post_title': 'The main title/heading of a post',
  'post_description': 'Short description or preview text of a post',
  'post_content': 'Main body content of a post',
  'post_url': 'Link to the full post or content',
  'post_image': 'Main image of a post',
  // Person elements
  'person_name': 'Full name of a person/user',
  'person_headline': 'Job title, tagline, or short bio',
  'person_bio': 'Full biography or description',
  'person_url': 'Link to person profile',
  'person_avatar': 'Profile picture or avatar image',
  'person_location': 'Location/city of the person',
  'person_company': 'Company name where person works',
  'person_job_title': 'Current job title',
  'person_email': 'Email address',
  'person_phone': 'Phone number',
  'person_website': 'Personal website URL',
  'person_social_links': 'Social media links',
  'person_connections_count': 'Number of connections/followers',
  // Group elements
  'group_name': 'Name of the group',
  'group_description': 'Description of the group',
  'group_url': 'Link to the group page',
  'group_member_count': 'Number of group members',
  'group_category': 'Group category or type',
  // Meta elements
  'author_name': 'Name of the post/content author',
  'author_url': 'Link to author profile',
  'author_avatar': 'Author profile picture',
  'timestamp': 'Date/time when content was posted',
  // Engagement
  'like_count': 'Number of likes/reactions',
  'comment_count': 'Number of comments',
  'share_count': 'Number of shares/reposts',
  'view_count': 'Number of views',
  'follower_count': 'Number of followers',
  'connection_count': 'Number of connections',
  // Comment elements
  'comment_text': 'Text content of a comment',
  'comment_author': 'Name of comment author',
  'comment_time': 'Time when comment was posted',
  'comment_reply_button': 'Button to reply to a comment',
  'comment_like_button': 'Button to like/react to a comment',
  // Messaging & Chat
  'message_input': 'Text input field for composing messages',
  'message_send_button': 'Button to send a message',
  'message_text': 'Text content of a message',
  'message_author': 'Name of message sender',
  'message_timestamp': 'Time when message was sent',
  'message_attachment_button': 'Button to attach files to message',
  'message_area': 'Main chat/conversation area container',
  'conversation_title': 'Title/name of a conversation',
  'conversation_participants': 'List of conversation participants',
  'unread_indicator': 'Badge showing unread message count',
  'typing_indicator': '"User is typing..." indicator element',
  // Form Inputs
  'text_input': 'Generic text input field',
  'email_input': 'Email address input field',
  'password_input': 'Password input field',
  'textarea': 'Multi-line text area input',
  'dropdown_select': 'Dropdown selection menu',
  'checkbox': 'Checkbox input element',
  'radio_button': 'Radio button input element',
  'file_upload_input': 'File upload/browse button',
  'date_picker': 'Date selection calendar input',
  'time_picker': 'Time selection input',
  'number_input': 'Numeric input field',
  'url_input': 'URL/website input field',
  'phone_input': 'Phone number input field',
  'search_input': '🌐 GENERAL: Main search input field used across all pages',
  // Action Buttons
  'submit_button': 'Form submit button',
  'cancel_button': 'Cancel or close button',
  'like_button': 'Like or reaction button (clickable)',
  'share_button': 'Share or repost button (clickable)',
  'reply_button': 'Reply to post/comment button (clickable)',
  'edit_button': 'Edit content button (clickable)',
  'delete_button': 'Delete or remove button (clickable)',
  'save_button': 'Save or bookmark button (clickable)',
  'follow_button': 'Follow user/page button (clickable)',
  'unfollow_button': 'Unfollow button (clickable)',
  'connect_request_button': 'Send connection request button (clickable)',
  'accept_button': 'Accept request/invitation button (clickable)',
  'decline_button': 'Decline or reject button (clickable)',
  'more_options_button': '"..." or kebab menu button (clickable)',
  'search_button': '🌐 GENERAL: Search submit button',
  'next_page_button': '🌐 GENERAL: Pagination "Next" button for navigating to next page (clickable)',
  'load_more_button': '🌐 GENERAL: "Load More" button for infinite scroll (clickable)',
  'back_button': 'Go back/return button (clickable)',
  'close_button': 'Close window/modal button (clickable)',
  'refresh_button': 'Refresh or reload button (clickable)',
  'download_button': 'Download file button (clickable)',
  'upload_button': 'Upload file button (clickable)',
  // Navigation
  'nav_menu': '🌐 GENERAL: Main navigation menu container',
  'nav_menu_item': '🌐 GENERAL: Individual navigation menu item (clickable)',
  'breadcrumb': '🌐 GENERAL: Breadcrumb navigation element',
  'tab_button': 'Tab switcher button (clickable)',
  'home_button': '🌐 GENERAL: Home page or logo button (clickable)',
  'profile_menu_button': '🌐 GENERAL: User profile dropdown button (clickable)',
  'notifications_button': '🌐 GENERAL: Notifications bell button (clickable)',
  'messages_button': '🌐 GENERAL: Messages/inbox button (clickable)',
  'settings_button': '🌐 GENERAL: Settings/preferences button (clickable)',
  'logout_button': '🌐 GENERAL: Logout button (clickable)',
  'login_button': '🌐 GENERAL: Login button (clickable)',
  'signup_button': '🌐 GENERAL: Sign up/register button (clickable)',
  // Content Display
  'image': 'Image element',
  'video': 'Video player element',
  'video_play_button': 'Play video button (clickable)',
  'video_pause_button': 'Pause video button (clickable)',
  'link': 'Hyperlink element (clickable)',
  'tag': 'Tag or label element',
  'badge': 'Badge or chip element',
  'status_indicator': 'Status dot (online/offline/away)',
  'tooltip': 'Tooltip or popover element',
  'icon': 'Icon or SVG element',
  'emoji_reaction': 'Emoji reaction button (clickable)',
  // Lists & Tables
  'list_item': 'Individual item in a list',
  'table': 'Table container element',
  'table_row': 'Table row element',
  'table_cell': 'Table cell data element',
  'table_header': 'Table header cell',
  'grid_item': 'Item in a grid layout',
  // Modals & Overlays
  'modal_close_button': 'Close modal X button (clickable)',
  'modal_title': 'Modal header/title text',
  'modal_content': 'Modal body content area',
  'overlay_backdrop': 'Dark overlay behind modal',
  'dropdown_menu': 'Dropdown menu container',
  'dropdown_menu_item': 'Option in dropdown menu (clickable)',
  'popup': 'Popup container element',
  'notification_banner': 'Notification banner element',
  // Form Messages
  'form_error_message': 'Form validation error message',
  'form_success_message': 'Form success confirmation message',
  'loading_indicator': 'Loading spinner or progress indicator',
  'progress_bar': 'Progress bar element',
};

export default function SchemaBuilder({ selectedElement, selectors, schemaElements, onAddElement, onCancel, onSelectAnother, existingElement }: SchemaBuilderProps) {
  const [elementName, setElementName] = useState('');
  const [elementType, setElementType] = useState<ElementType>('post_title');
  const [cssSelector, setCssSelector] = useState('');
  const [xpathSelector, setXpathSelector] = useState('');
  const [isRequired, setIsRequired] = useState(false);
  const [multiple, setMultiple] = useState(false);
  const [parentElement, setParentElement] = useState('');
  const [description, setDescription] = useState('');
  const [activeTab, setActiveTab] = useState<'css' | 'xpath'>('css');
  const [testResult, setTestResult] = useState<{ matchCount: number; success: boolean } | null>(null);
  const [activeHighlights, setActiveHighlights] = useState(false);
  const [autoDetectedWrapper, setAutoDetectedWrapper] = useState(false);
  const [showAttributes, setShowAttributes] = useState(false);

  // Load existing element data when editing
  useEffect(() => {
    if (existingElement) {
      setElementName(existingElement.name || '');
      setElementType(existingElement.element_type);
      setCssSelector(existingElement.css_selector);
      setXpathSelector(existingElement.xpath_selector);
      setIsRequired(existingElement.is_required);
      setMultiple(existingElement.multiple);
      setParentElement(existingElement.parent_element || '');
      setDescription(existingElement.description || '');
    }
  }, [existingElement]);

  useEffect(() => {
    if (selectors && !existingElement) {
      setCssSelector(selectors.cssSelector);
      setXpathSelector(selectors.xpathSelector);
    }
  }, [selectors, existingElement]);

  const handleTest = () => {
    if (activeTab === 'css') {
      const result = testCSSSelector(cssSelector);
      setTestResult({ matchCount: result.matchCount, success: result.success });

      // Highlight matched elements
      highlightMatchedElements(result.elements);
    } else {
      const result = testXPath(xpathSelector);
      setTestResult({ matchCount: result.matchCount, success: result.success });

      // Highlight matched elements
      highlightMatchedElements(result.elements);
    }
  };

  const highlightMatchedElements = (elements: Element[]) => {
    // Remove previous highlights
    document.querySelectorAll('.evenleads-test-highlight').forEach(el => el.remove());

    // Add highlights to matched elements
    elements.forEach((element, index) => {
      const rect = element.getBoundingClientRect();
      const highlight = document.createElement('div');
      highlight.className = 'evenleads-test-highlight';
      highlight.style.cssText = `
        position: fixed;
        top: ${rect.top}px;
        left: ${rect.left}px;
        width: ${rect.width}px;
        height: ${rect.height}px;
        border: 2px solid #10b981;
        background: rgba(16, 185, 129, 0.1);
        pointer-events: none;
        z-index: 999997;
      `;

      // Add number badge
      const badge = document.createElement('div');
      badge.style.cssText = `
        position: absolute;
        top: -10px;
        left: -10px;
        background: #10b981;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
      `;
      badge.textContent = (index + 1).toString();
      highlight.appendChild(badge);

      document.body.appendChild(highlight);
    });

    // Mark highlights as active (don't auto-remove)
    setActiveHighlights(true);
  };

  const handleClearHighlights = () => {
    document.querySelectorAll('.evenleads-test-highlight').forEach(el => el.remove());
    setActiveHighlights(false);
    setTestResult(null);
  };

  const handleTestClick = () => {
    // Test selector and get matched elements
    let elements: Element[] = [];
    if (activeTab === 'css') {
      const result = testCSSSelector(cssSelector);
      elements = result.elements;
      setTestResult({ matchCount: result.matchCount, success: result.success });
    } else {
      const result = testXPath(xpathSelector);
      elements = result.elements;
      setTestResult({ matchCount: result.matchCount, success: result.success });
    }

    // Highlight the elements
    highlightMatchedElements(elements);

    // Simulate click on matched elements
    if (elements.length > 0) {
      setTimeout(() => {
        const confirmMessage = elements.length === 1
          ? `Click this element? This will trigger its click handler.`
          : `Click all ${elements.length} matched elements? This will trigger their click handlers.`;

        if (confirm(confirmMessage)) {
          elements.forEach((el, index) => {
            setTimeout(() => {
              (el as HTMLElement).click();
            }, index * 100); // Stagger clicks by 100ms
          });
        }
      }, 100);
    } else {
      alert('No elements matched. Cannot test click.');
    }
  };

  const handleAdd = () => {
    const isWrapper = elementType.endsWith('_wrapper');

    const element: SchemaElement = {
      name: elementName || undefined,
      element_type: elementType,
      css_selector: cssSelector,
      xpath_selector: xpathSelector,
      is_required: isRequired,
      multiple,
      parent_element: isWrapper ? undefined : (parentElement || undefined),
      description: description || undefined,
      is_wrapper: isWrapper,
      relative_to_wrapper: !isWrapper && !!parentElement,
    };
    onAddElement(element);
  };

  const handleCopy = (text: string) => {
    navigator.clipboard.writeText(text);
  };

  // Add attribute to current selector
  const addAttributeToSelector = (attrName: string) => {
    if (!selectedElement) return;

    const attrValue = selectedElement.getAttribute(attrName);

    if (!attrValue) {
      alert(`Element doesn't have a "${attrName}" attribute`);
      return;
    }

    const tag = selectedElement.tagName.toLowerCase();

    if (activeTab === 'css') {
      // Generate CSS selector with attribute
      const newSelector = `${tag}[${attrName}="${CSS.escape(attrValue)}"]`;
      setCssSelector(newSelector);
    } else {
      // Generate XPath with attribute
      const newSelector = `//${tag}[@${attrName}="${attrValue}"]`;
      setXpathSelector(newSelector);
    }
  };

  // Auto-detect parent wrapper by checking if selectedElement is inside any wrapper
  const detectParentWrapper = useCallback((): string | null => {
    if (!selectedElement) return null;

    // Get all wrapper elements from schema
    const wrappers = schemaElements.filter(el => el.is_wrapper);

    // Check each wrapper to see if selected element is inside it
    for (const wrapper of wrappers) {
      try {
        // Try CSS selector first
        if (wrapper.css_selector) {
          const wrapperElements = document.querySelectorAll(wrapper.css_selector);
          for (const wrapperEl of Array.from(wrapperElements)) {
            if (wrapperEl.contains(selectedElement) && wrapperEl !== selectedElement) {
              return wrapper.element_type;
            }
          }
        }
      } catch (e) {
        // Try XPath if CSS fails
        try {
          if (wrapper.xpath_selector) {
            const result = document.evaluate(
              wrapper.xpath_selector,
              document,
              null,
              XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
              null
            );
            for (let i = 0; i < result.snapshotLength; i++) {
              const wrapperEl = result.snapshotItem(i);
              if (wrapperEl && wrapperEl.contains(selectedElement) && wrapperEl !== selectedElement) {
                return wrapper.element_type;
              }
            }
          }
        } catch (e2) {
          console.warn('Failed to test wrapper:', wrapper.element_type, e2);
        }
      }
    }

    return null;
  }, [selectedElement, schemaElements]);

  // Auto-select parent wrapper when element is selected
  useEffect(() => {
    if (selectedElement && !existingElement) {
      const detectedWrapper = detectParentWrapper();
      if (detectedWrapper) {
        setParentElement(detectedWrapper);
        setAutoDetectedWrapper(true); // Mark as auto-detected
      } else {
        setAutoDetectedWrapper(false);
      }
    }
  }, [selectedElement, detectParentWrapper, existingElement]);

  // Clear auto-detected flag when user manually changes parent
  const handleParentElementChange = (newParent: string) => {
    setParentElement(newParent);
    setAutoDetectedWrapper(false); // User manually changed it
  };

  if (!selectedElement || !selectors) return null;

  const elementPreview = selectedElement.textContent?.trim().substring(0, 100) || '';

  return (
    <div style={{
      backgroundColor: '#ffffff',
      borderRadius: '8px',
      padding: '16px',
      boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)',
      maxHeight: '500px',
      overflowY: 'auto',
    }}>
      <div style={{ marginBottom: '16px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <h3 style={{ margin: 0, fontSize: '16px', fontWeight: '600' }}>Configure Element</h3>
        <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
          <button
            onClick={onSelectAnother}
            style={{
              background: '#3b82f6',
              color: '#ffffff',
              border: 'none',
              borderRadius: '6px',
              padding: '6px 12px',
              cursor: 'pointer',
              fontSize: '12px',
              fontWeight: '500',
              display: 'flex',
              alignItems: 'center',
              gap: '4px',
            }}
            title="Return to inspect mode to select another element"
          >
            🔄 Select Another
          </button>
          <button
            onClick={onCancel}
            style={{
              background: 'none',
              border: 'none',
              cursor: 'pointer',
              padding: '4px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <X size={18} />
          </button>
        </div>
      </div>

      {/* Selector Preview - Prominent Display */}
      <div style={{ marginBottom: '12px' }}>
        {/* XPath Display */}
        <div style={{
          backgroundColor: '#8b5cf6',
          color: '#ffffff',
          padding: '10px 12px',
          borderRadius: '6px 6px 0 0',
          fontSize: '11px',
          fontFamily: 'monospace',
          fontWeight: '600',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          gap: '8px',
        }}>
          <div style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
            <span style={{ opacity: 0.9 }}>📍 XPATH:</span> {selectors.xpathSelector}
          </div>
          <button
            onClick={() => handleCopy(selectors.xpathSelector)}
            style={{
              background: 'rgba(255, 255, 255, 0.2)',
              border: 'none',
              borderRadius: '4px',
              padding: '4px 8px',
              cursor: 'pointer',
              color: '#ffffff',
              fontSize: '10px',
              fontWeight: '500',
              display: 'flex',
              alignItems: 'center',
              gap: '4px',
            }}
            title="Copy XPath"
          >
            <Copy size={12} /> Copy
          </button>
        </div>

        {/* CSS Display */}
        <div style={{
          backgroundColor: '#3b82f6',
          color: '#ffffff',
          padding: '10px 12px',
          borderRadius: '0 0 6px 6px',
          fontSize: '11px',
          fontFamily: 'monospace',
          fontWeight: '600',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          gap: '8px',
          marginBottom: '8px',
        }}>
          <div style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
            <span style={{ opacity: 0.9 }}>🎯 CSS:</span> {selectors.cssSelector}
          </div>
          <button
            onClick={() => handleCopy(selectors.cssSelector)}
            style={{
              background: 'rgba(255, 255, 255, 0.2)',
              border: 'none',
              borderRadius: '4px',
              padding: '4px 8px',
              cursor: 'pointer',
              color: '#ffffff',
              fontSize: '10px',
              fontWeight: '500',
              display: 'flex',
              alignItems: 'center',
              gap: '4px',
            }}
            title="Copy CSS Selector"
          >
            <Copy size={12} /> Copy
          </button>
        </div>

        {/* Element preview */}
        <div style={{
          backgroundColor: '#f3f4f6',
          padding: '8px',
          borderRadius: '4px',
          fontSize: '12px',
          fontFamily: 'monospace',
        }}>
          <div style={{ fontWeight: 'bold', marginBottom: '4px' }}>
            &lt;{selectedElement.tagName.toLowerCase()}&gt;
          </div>
          {elementPreview && (
            <div style={{ color: '#6b7280', fontSize: '11px' }}>
              "{elementPreview}"
            </div>
          )}
        </div>
      </div>

      {/* Warnings */}
      {selectors.warnings.length > 0 && (
        <div style={{
          backgroundColor: '#fef3c7',
          border: '1px solid #fbbf24',
          borderRadius: '4px',
          padding: '12px',
          marginBottom: '12px',
          fontSize: '12px',
        }}>
          <div style={{ display: 'flex', gap: '8px', alignItems: 'flex-start' }}>
            <AlertCircle size={16} style={{ color: '#f59e0b', flexShrink: 0, marginTop: '2px' }} />
            <div style={{ flex: 1 }}>
              {selectors.warnings.map((warning, i) => (
                <div key={i} style={{ marginBottom: selectors.warnings.length > 1 && i < selectors.warnings.length - 1 ? '12px' : '0' }}>
                  <div style={{ fontWeight: '600', marginBottom: '6px', color: '#92400e' }}>
                    ⚠️ {warning}
                  </div>

                  {/* Detailed explanation for positional selectors */}
                  {warning.includes('positional') && (
                    <div style={{
                      fontSize: '11px',
                      marginTop: '8px',
                      padding: '10px',
                      backgroundColor: '#fffbeb',
                      borderRadius: '4px',
                      lineHeight: '1.6',
                      color: '#78350f',
                    }}>
                      <div style={{ fontWeight: '600', marginBottom: '6px' }}>💡 What this means:</div>
                      <p style={{ margin: '0 0 8px 0' }}>
                        This selector uses <code style={{ background: '#fef3c7', padding: '2px 4px', borderRadius: '2px' }}>:nth-child()</code> or position-based matching
                        (e.g., "the 3rd div"). If the website adds/removes elements before this one, the selector
                        will point to the wrong element.
                      </p>

                      <div style={{ fontWeight: '600', marginBottom: '6px' }}>🔧 How to fix:</div>
                      <p style={{ margin: '0 0 6px 0' }}>Look for elements with stable attributes:</p>
                      <ul style={{ margin: '0 0 8px 0', paddingLeft: '20px' }}>
                        <li><code style={{ background: '#fef3c7', padding: '2px 4px', borderRadius: '2px' }}>id</code> - Best option if unique (e.g., id="search-box")</li>
                        <li><code style={{ background: '#fef3c7', padding: '2px 4px', borderRadius: '2px' }}>data-*</code> attributes (e.g., data-testid, data-id, data-role)</li>
                        <li><code style={{ background: '#fef3c7', padding: '2px 4px', borderRadius: '2px' }}>role</code>, <code style={{ background: '#fef3c7', padding: '2px 4px', borderRadius: '2px' }}>name</code>, <code style={{ background: '#fef3c7', padding: '2px 4px', borderRadius: '2px' }}>aria-label</code> - Accessibility attributes</li>
                        <li>Unique class names (not generic like "button" or "text")</li>
                      </ul>

                      <div style={{ padding: '8px', backgroundColor: '#fef3c7', borderRadius: '4px', fontSize: '10px' }}>
                        <strong>💡 Tip:</strong> Press <kbd style={{ background: '#fde68a', padding: '2px 4px', borderRadius: '2px' }}>{navigator.platform.includes('Mac') ? '⌘↑' : 'Ctrl↑'}</kbd> to select parent element - it might have better attributes!
                      </div>
                    </div>
                  )}

                  {/* Detailed explanation for lack of stable attributes */}
                  {warning.includes('lacks stable attributes') && (
                    <div style={{
                      fontSize: '11px',
                      marginTop: '8px',
                      padding: '10px',
                      backgroundColor: '#fffbeb',
                      borderRadius: '4px',
                      lineHeight: '1.6',
                      color: '#78350f',
                    }}>
                      <div style={{ fontWeight: '600', marginBottom: '6px' }}>💡 What this means:</div>
                      <p style={{ margin: '0 0 8px 0' }}>
                        This element has no unique identifiers (no <code style={{ background: '#fef3c7', padding: '2px 4px', borderRadius: '2px' }}>id</code>,
                        <code style={{ background: '#fef3c7', padding: '2px 4px', borderRadius: '2px' }}>data-*</code> attributes,
                        or meaningful attributes). The selector relies on generic properties that could change when the site updates.
                      </p>

                      <div style={{ fontWeight: '600', marginBottom: '6px' }}>✅ Is this okay?</div>
                      <p style={{ margin: '0 0 8px 0' }}>
                        This selector may still work reliably if:
                      </p>
                      <ul style={{ margin: '0 0 8px 0', paddingLeft: '20px' }}>
                        <li>The element's structure is unlikely to change</li>
                        <li>You're using specific tag + attribute combinations</li>
                        <li>The element is the only one of its kind on the page</li>
                      </ul>

                      <div style={{ fontWeight: '600', marginBottom: '6px' }}>🎯 Recommendation:</div>
                      <p style={{ margin: '0' }}>
                        Test thoroughly before using in production. If possible, ask developers to add
                        <code style={{ background: '#fef3c7', padding: '2px 4px', borderRadius: '2px' }}>data-testid</code> or similar stable attributes to key elements.
                      </p>
                    </div>
                  )}

                  {/* Detailed explanation for fragile nth-child warnings */}
                  {warning.includes('nth-child') && !warning.includes('positional') && (
                    <div style={{
                      fontSize: '11px',
                      marginTop: '8px',
                      padding: '10px',
                      backgroundColor: '#fffbeb',
                      borderRadius: '4px',
                      lineHeight: '1.6',
                      color: '#78350f',
                    }}>
                      <div style={{ fontWeight: '600', marginBottom: '6px' }}>⚠️ Why this is fragile:</div>
                      <p style={{ margin: '0 0 8px 0' }}>
                        Using <code style={{ background: '#fef3c7', padding: '2px 4px', borderRadius: '2px' }}>{warning.match(/:nth-child\(\d+\)/)?.[0]}</code> means
                        "select the Nth child". If elements are added/removed/reordered in that container, this will break.
                      </p>

                      <div style={{ fontWeight: '600', marginBottom: '6px' }}>🔄 What to do:</div>
                      <p style={{ margin: '0' }}>
                        Try to find a unique attribute on this element or its parent. If none exist, this selector might be your only option - just be aware it could break on site updates.
                      </p>
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Element Type */}
      <div style={{ marginBottom: '12px' }}>
        <label style={{ display: 'block', fontSize: '12px', fontWeight: '500', marginBottom: '4px' }}>
          Element Type *
        </label>
        <select
          value={elementType}
          onChange={(e) => setElementType(e.target.value as ElementType)}
          style={{
            width: '100%',
            padding: '6px 8px',
            borderRadius: '4px',
            border: '1px solid #d1d5db',
            fontSize: '13px',
          }}
        >
          {Object.entries(
            ELEMENT_TYPES.reduce((acc, type) => {
              if (!acc[type.category]) acc[type.category] = [];
              acc[type.category].push(type);
              return acc;
            }, {} as Record<string, typeof ELEMENT_TYPES>)
          ).map(([category, types]) => (
            <optgroup key={category} label={category}>
              {types.map(type => (
                <option key={type.value} value={type.value}>{type.label}</option>
              ))}
            </optgroup>
          ))}
        </select>

        {/* Element Description */}
        {ELEMENT_DESCRIPTIONS[elementType] && (
          <div style={{
            marginTop: '6px',
            padding: '8px 10px',
            backgroundColor: ELEMENT_DESCRIPTIONS[elementType].includes('🌐 GENERAL') ? '#f0fdfa' : '#f9fafb',
            borderLeft: `3px solid ${ELEMENT_DESCRIPTIONS[elementType].includes('🌐 GENERAL') ? '#14b8a6' : '#9ca3af'}`,
            borderRadius: '4px',
            fontSize: '11px',
            color: '#374151',
            lineHeight: '1.4',
          }}>
            <strong>ℹ️</strong> {ELEMENT_DESCRIPTIONS[elementType]}
          </div>
        )}
      </div>

      {/* Element Name */}
      <div style={{ marginBottom: '12px' }}>
        <label style={{ display: 'block', fontSize: '12px', fontWeight: '500', marginBottom: '4px' }}>
          Element Name (optional)
        </label>
        <input
          type="text"
          value={elementName}
          onChange={(e) => setElementName(e.target.value)}
          placeholder="e.g., Main Search Bar, Send Message Button, Profile Avatar"
          style={{
            width: '100%',
            padding: '6px 8px',
            borderRadius: '4px',
            border: '1px solid #d1d5db',
            fontSize: '13px',
          }}
        />
        <div style={{ fontSize: '10px', color: '#6b7280', marginTop: '4px' }}>
          Give this element a memorable name for easier identification in your schema
        </div>
      </div>

      {/* Parent Element (for non-wrappers) */}
      {!elementType.endsWith('_wrapper') && (
        <div style={{ marginBottom: '12px' }}>
          <label style={{ display: 'block', fontSize: '12px', fontWeight: '500', marginBottom: '4px' }}>
            Parent Wrapper (optional)
          </label>
          <select
            value={parentElement}
            onChange={(e) => handleParentElementChange(e.target.value)}
            style={{
              width: '100%',
              padding: '6px 8px',
              borderRadius: '4px',
              border: '1px solid #d1d5db',
              fontSize: '13px',
            }}
          >
            <option value="">None (absolute selector)</option>
            <option value="post_wrapper">Post Wrapper (relative)</option>
            <option value="person_wrapper">Person Wrapper (relative)</option>
            <option value="group_wrapper">Group Wrapper (relative)</option>
            <option value="comment_wrapper">Comment Wrapper (relative)</option>
            <option value="message_wrapper">Message Wrapper (relative)</option>
            <option value="conversation_wrapper">Conversation Wrapper (relative)</option>
            <option value="form_wrapper">Form Wrapper (relative)</option>
            <option value="list_wrapper">List Wrapper (relative)</option>
            <option value="modal_wrapper">Modal Wrapper (relative)</option>
          </select>

          {/* Auto-detected feedback */}
          {parentElement && autoDetectedWrapper && (
            <div style={{
              marginTop: '6px',
              padding: '8px 10px',
              backgroundColor: '#d1fae5',
              borderLeft: '3px solid #10b981',
              borderRadius: '4px',
              fontSize: '11px',
              color: '#065f46',
              lineHeight: '1.4',
            }}>
              ✨ <strong>Auto-detected:</strong> This element is inside <code style={{ background: '#a7f3d0', padding: '2px 4px', borderRadius: '2px', fontWeight: '600' }}>{parentElement}</code>
            </div>
          )}

          {/* Manual selection warning */}
          {parentElement && !autoDetectedWrapper && (
            <div style={{
              marginTop: '6px',
              padding: '6px 8px',
              backgroundColor: '#fef3c7',
              borderRadius: '4px',
              fontSize: '11px',
              color: '#78350f',
            }}>
              ⚠️ Use <strong>relative selectors</strong>: XPath should start with <code>.</code> (e.g., <code>.//span[@class="title"]</code>)
            </div>
          )}
        </div>
      )}

      {/* Selector Tabs */}
      <div style={{ marginBottom: '12px' }}>
        <div style={{ display: 'flex', gap: '8px', marginBottom: '8px' }}>
          <button
            onClick={() => setActiveTab('css')}
            style={{
              flex: 1,
              padding: '6px',
              backgroundColor: activeTab === 'css' ? '#3b82f6' : '#f3f4f6',
              color: activeTab === 'css' ? '#ffffff' : '#374151',
              border: 'none',
              borderRadius: '4px',
              fontSize: '12px',
              fontWeight: '500',
              cursor: 'pointer',
            }}
          >
            CSS Selector
          </button>
          <button
            onClick={() => setActiveTab('xpath')}
            style={{
              flex: 1,
              padding: '6px',
              backgroundColor: activeTab === 'xpath' ? '#3b82f6' : '#f3f4f6',
              color: activeTab === 'xpath' ? '#ffffff' : '#374151',
              border: 'none',
              borderRadius: '4px',
              fontSize: '12px',
              fontWeight: '500',
              cursor: 'pointer',
            }}
          >
            XPath
          </button>
        </div>

        {/* Quick Add Attributes */}
        <div style={{ marginBottom: '8px' }}>
          <div style={{ fontSize: '10px', fontWeight: '500', marginBottom: '4px', color: '#6b7280' }}>
            Quick Add Attribute (click to use):
          </div>
          <div style={{ display: 'flex', gap: '4px', flexWrap: 'wrap' }}>
            {['placeholder', 'type', 'name', 'role', 'aria-label', 'class', 'data-testid'].map(attr => (
              <button
                key={attr}
                onClick={() => addAttributeToSelector(attr)}
                style={{
                  padding: '4px 8px',
                  fontSize: '10px',
                  backgroundColor: '#f3f4f6',
                  border: '1px solid #d1d5db',
                  borderRadius: '4px',
                  cursor: 'pointer',
                  fontFamily: 'monospace',
                  fontWeight: '500',
                  transition: 'all 0.2s',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.backgroundColor = '#e5e7eb';
                  e.currentTarget.style.borderColor = '#9ca3af';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.backgroundColor = '#f3f4f6';
                  e.currentTarget.style.borderColor = '#d1d5db';
                }}
                title={`Use element's ${attr} attribute in selector`}
              >
                {attr}
              </button>
            ))}
          </div>
        </div>

        {/* Element Attributes (expandable) */}
        <div style={{ marginBottom: '8px', border: '1px solid #e5e7eb', borderRadius: '6px', overflow: 'hidden' }}>
          <button
            onClick={() => setShowAttributes(!showAttributes)}
            style={{
              width: '100%',
              padding: '8px',
              background: '#f9fafb',
              border: 'none',
              fontSize: '11px',
              fontWeight: '500',
              cursor: 'pointer',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              textAlign: 'left',
            }}
          >
            <span>📋 All Element Attributes ({selectedElement?.attributes.length || 0})</span>
            <span>{showAttributes ? '▼' : '▶'}</span>
          </button>

          {showAttributes && (
            <div style={{ padding: '8px', maxHeight: '150px', overflowY: 'auto', backgroundColor: '#ffffff' }}>
              {selectedElement && Array.from(selectedElement.attributes).map((attr, i) => (
                <div
                  key={i}
                  onClick={() => addAttributeToSelector(attr.name)}
                  style={{
                    padding: '6px 8px',
                    fontSize: '10px',
                    fontFamily: 'monospace',
                    borderRadius: '4px',
                    marginBottom: '4px',
                    backgroundColor: '#f9fafb',
                    cursor: 'pointer',
                    transition: 'background 0.2s',
                  }}
                  onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#e5e7eb'}
                  onMouseLeave={(e) => e.currentTarget.style.backgroundColor = '#f9fafb'}
                  title="Click to use this attribute in selector"
                >
                  <span style={{ color: '#3b82f6', fontWeight: '600' }}>{attr.name}</span>
                  <span style={{ color: '#6b7280' }}>="</span>
                  <span style={{ color: '#059669' }}>{attr.value.length > 50 ? attr.value.substring(0, 50) + '...' : attr.value}</span>
                  <span style={{ color: '#6b7280' }}>"</span>
                </div>
              ))}
            </div>
          )}
        </div>

        <div style={{ position: 'relative' }}>
          <textarea
            value={activeTab === 'css' ? cssSelector : xpathSelector}
            onChange={(e) => activeTab === 'css' ? setCssSelector(e.target.value) : setXpathSelector(e.target.value)}
            rows={4}
            placeholder={activeTab === 'css'
              ? 'input[placeholder="Search"]'
              : '//input[@placeholder="Search"]'
            }
            style={{
              width: '100%',
              padding: '8px',
              borderRadius: '4px',
              border: '1px solid #d1d5db',
              fontSize: '12px',
              fontFamily: 'monospace',
              resize: 'vertical',
            }}
          />
          <button
            onClick={() => handleCopy(activeTab === 'css' ? cssSelector : xpathSelector)}
            style={{
              position: 'absolute',
              top: '8px',
              right: '8px',
              background: 'rgba(255, 255, 255, 0.9)',
              border: '1px solid #d1d5db',
              borderRadius: '4px',
              padding: '4px',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
            }}
            title="Copy selector"
          >
            <Copy size={14} />
          </button>
        </div>

        <div style={{ marginTop: '8px', display: 'flex', gap: '8px' }}>
          <button
            onClick={handleTest}
            style={{
              flex: 1,
              padding: '6px 12px',
              backgroundColor: '#10b981',
              color: '#ffffff',
              border: 'none',
              borderRadius: '4px',
              fontSize: '12px',
              fontWeight: '500',
              cursor: 'pointer',
            }}
          >
            Test Selector
          </button>
          <button
            onClick={handleTestClick}
            style={{
              flex: 1,
              padding: '6px 12px',
              backgroundColor: '#f59e0b',
              color: '#ffffff',
              border: 'none',
              borderRadius: '4px',
              fontSize: '12px',
              fontWeight: '500',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '4px',
            }}
            title="Test if clicking the selector works"
          >
            🖱️ Test Click
          </button>
          {activeHighlights && (
            <button
              onClick={handleClearHighlights}
              style={{
                padding: '6px 12px',
                backgroundColor: '#ef4444',
                color: '#ffffff',
                border: 'none',
                borderRadius: '4px',
                fontSize: '12px',
                fontWeight: '500',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '4px',
              }}
              title="Clear test highlights from page"
            >
              <X size={14} />
              Clear
            </button>
          )}
        </div>

        {testResult && (
          <div style={{
            marginTop: '8px',
            padding: '8px',
            backgroundColor: testResult.matchCount === 1 ? '#d1fae5' : '#fee2e2',
            borderRadius: '4px',
            fontSize: '12px',
          }}>
            {testResult.matchCount === 1 ? (
              <span style={{ color: '#065f46' }}>✓ Selector matches 1 element (perfect!)</span>
            ) : testResult.matchCount === 0 ? (
              <span style={{ color: '#991b1b' }}>✗ Selector matches 0 elements</span>
            ) : (
              <span style={{ color: '#991b1b' }}>✗ Selector matches {testResult.matchCount} elements (should be 1)</span>
            )}
          </div>
        )}
      </div>

      {/* Options */}
      <div style={{ marginBottom: '12px' }}>
        <label style={{ display: 'flex', alignItems: 'center', gap: '8px', fontSize: '13px', marginBottom: '8px' }}>
          <input
            type="checkbox"
            checked={isRequired}
            onChange={(e) => setIsRequired(e.target.checked)}
          />
          Required element
        </label>
        <label style={{ display: 'flex', alignItems: 'center', gap: '8px', fontSize: '13px' }}>
          <input
            type="checkbox"
            checked={multiple}
            onChange={(e) => setMultiple(e.target.checked)}
          />
          Can match multiple elements
        </label>
      </div>

      {/* Description */}
      <div style={{ marginBottom: '16px' }}>
        <label style={{ display: 'block', fontSize: '12px', fontWeight: '500', marginBottom: '4px' }}>
          Description (optional)
        </label>
        <input
          type="text"
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          placeholder="Add notes about this element..."
          style={{
            width: '100%',
            padding: '6px 8px',
            borderRadius: '4px',
            border: '1px solid #d1d5db',
            fontSize: '13px',
          }}
        />
      </div>

      {/* Actions */}
      <div style={{ display: 'flex', gap: '8px' }}>
        <button
          onClick={handleAdd}
          style={{
            flex: 1,
            padding: '8px',
            backgroundColor: '#3b82f6',
            color: '#ffffff',
            border: 'none',
            borderRadius: '4px',
            fontSize: '13px',
            fontWeight: '500',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '6px',
          }}
        >
          <Check size={16} />
          Add to Schema
        </button>
        <button
          onClick={onCancel}
          style={{
            padding: '8px 16px',
            backgroundColor: '#f3f4f6',
            color: '#374151',
            border: 'none',
            borderRadius: '4px',
            fontSize: '13px',
            fontWeight: '500',
            cursor: 'pointer',
          }}
        >
          Cancel
        </button>
      </div>
    </div>
  );
}
