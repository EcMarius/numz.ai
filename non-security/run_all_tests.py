#!/usr/bin/env python3
"""
EvenLeads Security Test Suite
Runs all penetration tests and generates a comprehensive report
"""

import sys
import os
import json
import time
from datetime import datetime
from colorama import Fore, Style, init
from tabulate import tabulate

# Add tests directory to path
sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'tests'))

from test_mass_assignment import MassAssignmentTester
from test_rate_limiting import RateLimitTester
from test_admin_authorization import AdminAuthTester
from test_file_upload import FileUploadTester
from test_business_logic import BusinessLogicTester
from test_config_security import ConfigSecurityTester

init(autoreset=True)

class SecurityTestRunner:
    def __init__(self, base_url, test_email=None, test_password=None):
        self.base_url = base_url
        self.test_email = test_email
        self.test_password = test_password
        self.all_results = {}
        self.start_time = None
        self.end_time = None

    def print_banner(self):
        """Print test suite banner"""
        print(f"\n{Fore.YELLOW}{'='*70}")
        print(f"{'='*70}")
        print(f"  EVENLEADS SECURITY TESTING SUITE")
        print(f"  Authorized Penetration Testing")
        print(f"{'='*70}")
        print(f"{'='*70}{Style.RESET_ALL}\n")
        print(f"Target: {Fore.CYAN}{self.base_url}{Style.RESET_ALL}")
        print(f"Time:   {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")

    def run_test_suite(self, suite_name, tester, run_method, *args):
        """Run a test suite and collect results"""
        print(f"\n{Fore.MAGENTA}{'#'*70}")
        print(f"  RUNNING: {suite_name}")
        print(f"{'#'*70}{Style.RESET_ALL}\n")

        suite_start = time.time()
        results = run_method(*args)
        suite_duration = time.time() - suite_start

        self.all_results[suite_name] = {
            'results': results,
            'duration': suite_duration,
            'vulnerable_count': sum(1 for r in results if r['success']),
            'total_count': len(results)
        }

        print(f"\n{Fore.MAGENTA}Suite completed in {suite_duration:.2f}s{Style.RESET_ALL}\n")
        time.sleep(2)

    def generate_summary_report(self):
        """Generate comprehensive summary report"""
        print(f"\n{Fore.YELLOW}{'='*70}")
        print(f"{'='*70}")
        print(f"  FINAL SECURITY REPORT")
        print(f"{'='*70}")
        print(f"{'='*70}{Style.RESET_ALL}\n")

        # Overall statistics
        total_tests = sum(suite['total_count'] for suite in self.all_results.values())
        total_vulnerable = sum(suite['vulnerable_count'] for suite in self.all_results.values())
        total_secure = total_tests - total_vulnerable
        total_duration = self.end_time - self.start_time

        print(f"{Fore.CYAN}Overall Statistics:{Style.RESET_ALL}")
        print(f"  Total Test Suites: {len(self.all_results)}")
        print(f"  Total Tests Run:   {total_tests}")
        print(f"  Vulnerable:        {Fore.RED}{total_vulnerable}{Style.RESET_ALL}")
        print(f"  Secure:            {Fore.GREEN}{total_secure}{Style.RESET_ALL}")
        print(f"  Duration:          {total_duration:.2f}s\n")

        # Per-suite breakdown
        print(f"{Fore.CYAN}Test Suite Breakdown:{Style.RESET_ALL}\n")

        table_data = []
        for suite_name, data in self.all_results.items():
            vulnerable_pct = (data['vulnerable_count'] / data['total_count'] * 100) if data['total_count'] > 0 else 0

            status = f"{Fore.GREEN}PASS" if data['vulnerable_count'] == 0 else f"{Fore.RED}FAIL"

            table_data.append([
                suite_name,
                data['total_count'],
                f"{Fore.RED}{data['vulnerable_count']}{Style.RESET_ALL}",
                f"{vulnerable_pct:.1f}%",
                f"{data['duration']:.1f}s",
                f"{status}{Style.RESET_ALL}"
            ])

        print(tabulate(
            table_data,
            headers=['Test Suite', 'Tests', 'Vulnerable', '%', 'Time', 'Status'],
            tablefmt='grid'
        ))
        print()

        # Detailed vulnerabilities
        if total_vulnerable > 0:
            print(f"\n{Fore.RED}{'='*70}")
            print(f"  VULNERABILITIES DETECTED")
            print(f"{'='*70}{Style.RESET_ALL}\n")

            vuln_number = 1
            for suite_name, data in self.all_results.items():
                vulnerable_tests = [r for r in data['results'] if r['success']]

                if vulnerable_tests:
                    print(f"{Fore.YELLOW}[{suite_name}]{Style.RESET_ALL}")
                    for test in vulnerable_tests:
                        print(f"  {vuln_number}. {Fore.RED}{test['test']}{Style.RESET_ALL}")
                        print(f"     └─ {test['details']}")
                        vuln_number += 1
                    print()

            print(f"{Fore.RED}⚠️  CRITICAL: {total_vulnerable} security vulnerabilities found!{Style.RESET_ALL}")
            print(f"{Fore.RED}   Immediate action required to secure the platform.{Style.RESET_ALL}\n")

        else:
            print(f"\n{Fore.GREEN}{'='*70}")
            print(f"  ✓ ALL TESTS PASSED")
            print(f"{'='*70}{Style.RESET_ALL}\n")
            print(f"{Fore.GREEN}No vulnerabilities detected in tested areas.{Style.RESET_ALL}\n")

        # Recommendations
        if total_vulnerable > 0:
            print(f"{Fore.CYAN}{'='*70}")
            print(f"  RECOMMENDED ACTIONS")
            print(f"{'='*70}{Style.RESET_ALL}\n")

            recommendations = []

            for suite_name, data in self.all_results.items():
                vulnerable_tests = [r for r in data['results'] if r['success']]

                if "Mass Assignment" in suite_name and vulnerable_tests:
                    recommendations.append("1. Remove sensitive fields from User model $fillable array")
                    recommendations.append("2. Use $guarded to protect role_id, verified, bypass_* fields")

                if "Rate Limiting" in suite_name and vulnerable_tests:
                    recommendations.append("3. Implement rate limiting on /api/auth/login endpoint")
                    recommendations.append("4. Add throttling to registration and token endpoints")

                if "Admin Authorization" in suite_name and vulnerable_tests:
                    recommendations.append("5. Add admin role checks to all admin endpoints")
                    recommendations.append("6. Implement proper authorization middleware")

            for rec in set(recommendations):
                print(f"  {rec}")
            print()

    def save_results(self):
        """Save results to JSON file"""
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f'security_test_results_{timestamp}.json'

        report = {
            'target': self.base_url,
            'timestamp': datetime.now().isoformat(),
            'duration': self.end_time - self.start_time,
            'summary': {
                'total_tests': sum(suite['total_count'] for suite in self.all_results.values()),
                'vulnerable': sum(suite['vulnerable_count'] for suite in self.all_results.values()),
                'secure': sum(suite['total_count'] - suite['vulnerable_count'] for suite in self.all_results.values())
            },
            'test_suites': self.all_results
        }

        with open(filename, 'w') as f:
            json.dump(report, f, indent=2)

        print(f"{Fore.GREEN}✓ Full report saved to: {filename}{Style.RESET_ALL}\n")

    def run_all_tests(self):
        """Run all test suites"""
        self.start_time = time.time()
        self.print_banner()

        try:
            # Test Suite 1: Mass Assignment Vulnerabilities
            mass_tester = MassAssignmentTester(self.base_url)
            self.run_test_suite(
                "Mass Assignment Vulnerabilities",
                mass_tester,
                mass_tester.run_all_tests,
                self.test_email,
                self.test_password
            )

            # Test Suite 2: Rate Limiting
            rate_tester = RateLimitTester(self.base_url)
            self.run_test_suite(
                "Rate Limiting",
                rate_tester,
                rate_tester.run_all_tests
            )

            # Test Suite 3: Admin Authorization
            admin_tester = AdminAuthTester(self.base_url)
            self.run_test_suite(
                "Admin Authorization",
                admin_tester,
                admin_tester.run_all_tests,
                None  # Will create its own test account
            )

            # Test Suite 4: File Upload Security
            file_tester = FileUploadTester(self.base_url)
            self.run_test_suite(
                "File Upload Security",
                file_tester,
                file_tester.run_all_tests
            )

            # Test Suite 5: Business Logic
            business_tester = BusinessLogicTester(self.base_url)
            self.run_test_suite(
                "Business Logic",
                business_tester,
                business_tester.run_all_tests,
                self.test_email,
                self.test_password
            )

            # Test Suite 6: Configuration Security
            config_tester = ConfigSecurityTester(self.base_url)
            self.run_test_suite(
                "Configuration Security",
                config_tester,
                config_tester.run_all_tests,
                self.test_email,
                self.test_password
            )

        except KeyboardInterrupt:
            print(f"\n\n{Fore.YELLOW}Tests interrupted by user{Style.RESET_ALL}\n")
        except Exception as e:
            print(f"\n\n{Fore.RED}Error running tests: {str(e)}{Style.RESET_ALL}\n")
        finally:
            self.end_time = time.time()

        # Generate report
        self.generate_summary_report()
        self.save_results()

        # Return exit code based on vulnerabilities
        total_vulnerable = sum(suite['vulnerable_count'] for suite in self.all_results.values())
        return 1 if total_vulnerable > 0 else 0

