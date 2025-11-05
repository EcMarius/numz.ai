#!/usr/bin/env python3
"""
Test Configuration Security Issues
Tests for exposed credentials, weak secrets, and configuration vulnerabilities
"""

import requests
import time
import json
from datetime import datetime
from colorama import Fore, Style, init

init(autoreset=True)

class ConfigSecurityTester:
    def __init__(self, base_url):
        self.base_url = base_url.rstrip('/')
        self.results = []

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

    def test_env_example_accessible(self):
        """Test if .env.example is publicly accessible"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 1: .env.example File Accessibility")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        endpoints = [
            "/.env.example",
            "/.env",
            "/env.example",
            "/.env.backup",
            "/.env.old"
        ]

        found_files = []

        for endpoint in endpoints:
            try:
                print(f"Checking: {endpoint}")
                response = requests.get(
                    f"{self.base_url}{endpoint}",
                    timeout=5,
                    allow_redirects=False
                )

                print(f"  Status: {response.status_code}")

                if response.status_code == 200:
                    print(f"  {Fore.RED}✗ FILE ACCESSIBLE!{Style.RESET_ALL}")
                    found_files.append(endpoint)

                    # Check for credentials in content
                    if 'PASSWORD' in response.text or 'SECRET' in response.text:
                        print(f"  {Fore.RED}⚠️  Contains credentials/secrets!{Style.RESET_ALL}\n")
                    else:
                        print()
                elif response.status_code in [403, 404]:
                    print(f"  {Fore.GREEN}✓ Not accessible{Style.RESET_ALL}\n")
                else:
                    print(f"  ? Status: {response.status_code}\n")

            except Exception as e:
                print(f"  Error: {str(e)}\n")

        if found_files:
            self.log_result(
                "Environment File Exposure",
                True,
                f"Environment files accessible: {', '.join(found_files)}"
            )
            return True
        else:
            self.log_result(
                "Environment File Exposure",
                False,
                "Environment files not accessible"
            )
            return False

    def test_debug_mode_enabled(self):
        """Test if debug mode is enabled in production"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 2: Debug Mode Detection")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        try:
            # Trigger error to check for debug output
            response = requests.get(
                f"{self.base_url}/error/test",  # Known error endpoint
                timeout=10
            )

            print(f"Status Code: {response.status_code}")

            # Check for debug traces
            debug_indicators = [
                'Whoops\\Handler',
                'Stack trace',
                'vendor/laravel',
                'APP_DEBUG',
                'DebugBar',
                'Ignition'
            ]

            has_debug = any(indicator in response.text for indicator in debug_indicators)

            if has_debug:
                print(f"{Fore.RED}Debug mode appears to be enabled!{Style.RESET_ALL}")
                print(f"Response contains stack traces/debug info\n")
                self.log_result(
                    "Debug Mode Detection",
                    True,
                    "Debug mode enabled - exposes sensitive information"
                )
                return True
            else:
                print(f"{Fore.GREEN}No debug information exposed{Style.RESET_ALL}\n")
                self.log_result(
                    "Debug Mode Detection",
                    False,
                    "Debug mode disabled (correct)"
                )
                return False

        except Exception as e:
            print(f"{Fore.YELLOW}Could not test: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Debug Mode Detection", False, "Could not test")
            return False

    def test_information_disclosure(self):
        """Test for information disclosure in responses"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 3: Information Disclosure")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        endpoints = [
            ("/api/settings", "Public settings endpoint"),
            ("/api/v1/health", "Health check endpoint"),
            ("/.git/config", "Git configuration"),
            ("/composer.json", "Composer dependencies"),
            ("/package.json", "NPM dependencies"),
        ]

        disclosed_info = []

        for endpoint, desc in endpoints:
            try:
                print(f"Testing: {endpoint} ({desc})")
                response = requests.get(
                    f"{self.base_url}{endpoint}",
                    timeout=5
                )

                print(f"  Status: {response.status_code}")

                if response.status_code == 200:
                    # Check what information is exposed
                    sensitive_keys = ['key', 'secret', 'password', 'token', 'api', 'stripe', 'database']
                    content_lower = response.text.lower()

                    exposed = [key for key in sensitive_keys if key in content_lower]

                    if exposed:
                        print(f"  {Fore.RED}✗ Exposes: {', '.join(exposed)}{Style.RESET_ALL}\n")
                        disclosed_info.append(f"{endpoint}: {', '.join(exposed)}")
                    else:
                        print(f"  {Fore.YELLOW}! Accessible but no sensitive data{Style.RESET_ALL}\n")
                elif response.status_code in [403, 404]:
                    print(f"  {Fore.GREEN}✓ Not accessible{Style.RESET_ALL}\n")
                else:
                    print(f"  ? Status: {response.status_code}\n")

            except Exception as e:
                print(f"  Error: {str(e)}\n")

        if disclosed_info:
            self.log_result(
                "Information Disclosure",
                True,
                f"Sensitive information exposed: {'; '.join(disclosed_info)}"
            )
            return True
        else:
            self.log_result(
                "Information Disclosure",
                False,
                "No sensitive information disclosed"
            )
            return False

    def test_security_headers(self):
        """Test if security headers are present"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 4: Security Headers")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        try:
            response = requests.get(
                f"{self.base_url}",
                timeout=10
            )

            headers = response.headers
            print(f"{Fore.CYAN}Checking security headers:{Style.RESET_ALL}\n")

            required_headers = {
                'X-Frame-Options': ['DENY', 'SAMEORIGIN'],
                'X-Content-Type-Options': ['nosniff'],
                'X-XSS-Protection': ['1'],
                'Strict-Transport-Security': ['max-age'],
                'Content-Security-Policy': ['default-src']
            }

            missing_headers = []
            weak_headers = []

            for header, expected_values in required_headers.items():
                value = headers.get(header, '')
                present = bool(value)

                if not present:
                    print(f"  {Fore.RED}✗ {header}: MISSING{Style.RESET_ALL}")
                    missing_headers.append(header)
                else:
                    # Check if value is strong enough
                    is_strong = any(exp in value for exp in expected_values)
                    if is_strong:
                        print(f"  {Fore.GREEN}✓ {header}: {value[:50]}{Style.RESET_ALL}")
                    else:
                        print(f"  {Fore.YELLOW}! {header}: {value[:50]} (weak){Style.RESET_ALL}")
                        weak_headers.append(header)

            print()

            if missing_headers or weak_headers:
                details = f"Missing: {', '.join(missing_headers)}; Weak: {', '.join(weak_headers)}"
                self.log_result(
                    "Security Headers",
                    True,
                    details if details != "Missing: ; Weak: " else "Some headers missing/weak"
                )
                return True
            else:
                self.log_result(
                    "Security Headers",
                    False,
                    "All security headers present and strong"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Security Headers", False, f"Error: {str(e)}")
            return False

    def test_cors_misconfiguration(self):
        """Test for CORS misconfiguration"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 5: CORS Configuration")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        try:
            response = requests.options(
                f"{self.base_url}/api/v1/campaigns",
                headers={
                    'Origin': 'https://evil.com',
                    'Access-Control-Request-Method': 'POST'
                },
                timeout=10
            )

            cors_header = response.headers.get('Access-Control-Allow-Origin', '')
            print(f"CORS Header: {cors_header}\n")

            if cors_header == '*':
                self.log_result(
                    "CORS Misconfiguration",
                    True,
                    "CORS allows all origins (*) - API accessible from any domain"
                )
                return True
            elif 'evil.com' in cors_header:
                self.log_result(
                    "CORS Misconfiguration",
                    True,
                    "CORS accepts arbitrary origins"
                )
                return True
            elif cors_header:
                print(f"{Fore.GREEN}CORS is configured: {cors_header}{Style.RESET_ALL}\n")
                self.log_result(
                    "CORS Misconfiguration",
                    False,
                    f"CORS properly configured: {cors_header}"
                )
                return False
            else:
                print(f"{Fore.GREEN}No CORS header (API may be origin-restricted){Style.RESET_ALL}\n")
                self.log_result(
                    "CORS Misconfiguration",
                    False,
                    "No CORS header present"
                )
                return False

        except Exception as e:
            print(f"{Fore.YELLOW}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("CORS Misconfiguration", False, f"Error: {str(e)}")
            return False

    def run_all_tests(self, email=None, password=None):
        """Run all configuration security tests"""
        print(f"\n{Fore.YELLOW}{'='*60}")
        print(f"CONFIGURATION SECURITY TESTS")
        print(f"{'='*60}{Style.RESET_ALL}\n")
        print(f"Target: {self.base_url}")
        print(f"Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")

        # Run tests
        self.test_env_example_accessible()
        time.sleep(1)

        self.test_debug_mode_enabled()
        time.sleep(1)

        self.test_information_disclosure()
        time.sleep(1)

        self.test_security_headers()
        time.sleep(1)

        self.test_cors_misconfiguration()

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
            print(f"{Fore.RED}⚠️  WARNING: Configuration issues detected!{Style.RESET_ALL}\n")
        else:
            print(f"{Fore.GREEN}✓ All configuration tests passed{Style.RESET_ALL}\n")

        return self.results

if __name__ == "__main__":
    import sys
    import os
    from dotenv import load_dotenv

    load_dotenv()

    base_url = os.getenv('BASE_URL', 'https://evenleads.com')

    if len(sys.argv) > 1:
        base_url = sys.argv[1]

    tester = ConfigSecurityTester(base_url)
    results = tester.run_all_tests()

    with open('results_config_security.json', 'w') as f:
        json.dump(results, f, indent=2)
    print(f"Results saved to: results_config_security.json")
