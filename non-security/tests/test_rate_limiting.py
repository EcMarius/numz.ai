#!/usr/bin/env python3
"""
Test Rate Limiting Vulnerabilities
Tests if endpoints have proper rate limiting to prevent brute force attacks
"""

import requests
import time
import json
from datetime import datetime
from colorama import Fore, Style, init
from concurrent.futures import ThreadPoolExecutor, as_completed

init(autoreset=True)

class RateLimitTester:
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

    def test_login_rate_limit(self, attempts=50):
        """Test login endpoint for rate limiting"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 1: Login Endpoint Rate Limiting")
        print(f"{'='*60}{Style.RESET_ALL}\n")
        print(f"Attempting {attempts} rapid login requests...\n")

        successful_attempts = 0
        blocked_attempts = 0
        response_codes = {}

        start_time = time.time()

        for i in range(attempts):
            try:
                response = requests.post(
                    f"{self.base_url}/api/auth/login",
                    json={
                        "email": "test@example.com",
                        "password": f"wrongpass{i}"
                    },
                    headers={'Content-Type': 'application/json'},
                    timeout=5
                )

                code = response.status_code
                response_codes[code] = response_codes.get(code, 0) + 1

                if code == 429:  # Too Many Requests
                    blocked_attempts += 1
                    print(f"{Fore.GREEN}Attempt {i+1}: BLOCKED (429){Style.RESET_ALL}")
                else:
                    successful_attempts += 1
                    if i < 10 or i % 10 == 0:
                        print(f"{Fore.YELLOW}Attempt {i+1}: Allowed ({code}){Style.RESET_ALL}")

                time.sleep(0.1)  # Small delay

            except requests.exceptions.Timeout:
                print(f"{Fore.RED}Attempt {i+1}: Timeout{Style.RESET_ALL}")
            except Exception as e:
                print(f"{Fore.RED}Attempt {i+1}: Error - {str(e)}{Style.RESET_ALL}")

        elapsed_time = time.time() - start_time

        print(f"\n{Fore.CYAN}Results:{Style.RESET_ALL}")
        print(f"  Total Attempts: {attempts}")
        print(f"  Successful: {successful_attempts}")
        print(f"  Blocked (429): {blocked_attempts}")
        print(f"  Time Elapsed: {elapsed_time:.2f}s")
        print(f"  Request Rate: {attempts/elapsed_time:.2f} req/s")
        print(f"  Response Codes: {response_codes}\n")

        # If more than 80% of attempts succeeded, it's vulnerable
        if successful_attempts > (attempts * 0.8):
            self.log_result(
                "Login Rate Limiting",
                True,
                f"No rate limiting! {successful_attempts}/{attempts} attempts succeeded"
            )
            return True
        else:
            self.log_result(
                "Login Rate Limiting",
                False,
                f"Rate limiting active: {blocked_attempts}/{attempts} blocked"
            )
            return False

    def test_registration_rate_limit(self, attempts=20):
        """Test registration endpoint for rate limiting"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 2: Registration Endpoint Rate Limiting")
        print(f"{'='*60}{Style.RESET_ALL}\n")
        print(f"Attempting {attempts} rapid registration requests...\n")

        successful_attempts = 0
        blocked_attempts = 0

        start_time = time.time()

        for i in range(attempts):
            try:
                timestamp = int(time.time() * 1000) + i
                response = requests.post(
                    f"{self.base_url}/register",
                    json={
                        "name": f"Rate Test {i}",
                        "email": f"ratetest{timestamp}@example.com",
                        "password": "Password123",
                        "password_confirmation": "Password123"
                    },
                    headers={'Content-Type': 'application/json'},
                    timeout=5
                )

                if response.status_code == 429:
                    blocked_attempts += 1
                    print(f"{Fore.GREEN}Attempt {i+1}: BLOCKED (429){Style.RESET_ALL}")
                elif response.status_code in [200, 201, 302]:
                    successful_attempts += 1
                    print(f"{Fore.YELLOW}Attempt {i+1}: Success ({response.status_code}){Style.RESET_ALL}")
                else:
                    print(f"{Fore.YELLOW}Attempt {i+1}: Response {response.status_code}{Style.RESET_ALL}")

                time.sleep(0.2)

            except Exception as e:
                print(f"{Fore.RED}Attempt {i+1}: Error - {str(e)}{Style.RESET_ALL}")

        elapsed_time = time.time() - start_time

        print(f"\n{Fore.CYAN}Results:{Style.RESET_ALL}")
        print(f"  Total Attempts: {attempts}")
        print(f"  Successful: {successful_attempts}")
        print(f"  Blocked: {blocked_attempts}")
        print(f"  Time: {elapsed_time:.2f}s\n")

        if successful_attempts > (attempts * 0.7):
            self.log_result(
                "Registration Rate Limiting",
                True,
                f"Weak/no rate limiting: {successful_attempts}/{attempts} succeeded"
            )
            return True
        else:
            self.log_result(
                "Registration Rate Limiting",
                False,
                f"Rate limiting active: {blocked_attempts}/{attempts} blocked"
            )
            return False

    def test_token_enumeration_rate_limit(self, attempts=30):
        """Test growth hacking token endpoint for rate limiting"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 3: Token Enumeration Rate Limiting")
        print(f"{'='*60}{Style.RESET_ALL}\n")
        print(f"Attempting {attempts} rapid token attempts...\n")

        successful_attempts = 0
        blocked_attempts = 0

        for i in range(attempts):
            try:
                response = requests.post(
                    f"{self.base_url}/welcome/set-password",
                    json={
                        "token": f"token-test-{i}",
                        "password": "Password123",
                        "password_confirmation": "Password123"
                    },
                    headers={'Content-Type': 'application/json'},
                    timeout=5
                )

                if response.status_code == 429:
                    blocked_attempts += 1
                    if i < 10:
                        print(f"{Fore.GREEN}Attempt {i+1}: BLOCKED (429){Style.RESET_ALL}")
                else:
                    successful_attempts += 1
                    if i < 10:
                        print(f"{Fore.YELLOW}Attempt {i+1}: Allowed ({response.status_code}){Style.RESET_ALL}")

                time.sleep(0.1)

            except Exception as e:
                if i < 5:
                    print(f"{Fore.RED}Attempt {i+1}: Error{Style.RESET_ALL}")

        print(f"\n{Fore.CYAN}Results:{Style.RESET_ALL}")
        print(f"  Successful: {successful_attempts}/{attempts}")
        print(f"  Blocked: {blocked_attempts}/{attempts}\n")

        if successful_attempts > (attempts * 0.8):
            self.log_result(
                "Token Enumeration Rate Limiting",
                True,
                f"No rate limiting on token endpoint!"
            )
            return True
        else:
            self.log_result(
                "Token Enumeration Rate Limiting",
                False,
                "Rate limiting active"
            )
            return False

    def test_parallel_requests(self, endpoint="/api/auth/login", threads=10):
        """Test concurrent request handling"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 4: Parallel Request Handling")
        print(f"{'='*60}{Style.RESET_ALL}\n")
        print(f"Sending {threads} parallel requests to {endpoint}...\n")

        def make_request(i):
            try:
                start = time.time()
                response = requests.post(
                    f"{self.base_url}{endpoint}",
                    json={
                        "email": f"test{i}@example.com",
                        "password": "wrongpass"
                    },
                    headers={'Content-Type': 'application/json'},
                    timeout=10
                )
                elapsed = time.time() - start
                return (response.status_code, elapsed)
            except Exception as e:
                return (None, str(e))

        results = []
        start_time = time.time()

        with ThreadPoolExecutor(max_workers=threads) as executor:
            futures = [executor.submit(make_request, i) for i in range(threads)]
            for future in as_completed(futures):
                results.append(future.result())

        total_time = time.time() - start_time

        success_count = sum(1 for r in results if r[0] and r[0] != 429)
        blocked_count = sum(1 for r in results if r[0] == 429)

        print(f"{Fore.CYAN}Results:{Style.RESET_ALL}")
        print(f"  Total Requests: {threads}")
        print(f"  Successful: {success_count}")
        print(f"  Blocked (429): {blocked_count}")
        print(f"  Total Time: {total_time:.2f}s\n")

        if success_count == threads:
            self.log_result(
                "Parallel Request Handling",
                True,
                f"All {threads} parallel requests succeeded (no rate limit)"
            )
            return True
        else:
            self.log_result(
                "Parallel Request Handling",
                False,
                f"{blocked_count}/{threads} requests blocked"
            )
            return False

    def run_all_tests(self):
        """Run all rate limiting tests"""
        print(f"\n{Fore.YELLOW}{'='*60}")
        print(f"RATE LIMITING VULNERABILITY TESTS")
        print(f"{'='*60}{Style.RESET_ALL}\n")
        print(f"Target: {self.base_url}")
        print(f"Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")

        # Run tests
        self.test_login_rate_limit(attempts=50)
        time.sleep(2)

        self.test_registration_rate_limit(attempts=20)
        time.sleep(2)

        self.test_token_enumeration_rate_limit(attempts=30)
        time.sleep(2)

        self.test_parallel_requests(threads=10)

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
            print(f"{Fore.RED}⚠️  WARNING: Rate limiting vulnerabilities detected!{Style.RESET_ALL}")
            print(f"{Fore.RED}   Attackers can perform brute force attacks.{Style.RESET_ALL}\n")
        else:
            print(f"{Fore.GREEN}✓ All rate limiting tests passed{Style.RESET_ALL}\n")

        return self.results

if __name__ == "__main__":
    import sys
    import os
    from dotenv import load_dotenv

    load_dotenv()

    base_url = os.getenv('BASE_URL', 'https://evenleads.com')

    if len(sys.argv) > 1:
        base_url = sys.argv[1]

    tester = RateLimitTester(base_url)
    results = tester.run_all_tests()

    with open('results_rate_limiting.json', 'w') as f:
        json.dump(results, f, indent=2)
    print(f"Results saved to: results_rate_limiting.json")