def main():
    """Main entry point"""
    from dotenv import load_dotenv

    # Load environment variables
    load_dotenv()

    base_url = os.getenv('BASE_URL', 'https://evenleads.com')
    test_email = os.getenv('TEST_EMAIL')
    test_password = os.getenv('TEST_PASSWORD')

    # Parse command line arguments
    if len(sys.argv) > 1:
        if sys.argv[1] in ['-h', '--help']:
            print(f"""
{Fore.CYAN}EvenLeads Security Test Suite{Style.RESET_ALL}

Usage:
  python run_all_tests.py [BASE_URL]

Arguments:
  BASE_URL    Target URL (default: from .env or https://evenleads.com)

Environment Variables (.env file):
  BASE_URL         Target URL
  TEST_EMAIL       Test account email (optional)
  TEST_PASSWORD    Test account password (optional)

Examples:
  python run_all_tests.py
  python run_all_tests.py https://staging.evenleads.com

Output:
  - Console: Detailed test results with colors
  - JSON: security_test_results_TIMESTAMP.json
            """)
            return 0
        else:
            base_url = sys.argv[1]

    # Confirm before starting
    print(f"\n{Fore.YELLOW}Target: {base_url}{Style.RESET_ALL}")
    print(f"\nThis will run penetration tests against the target.")
    confirm = input(f"Continue? (y/N): ")

    if confirm.lower() != 'y':
        print(f"\n{Fore.YELLOW}Tests cancelled{Style.RESET_ALL}\n")
        return 0

    # Run tests
    runner = SecurityTestRunner(base_url, test_email, test_password)
    exit_code = runner.run_all_tests()

    return exit_code

if __name__ == "__main__":
    sys.exit(main())
