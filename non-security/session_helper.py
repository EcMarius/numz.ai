#!/usr/bin/env python3
"""
Session Helper for Security Tests
Allows using existing browser sessions instead of programmatic login
"""

import os
import json
import requests
from colorama import Fore, Style

class SessionManager:
    """
    Manages authentication sessions for security tests

    Can use either:
    1. Existing browser session (cookies)
    2. Programmatic login (API token)
    """

    def __init__(self, base_url):
        self.base_url = base_url.rstrip('/')
        self.use_existing_session = os.getenv('ACCOUNT_EXISTING', 'false').lower() == 'true'
        self.session_cookies = None
        self.api_token = None

        if self.use_existing_session:
            self._load_existing_session()

    def _load_existing_session(self):
        """Load existing browser session from environment"""
        print(f"{Fore.CYAN}[Session Helper] Using existing browser session{Style.RESET_ALL}")

        # Try to load session cookies from environment
        # Method 1: JSON string of cookies
        cookies_json = os.getenv('SESSION_COOKIES')
        if cookies_json:
            try:
                self.session_cookies = json.loads(cookies_json)
                print(f"{Fore.GREEN}✓ Loaded {len(self.session_cookies)} cookies from SESSION_COOKIES{Style.RESET_ALL}")
                return
            except json.JSONDecodeError:
                print(f"{Fore.YELLOW}⚠ Could not parse SESSION_COOKIES JSON{Style.RESET_ALL}")

        # Method 2: Individual cookie values
        laravel_session = os.getenv('LARAVEL_SESSION')
        xsrf_token = os.getenv('XSRF_TOKEN')

        if laravel_session:
            self.session_cookies = {
                'laravel_session': laravel_session
            }
            if xsrf_token:
                self.session_cookies['XSRF-TOKEN'] = xsrf_token

            print(f"{Fore.GREEN}✓ Loaded Laravel session cookie{Style.RESET_ALL}")
            return

        # Method 3: Try to get API token directly
        api_token = os.getenv('API_TOKEN')
        if api_token:
            self.api_token = api_token
            print(f"{Fore.GREEN}✓ Using API token from environment{Style.RESET_ALL}")
            return

        print(f"{Fore.YELLOW}⚠ No session data found in environment variables{Style.RESET_ALL}")
        print(f"{Fore.YELLOW}  Set one of: SESSION_COOKIES, LARAVEL_SESSION, or API_TOKEN{Style.RESET_ALL}")

    def get_authenticated_session(self):
        """
        Get a requests session with authentication

        Returns:
            tuple: (session, auth_type) where auth_type is 'cookies', 'token', or None
        """
        session = requests.Session()

        if self.use_existing_session:
            if self.session_cookies:
                session.cookies.update(self.session_cookies)
                return (session, 'cookies')
            elif self.api_token:
                session.headers.update({'Authorization': f'Bearer {self.api_token}'})
                return (session, 'token')

        return (session, None)

    def get_auth_headers(self):
        """Get authentication headers for requests"""
        if self.use_existing_session and self.api_token:
            return {'Authorization': f'Bearer {self.api_token}'}
        return {}

    def get_cookies(self):
        """Get session cookies"""
        if self.use_existing_session and self.session_cookies:
            return self.session_cookies
        return {}

    def login_if_needed(self, email=None, password=None):
        """
        Login only if not using existing session

        Returns:
            tuple: (success, token_or_cookies, message)
        """
        if self.use_existing_session:
            if self.session_cookies:
                return (True, self.session_cookies, "Using existing session cookies")
            elif self.api_token:
                return (True, self.api_token, "Using existing API token")
            else:
                return (False, None, "ACCOUNT_EXISTING is set but no session data provided")

        # Programmatic login
        if not email or not password:
            return (False, None, "Email and password required for login")

        try:
            response = requests.post(
                f"{self.base_url}/api/auth/login",
                json={"email": email, "password": password},
                headers={'Content-Type': 'application/json'},
                timeout=10
            )

            if response.status_code != 200:
                return (False, None, f"Login failed: {response.status_code}")

            try:
                token = response.json().get('token') or response.json().get('access_token')
            except:
                return (False, None, "Could not extract token from response")

            if not token:
                return (False, None, "No token in login response")

            self.api_token = token
            return (True, token, "Login successful")

        except Exception as e:
            return (False, None, f"Login error: {str(e)}")

    def make_authenticated_request(self, method, url, **kwargs):
        """
        Make an authenticated HTTP request

        Args:
            method: HTTP method ('get', 'post', 'put', 'delete', etc.)
            url: URL to request
            **kwargs: Additional arguments for requests

        Returns:
            requests.Response object
        """
        session, auth_type = self.get_authenticated_session()

        # Add auth headers if using token
        if 'headers' not in kwargs:
            kwargs['headers'] = {}
        kwargs['headers'].update(self.get_auth_headers())

        # Add timeout if not specified
        if 'timeout' not in kwargs:
            kwargs['timeout'] = 10

        # Make request
        return session.request(method, url, **kwargs)

    def is_using_existing_session(self):
        """Check if using existing session mode"""
        return self.use_existing_session

    def print_session_info(self):
        """Print current session information"""
        if self.use_existing_session:
            print(f"\n{Fore.CYAN}Session Mode: Existing Browser Session{Style.RESET_ALL}")
            if self.session_cookies:
                print(f"  └─ Using {len(self.session_cookies)} cookies")
            elif self.api_token:
                print(f"  └─ Using API token")
            else:
                print(f"  └─ {Fore.YELLOW}No session data loaded!{Style.RESET_ALL}")
        else:
            print(f"\n{Fore.CYAN}Session Mode: Programmatic Login{Style.RESET_ALL}")
        print()


def print_session_setup_instructions():
    """Print instructions for setting up existing session"""
    print(f"""
{Fore.CYAN}{'='*70}
HOW TO USE EXISTING BROWSER SESSION
{'='*70}{Style.RESET_ALL}

To use an existing logged-in browser session, set these environment variables:

{Fore.YELLOW}Method 1: Session Cookies (Recommended){Style.RESET_ALL}
1. Log in to your application in a browser
2. Open Developer Tools (F12) → Application/Storage → Cookies
3. Copy the cookies and create a JSON object:

   export SESSION_COOKIES='{{"laravel_session":"your-session-value","XSRF-TOKEN":"your-xsrf-token"}}'
   export ACCOUNT_EXISTING=true

{Fore.YELLOW}Method 2: Individual Cookie Values{Style.RESET_ALL}
1. Copy just the Laravel session cookie:

   export LARAVEL_SESSION="your-session-value"
   export XSRF_TOKEN="your-xsrf-token"  # Optional
   export ACCOUNT_EXISTING=true

{Fore.YELLOW}Method 3: API Token{Style.RESET_ALL}
1. Get your API token from the application
2. Set it directly:

   export API_TOKEN="your-api-token"
   export ACCOUNT_EXISTING=true

{Fore.CYAN}Usage:{Style.RESET_ALL}
  export ACCOUNT_EXISTING=true
  export LARAVEL_SESSION="abc123..."
  python run_all_tests.py

{Fore.CYAN}Or add to .env file:{Style.RESET_ALL}
  ACCOUNT_EXISTING=true
  LARAVEL_SESSION=abc123...

{Fore.YELLOW}Note:{Style.RESET_ALL} If ACCOUNT_EXISTING is not set or false, tests will use
programmatic login with TEST_EMAIL and TEST_PASSWORD as before.

{Fore.CYAN}{'='*70}{Style.RESET_ALL}
    """)
