#!/usr/bin/env python3
"""
Test Admin Authorization Vulnerabilities
Tests if regular users can access admin endpoints
"""

import requests
import time
import json
from datetime import datetime
from colorama import Fore, Style, init

init(autoreset=True)

class AdminAuthTester:
    def __init__(self, base_url):
        self.base_url = base_url.rstrip('/')
        self.results = []
        self.regular_user_token = None

    def log_result(self, test_name, success, details):
        """Log test result"""
        result = {
            'test': test_name,
            'success': success,
            'details': details,
            'timestamp': datetime.now().isoformat()
        }
        self.results.append(result)

        status = f"{Fore.RED}VULNERABLE" if success else f"{Fore.GREEN}SECURE"
        print(f"[{status}{Style.RESET_ALL}] {test_name}")
        print(f"  └─ {details}\n")

    def create_test_account(self):
        """Create a test account and get token"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"SETUP: Creating Test Account")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        timestamp = int(time.time())
        email = f"admin-test-{timestamp}@example.com"
        password = "Password123"

        # Register
        try:
            reg_response = requests.post(
                f"{self.base_url}/register",
                json={
                    "name": "Admin Test User",
                    "email": email,
                    "password": password,
                    "password_confirmation": password
                },
                headers={'Content-Type': 'application/json'},
                timeout=10
            )

            print(f"Registration Status: {reg_response.status_code}")

            # Login to get token
            login_response = requests.post(
                f"{self.base_url}/api/auth/login",
                json={"email": email, "password": password},
                headers={'Content-Type': 'application/json'},
                timeout=10
            )

            if login_response.status_code == 200:
                try:
                    token = login_response.json().get('token') or login_response.json().get('access_token')
                    self.regular_user_token = token
                    print(f"{Fore.GREEN}✓ Test account created{Style.RESET_ALL}")
                    print(f"  Email: {email}")
                    print(f"  Token: {token[:30]}...\n")
                    return True
                except:
                    print(f"{Fore.RED}✗ Could not extract token{Style.RESET_ALL}\n")
                    return False
            else:
                print(f"{Fore.RED}✗ Login failed: {login_response.status_code}{Style.RESET_ALL}\n")
                return False

        except Exception as e:
            print(f"{Fore.RED}Error creating test account: {str(e)}{Style.RESET_ALL}\n")
            return False

    def test_schema_access(self):
        """Test access to admin schema endpoints"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 1: Admin Schema Endpoint Access")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        if not self.regular_user_token:
            print(f"{Fore.YELLOW}Skipped: No token available{Style.RESET_ALL}\n")
            return False

        endpoints = [
            ("GET", "/api/v1/admin/schemas", "List schemas"),
            ("GET", "/api/v1/admin/schemas/1", "Get schema"),
            ("DELETE", "/api/v1/admin/schemas/1", "Delete schema"),
        ]

        vulnerable = False

        for method, endpoint, desc in endpoints:
            try:
                print(f"Testing: {method} {endpoint} ({desc})")

                if method == "GET":
                    response = requests.get(
                        f"{self.base_url}{endpoint}",
                        headers={
                            'Authorization': f'Bearer {self.regular_user_token}',
                            'Content-Type': 'application/json'
                        },
                        timeout=10
                    )
                elif method == "DELETE":
                    response = requests.delete(
                        f"{self.base_url}{endpoint}",
                        headers={
                            'Authorization': f'Bearer {self.regular_user_token}',
                            'Content-Type': 'application/json'
                        },
                        timeout=10
                    )
                else:
                    continue

                print(f"  Status: {response.status_code}")

                # If we get 200, 201, or anything other than 403/401, it's vulnerable
                if response.status_code in [200, 201, 204]:
                    print(f"  {Fore.RED}✗ ACCESS GRANTED (Should be 403!){Style.RESET_ALL}\n")
                    vulnerable = True
                elif response.status_code in [401, 403]:
                    print(f"  {Fore.GREEN}✓ Access denied (correct){Style.RESET_ALL}\n")
                else:
                    print(f"  {Fore.YELLOW}? Unexpected response{Style.RESET_ALL}\n")

                time.sleep(0.5)

            except Exception as e:
                print(f"  {Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")

        if vulnerable:
            self.log_result(
                "Admin Schema Access",
                True,
                "Regular user can access admin endpoints!"
            )
        else:
            self.log_result(
                "Admin Schema Access",
                False,
                "Admin endpoints properly protected"
            )

        return vulnerable

    def test_plugin_upload(self):
        """Test if regular users can upload plugins"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 2: Plugin Upload Authorization")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        if not self.regular_user_token:
            print(f"{Fore.YELLOW}Skipped: No token available{Style.RESET_ALL}\n")
            return False

        # Create a dummy plugin file
        import tempfile
        import zipfile

        with tempfile.NamedTemporaryFile(mode='w', suffix='.json', delete=False) as f:
            json.dump({"name": "test-plugin", "version": "1.0.0"}, f)
            plugin_json = f.name

        with tempfile.NamedTemporaryFile(suffix='.zip', delete=False) as f:
            zip_path = f.name

        with zipfile.ZipFile(zip_path, 'w') as zf:
            zf.write(plugin_json, 'plugin.json')

        try:
            print(f"Attempting plugin upload as regular user...")

            with open(zip_path, 'rb') as f:
                response = requests.post(
                    f"{self.base_url}/admin/plugins/upload",
                    files={'plugin': ('test.zip', f, 'application/zip')},
                    headers={
                        'Authorization': f'Bearer {self.regular_user_token}'
                    },
                    timeout=15
                )

            print(f"Status Code: {response.status_code}")
            print(f"Response: {response.text[:300]}\n")

            if response.status_code in [200, 201]:
                self.log_result(
                    "Plugin Upload Authorization",
                    True,
                    "Regular user can upload plugins! (RCE possible)"
                )
                return True
            else:
                self.log_result(
                    "Plugin Upload Authorization",
                    False,
                    "Plugin upload blocked for regular users"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Plugin Upload Authorization", False, f"Error: {str(e)}")
            return False
        finally:
            import os
            os.unlink(plugin_json)
            os.unlink(zip_path)

    def test_admin_panel_access(self):
        """Test if regular users can access admin panel"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 3: Admin Panel Access")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        if not self.regular_user_token:
            print(f"{Fore.YELLOW}Skipped: No token available{Style.RESET_ALL}\n")
            return False

        admin_urls = [
            "/admin",
            "/admin/dashboard",
            "/admin/users",
            "/admin/settings"
        ]

        vulnerable = False

        for url in admin_urls:
            try:
                print(f"Testing: {url}")

                response = requests.get(
                    f"{self.base_url}{url}",
                    headers={
                        'Authorization': f'Bearer {self.regular_user_token}'
                    },
                    timeout=10,
                    allow_redirects=False
                )

                print(f"  Status: {response.status_code}")

                if response.status_code == 200:
                    print(f"  {Fore.RED}✗ ACCESS GRANTED{Style.RESET_ALL}\n")
                    vulnerable = True
                elif response.status_code in [301, 302]:
                    print(f"  {Fore.YELLOW}→ Redirected to: {response.headers.get('Location')}{Style.RESET_ALL}\n")
                elif response.status_code in [401, 403]:
                    print(f"  {Fore.GREEN}✓ Access denied{Style.RESET_ALL}\n")
                else:
                    print(f"  ? Response: {response.status_code}\n")

            except Exception as e:
                print(f"  Error: {str(e)}\n")

        if vulnerable:
            self.log_result(
                "Admin Panel Access",
                True,
                "Regular user accessed admin panel!"
            )
        else:
            self.log_result(
                "Admin Panel Access",
                False,
                "Admin panel properly protected"
            )

        return vulnerable

    def run_all_tests(self, existing_token=None):
        """Run all admin authorization tests"""
        print(f"\n{Fore.YELLOW}{'='*60}")
        print(f"ADMIN AUTHORIZATION VULNERABILITY TESTS")
        print(f"{'='*60}{Style.RESET_ALL}\n")
        print(f"Target: {self.base_url}")
        print(f"Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")

        # Setup
        if existing_token:
            self.regular_user_token = existing_token
            print(f"{Fore.GREEN}Using provided token{Style.RESET_ALL}\n")
        else:
            if not self.create_test_account():
                print(f"{Fore.RED}Cannot continue without a test account{Style.RESET_ALL}")
                return []

        # Run tests
        self.test_schema_access()
        time.sleep(1)

        self.test_plugin_upload()
        time.sleep(1)

        self.test_admin_panel_access()

        # Summary
        print(f"\n{Fore.YELLOW}{'='*60}")
        print(f"TEST SUMMARY")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        vulnerable_count = sum(1 for r in self.results if r['success'])
        total_count = len(self.results)

        print(f"Total Tests: {total_count}")
        print(f"Vulnerable: {Fore.RED}{vulnerable_count}{Style.RESET_ALL}")
        print(f"Secure: {Fore.GREEN}{total_count - vulnerable_count}{Style.RESET_ALL}\n")

        if vulnerable_count > 0:
            print(f"{Fore.RED}⚠️  CRITICAL: Authorization vulnerabilities detected!{Style.RESET_ALL}")
            print(f"{Fore.RED}   Regular users can access admin functions.{Style.RESET_ALL}\n")
        else:
            print(f"{Fore.GREEN}✓ All authorization tests passed{Style.RESET_ALL}\n")

        return self.results

if __name__ == "__main__":
    import sys
    import os
    from dotenv import load_dotenv

    load_dotenv()

    base_url = os.getenv('BASE_URL', 'https://evenleads.com')
    token = os.getenv('TEST_TOKEN')

    if len(sys.argv) > 1:
        base_url = sys.argv[1]

    tester = AdminAuthTester(base_url)
    results = tester.run_all_tests(token)

    with open('results_admin_authorization.json', 'w') as f:
        json.dump(results, f, indent=2)
    print(f"Results saved to: results_admin_authorization.json")
