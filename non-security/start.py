#!/usr/bin/env python3
"""
EvenLeads Security Testing - Interactive Menu
One-stop interface for all security testing needs
"""

import sys
import os
import json
import subprocess
import time
from datetime import datetime
from pathlib import Path

# Check if colorama is available, if not, provide minimal fallback
try:
    from colorama import Fore, Style, init
    init(autoreset=True)
    COLORS_AVAILABLE = True
except ImportError:
    COLORS_AVAILABLE = False
    # Fallback color class
    class Fore:
        RED = YELLOW = GREEN = CYAN = MAGENTA = BLUE = WHITE = RESET = ''
    class Style:
        RESET_ALL = BRIGHT = ''

class SecurityTestMenu:
    def __init__(self):
        self.base_dir = Path(__file__).parent
        self.tests_dir = self.base_dir / 'tests'
        self.env_file = self.base_dir / '.env'
        self.config = self.load_config()

    def load_config(self):
        """Load configuration from .env file"""
        config = {
            'BASE_URL': 'https://evenleads.com',
            'TEST_EMAIL': '',
            'TEST_PASSWORD': '',
            'VERBOSE': 'true'
        }

        if self.env_file.exists():
            with open(self.env_file, 'r') as f:
                for line in f:
                    line = line.strip()
                    if line and not line.startswith('#') and '=' in line:
                        key, value = line.split('=', 1)
                        config[key.strip()] = value.strip().strip('"').strip("'")

        return config

    def save_config(self):
        """Save configuration to .env file"""
        with open(self.env_file, 'w') as f:
            f.write("# EvenLeads Security Testing Configuration\n")
            f.write(f"# Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n\n")
            for key, value in self.config.items():
                f.write(f"{key}={value}\n")

    def clear_screen(self):
        """Clear terminal screen"""
        os.system('clear' if os.name != 'nt' else 'cls')

    def print_banner(self):
        """Print main banner"""
        print(f"\n{Fore.CYAN}{'='*70}")
        print(f"{'='*70}")
        print(f"{Fore.YELLOW}  🔐 EVENLEADS SECURITY TESTING SUITE{Style.RESET_ALL}")
        print(f"{Fore.CYAN}  Interactive Penetration Testing Interface")
        print(f"{'='*70}")
        print(f"{'='*70}{Style.RESET_ALL}\n")

    def check_dependencies(self):
        """Check if required dependencies are installed"""
        # Map package names to import names
        required = {
            'requests': 'requests',
            'colorama': 'colorama',
            'tabulate': 'tabulate',
            'python-dotenv': 'dotenv'  # Package name vs import name
        }
        missing = []

        for package, import_name in required.items():
            try:
                __import__(import_name)
            except ImportError:
                missing.append(package)

        return missing

    def install_dependencies(self):
        """Install required dependencies"""
        self.clear_screen()
        self.print_banner()

        print(f"{Fore.YELLOW}Installing Dependencies...{Style.RESET_ALL}\n")

        missing = self.check_dependencies()

        if not missing:
            print(f"{Fore.GREEN}✓ All dependencies already installed!{Style.RESET_ALL}\n")
            input("Press Enter to continue...")
            return True

        print(f"Missing packages: {', '.join(missing)}\n")

        # Offer multiple installation methods
        print(f"{Fore.CYAN}Installation Method:{Style.RESET_ALL}")
        print(f"  1) pip3 install (recommended)")
        print(f"  2) pip3 install --user (user-level)")
        print(f"  3) pip3 install --break-system-packages (system-wide)")
        print(f"  4) Show manual installation command")
        print(f"  r) Return")

        choice = input(f"\n{Fore.GREEN}Select method (1-4): {Style.RESET_ALL}").strip()

        if choice == 'r':
            return False

        if choice == '4':
            print(f"\n{Fore.CYAN}Manual Installation:{Style.RESET_ALL}\n")
            print(f"Run this command in your terminal:\n")
            print(f"  {Fore.YELLOW}pip3 install -r requirements.txt --user{Style.RESET_ALL}\n")
            print(f"Or:\n")
            print(f"  {Fore.YELLOW}pip3 install requests colorama tabulate python-dotenv --user{Style.RESET_ALL}\n")
            input("Press Enter to continue...")
            return False

        print(f"\n{Fore.YELLOW}Installing...{Style.RESET_ALL}\n")

        try:
            if choice == '1':
                # Try pip3 directly
                subprocess.run(
                    ['pip3', 'install', '-r', 'requirements.txt'],
                    check=True
                )
            elif choice == '2':
                # User-level installation
                subprocess.run(
                    ['pip3', 'install', '-r', 'requirements.txt', '--user'],
                    check=True
                )
            elif choice == '3':
                # System-wide (break system packages)
                confirm = input(f"{Fore.RED}This may affect system Python. Continue? (y/N): {Style.RESET_ALL}").strip().lower()
                if confirm != 'y':
                    return False
                subprocess.run(
                    ['pip3', 'install', '-r', 'requirements.txt', '--break-system-packages'],
                    check=True
                )
            else:
                print(f"{Fore.RED}Invalid option{Style.RESET_ALL}")
                time.sleep(1)
                return self.install_dependencies()

            print(f"\n{Fore.GREEN}✓ Dependencies installed successfully!{Style.RESET_ALL}\n")
            print(f"{Fore.CYAN}Verifying installation...{Style.RESET_ALL}\n")

            # Re-check
            still_missing = self.check_dependencies()
            if still_missing:
                print(f"{Fore.YELLOW}Still missing: {', '.join(still_missing)}{Style.RESET_ALL}")
                print(f"\nYou may need to restart the terminal or use:\n")
                print(f"  pip3 install {' '.join(still_missing)} --user\n")
            else:
                print(f"{Fore.GREEN}✓ All packages verified!{Style.RESET_ALL}\n")

            input("Press Enter to continue...")
            return True

        except subprocess.CalledProcessError as e:
            print(f"\n{Fore.RED}✗ Installation failed{Style.RESET_ALL}\n")
            print(f"{Fore.YELLOW}Try running manually:{Style.RESET_ALL}")
            print(f"  pip3 install -r requirements.txt --user\n")
            input("Press Enter to continue...")
            return False
        except FileNotFoundError:
            print(f"\n{Fore.RED}✗ pip3 command not found{Style.RESET_ALL}\n")
            print(f"Please install pip3 or run:")
            print(f"  python3 -m pip install -r requirements.txt --user\n")
            input("Press Enter to continue...")
            return False

    def configure_settings(self):
        """Configure test settings"""
        self.clear_screen()
        self.print_banner()

        print(f"{Fore.CYAN}Configuration Settings{Style.RESET_ALL}\n")
        print(f"Current configuration:\n")

        for i, (key, value) in enumerate(self.config.items(), 1):
            display_value = '(not set)' if not value else value
            if 'PASSWORD' in key and value:
                display_value = '*' * 8
            print(f"  {i}. {key}: {Fore.YELLOW}{display_value}{Style.RESET_ALL}")

        print(f"\n{Fore.CYAN}Options:{Style.RESET_ALL}")
        print(f"  1-{len(self.config)}) Edit specific setting")
        print(f"  a) Auto-configure (use defaults)")
        print(f"  s) Save and return")
        print(f"  r) Return without saving")

        choice = input(f"\n{Fore.GREEN}Select option: {Style.RESET_ALL}").strip().lower()

        if choice == 'a':
            if not self.env_file.exists():
                subprocess.run(['cp', '.env.example', '.env'])
                self.config = self.load_config()
                print(f"\n{Fore.GREEN}✓ Configuration created from .env.example{Style.RESET_ALL}")
            else:
                print(f"\n{Fore.YELLOW}.env already exists{Style.RESET_ALL}")
            time.sleep(1)
            return self.configure_settings()

        elif choice == 's':
            self.save_config()
            print(f"\n{Fore.GREEN}✓ Configuration saved!{Style.RESET_ALL}")
            time.sleep(1)
            return True

        elif choice == 'r':
            return True

        elif choice.isdigit() and 1 <= int(choice) <= len(self.config):
            idx = int(choice) - 1
            key = list(self.config.keys())[idx]
            print(f"\n{Fore.CYAN}Enter new value for {key}:{Style.RESET_ALL}")
            print(f"Current: {self.config[key]}")
            new_value = input(f"New value (or Enter to skip): ").strip()

            if new_value:
                self.config[key] = new_value
                print(f"{Fore.GREEN}✓ Updated!{Style.RESET_ALL}")
            time.sleep(0.5)
            return self.configure_settings()

        else:
            return self.configure_settings()

    def select_tests(self):
        """Interactive test selection"""
        self.clear_screen()
        self.print_banner()

        print(f"{Fore.CYAN}Select Tests to Run{Style.RESET_ALL}\n")

        tests = [
            {
                'name': 'Mass Assignment Tests',
                'file': 'test_mass_assignment.py',
                'count': 5,
                'severity': 'CRITICAL',
                'desc': 'Admin escalation, bypass flags, trial manipulation'
            },
            {
                'name': 'Rate Limiting Tests',
                'file': 'test_rate_limiting.py',
                'count': 4,
                'severity': 'HIGH',
                'desc': 'Brute force protection, token enumeration'
            },
            {
                'name': 'Admin Authorization Tests',
                'file': 'test_admin_authorization.py',
                'count': 3,
                'severity': 'CRITICAL',
                'desc': 'Plugin upload RCE, admin endpoint access'
            },
            {
                'name': 'File Upload Tests',
                'file': 'test_file_upload.py',
                'count': 5,
                'severity': 'CRITICAL',
                'desc': 'PHP shells, XSS, malware upload'
            },
            {
                'name': 'Business Logic Tests',
                'file': 'test_business_logic.py',
                'count': 3,
                'severity': 'HIGH',
                'desc': 'Cache bypass, organization role manipulation'
            },
            {
                'name': 'Configuration Security Tests',
                'file': 'test_config_security.py',
                'count': 5,
                'severity': 'MEDIUM',
                'desc': 'Credentials exposure, debug mode, headers'
            }
        ]

        for i, test in enumerate(tests, 1):
            severity_color = Fore.RED if test['severity'] == 'CRITICAL' else (Fore.YELLOW if test['severity'] == 'HIGH' else Fore.CYAN)
            print(f"  {i}. {test['name']}")
            print(f"     {Fore.WHITE}({test['count']} tests | {severity_color}{test['severity']}{Style.RESET_ALL})")
            print(f"     {Fore.WHITE}{test['desc']}{Style.RESET_ALL}\n")

        print(f"{Fore.CYAN}Options:{Style.RESET_ALL}")
        print(f"  1-{len(tests)}) Run specific test suite")
        print(f"  a) Run ALL tests (recommended)")
        print(f"  c) Run CRITICAL only")
        print(f"  h) Run HIGH priority only")
        print(f"  r) Return to main menu")

        choice = input(f"\n{Fore.GREEN}Select option: {Style.RESET_ALL}").strip().lower()

        if choice == 'a':
            return self.run_all_tests()
        elif choice == 'c':
            return self.run_critical_tests()
        elif choice == 'h':
            return self.run_high_tests()
        elif choice == 'r':
            return
        elif choice.isdigit() and 1 <= int(choice) <= len(tests):
            idx = int(choice) - 1
            return self.run_single_test(tests[idx])
        else:
            print(f"\n{Fore.RED}Invalid option{Style.RESET_ALL}")
            time.sleep(1)
            return self.select_tests()

    def run_single_test(self, test_info):
        """Run a single test suite"""
        self.clear_screen()
        self.print_banner()

        print(f"{Fore.CYAN}Running: {test_info['name']}{Style.RESET_ALL}\n")
        print(f"File: {test_info['file']}")
        print(f"Tests: {test_info['count']}")
        print(f"Severity: {test_info['severity']}\n")

        print(f"{Fore.YELLOW}Starting test...{Style.RESET_ALL}\n")
        print("=" * 70 + "\n")

        try:
            # Set environment variables
            env = os.environ.copy()
            env.update({
                'BASE_URL': self.config['BASE_URL'],
                'TEST_EMAIL': self.config.get('TEST_EMAIL', ''),
                'TEST_PASSWORD': self.config.get('TEST_PASSWORD', ''),
            })

            # Run test
            result = subprocess.run(
                [sys.executable, str(self.tests_dir / test_info['file'])],
                env=env,
                cwd=str(self.base_dir)
            )

            print("\n" + "=" * 70)
            print(f"\n{Fore.GREEN}Test completed!{Style.RESET_ALL}")
            print(f"Exit code: {result.returncode}")

        except Exception as e:
            print(f"\n{Fore.RED}Error running test: {e}{Style.RESET_ALL}")

        print(f"\n{Fore.CYAN}Options:{Style.RESET_ALL}")
        print(f"  r) Run again")
        print(f"  v) View JSON results")
        print(f"  m) Return to menu")

        choice = input(f"\n{Fore.GREEN}Select option: {Style.RESET_ALL}").strip().lower()

        if choice == 'r':
            return self.run_single_test(test_info)
        elif choice == 'v':
            self.view_results()
        # Return to menu in all cases

    def run_all_tests(self):
        """Run all tests"""
        self.clear_screen()
        self.print_banner()

        print(f"{Fore.YELLOW}Running ALL Security Tests{Style.RESET_ALL}\n")
        print(f"Target: {Fore.CYAN}{self.config['BASE_URL']}{Style.RESET_ALL}")
        print(f"Total: 6 test suites, 25 tests\n")

        confirm = input(f"Continue? (y/N): ").strip().lower()

        if confirm != 'y':
            return

        print(f"\n{Fore.YELLOW}Starting comprehensive test...{Style.RESET_ALL}\n")
        print("=" * 70 + "\n")

        try:
            # Set environment variables
            env = os.environ.copy()
            env.update({
                'BASE_URL': self.config['BASE_URL'],
                'TEST_EMAIL': self.config.get('TEST_EMAIL', ''),
                'TEST_PASSWORD': self.config.get('TEST_PASSWORD', ''),
            })

            # Run main test runner
            result = subprocess.run(
                [sys.executable, 'run_all_tests.py'],
                env=env,
                cwd=str(self.base_dir)
            )

            print("\n" + "=" * 70)
            print(f"\n{Fore.GREEN}All tests completed!{Style.RESET_ALL}")

        except Exception as e:
            print(f"\n{Fore.RED}Error: {e}{Style.RESET_ALL}")

        input("\nPress Enter to continue...")

    def run_critical_tests(self):
        """Run only CRITICAL severity tests"""
        self.clear_screen()
        self.print_banner()

        print(f"{Fore.RED}Running CRITICAL Tests Only{Style.RESET_ALL}\n")

        critical_tests = [
            'test_mass_assignment.py',
            'test_file_upload.py',
            'test_admin_authorization.py'
        ]

        for test_file in critical_tests:
            print(f"{Fore.YELLOW}Running {test_file}...{Style.RESET_ALL}\n")
            print("-" * 70)

            env = os.environ.copy()
            env.update({
                'BASE_URL': self.config['BASE_URL'],
                'TEST_EMAIL': self.config.get('TEST_EMAIL', ''),
                'TEST_PASSWORD': self.config.get('TEST_PASSWORD', ''),
            })

            subprocess.run(
                [sys.executable, str(self.tests_dir / test_file)],
                env=env,
                cwd=str(self.base_dir)
            )

            print("\n" + "=" * 70 + "\n")
            time.sleep(1)

        input("\nPress Enter to continue...")

    def run_high_tests(self):
        """Run only HIGH severity tests"""
        self.clear_screen()
        self.print_banner()

        print(f"{Fore.YELLOW}Running HIGH Priority Tests{Style.RESET_ALL}\n")

        high_tests = [
            'test_rate_limiting.py',
            'test_business_logic.py',
            'test_config_security.py'
        ]

        for test_file in high_tests:
            print(f"{Fore.YELLOW}Running {test_file}...{Style.RESET_ALL}\n")
            print("-" * 70)

            env = os.environ.copy()
            env.update({
                'BASE_URL': self.config['BASE_URL'],
                'TEST_EMAIL': self.config.get('TEST_EMAIL', ''),
                'TEST_PASSWORD': self.config.get('TEST_PASSWORD', ''),
            })

            subprocess.run(
                [sys.executable, str(self.tests_dir / test_file)],
                env=env,
                cwd=str(self.base_dir)
            )

            print("\n" + "=" * 70 + "\n")
            time.sleep(1)

        input("\nPress Enter to continue...")

    def view_results(self):
        """View test results"""
        self.clear_screen()
        self.print_banner()

        print(f"{Fore.CYAN}Test Results{Style.RESET_ALL}\n")

        # Find all result JSON files
        result_files = list(self.base_dir.glob('results_*.json'))
        result_files += list(self.base_dir.glob('security_test_results_*.json'))

        if not result_files:
            print(f"{Fore.YELLOW}No result files found.{Style.RESET_ALL}")
            print(f"Run some tests first!\n")
            input("Press Enter to continue...")
            return

        # Sort by modification time (newest first)
        result_files.sort(key=lambda x: x.stat().st_mtime, reverse=True)

        print(f"Found {len(result_files)} result file(s):\n")

        for i, file in enumerate(result_files, 1):
            mod_time = datetime.fromtimestamp(file.stat().st_mtime)
            size = file.stat().st_size
            print(f"  {i}. {file.name}")
            print(f"     {Fore.WHITE}Modified: {mod_time.strftime('%Y-%m-%d %H:%M:%S')} | Size: {size} bytes{Style.RESET_ALL}\n")

        print(f"{Fore.CYAN}Options:{Style.RESET_ALL}")
        print(f"  1-{len(result_files)}) View specific result")
        print(f"  l) View latest result")
        print(f"  d) Delete all results")
        print(f"  r) Return to menu")

        choice = input(f"\n{Fore.GREEN}Select option: {Style.RESET_ALL}").strip().lower()

        if choice == 'l' and result_files:
            self.display_result_file(result_files[0])
        elif choice == 'd':
            confirm = input(f"\n{Fore.RED}Delete all result files? (y/N): {Style.RESET_ALL}").strip().lower()
            if confirm == 'y':
                for file in result_files:
                    file.unlink()
                print(f"\n{Fore.GREEN}✓ All results deleted{Style.RESET_ALL}")
                time.sleep(1)
            return
        elif choice == 'r':
            return
        elif choice.isdigit() and 1 <= int(choice) <= len(result_files):
            idx = int(choice) - 1
            self.display_result_file(result_files[idx])
        else:
            return self.view_results()

    def display_result_file(self, file_path):
        """Display content of a result file"""
        self.clear_screen()
        self.print_banner()

        print(f"{Fore.CYAN}Results: {file_path.name}{Style.RESET_ALL}\n")

        try:
            with open(file_path, 'r') as f:
                data = json.load(f)

            # Handle different result formats
            if isinstance(data, dict) and 'test_suites' in data:
                # Complete report from run_all_tests.py
                print(f"{Fore.YELLOW}Complete Security Report{Style.RESET_ALL}\n")
                print(f"Target: {data.get('target', 'N/A')}")
                print(f"Timestamp: {data.get('timestamp', 'N/A')}")
                print(f"Duration: {data.get('duration', 0):.2f}s\n")

                summary = data.get('summary', {})
                print(f"{Fore.CYAN}Summary:{Style.RESET_ALL}")
                print(f"  Total Tests: {summary.get('total_tests', 0)}")
                print(f"  Vulnerable: {Fore.RED}{summary.get('vulnerable', 0)}{Style.RESET_ALL}")
                print(f"  Secure: {Fore.GREEN}{summary.get('secure', 0)}{Style.RESET_ALL}\n")

                # Show per-suite results
                print(f"{Fore.CYAN}Test Suites:{Style.RESET_ALL}\n")
                for suite_name, suite_data in data.get('test_suites', {}).items():
                    vuln = suite_data.get('vulnerable_count', 0)
                    total = suite_data.get('total_count', 0)
                    status = f"{Fore.RED}VULNERABLE" if vuln > 0 else f"{Fore.GREEN}SECURE"
                    print(f"  {suite_name}: {vuln}/{total} vulnerable {status}{Style.RESET_ALL}")

            elif isinstance(data, list):
                # Individual test results
                print(f"{Fore.CYAN}Test Results:{Style.RESET_ALL}\n")

                vulnerable = [r for r in data if r.get('success')]
                secure = [r for r in data if not r.get('success')]

                print(f"Total: {len(data)} tests")
                print(f"Vulnerable: {Fore.RED}{len(vulnerable)}{Style.RESET_ALL}")
                print(f"Secure: {Fore.GREEN}{len(secure)}{Style.RESET_ALL}\n")

                if vulnerable:
                    print(f"{Fore.RED}Vulnerable Tests:{Style.RESET_ALL}\n")
                    for i, result in enumerate(vulnerable, 1):
                        print(f"  {i}. {result.get('test', 'Unknown')}")
                        print(f"     {Fore.WHITE}{result.get('details', '')}{Style.RESET_ALL}\n")

            else:
                print(f"{Fore.YELLOW}Raw JSON:{Style.RESET_ALL}\n")
                print(json.dumps(data, indent=2))

        except Exception as e:
            print(f"{Fore.RED}Error reading file: {e}{Style.RESET_ALL}")

        print("\n" + "=" * 70)
        input("\nPress Enter to continue...")

    def view_documentation(self):
        """View available documentation"""
        self.clear_screen()
        self.print_banner()

        print(f"{Fore.CYAN}Documentation{Style.RESET_ALL}\n")

        docs = [
            ('QUICK_START.md', '5-minute setup guide'),
            ('README.md', 'Complete documentation'),
            ('TEST_SUMMARY.md', 'Test coverage and results guide'),
            ('VULNERABILITY_TEST_MAPPING.md', 'Vulnerability to test mapping'),
            ('COVERAGE_CHECKLIST.md', 'Coverage verification'),
            ('INDEX.md', 'Overview and index'),
            ('../SECURITY_VULNERABILITIES_REPORT.md', 'Detailed vulnerability report')
        ]

        for i, (file, desc) in enumerate(docs, 1):
            exists = (self.base_dir / file).exists() if not file.startswith('..') else (self.base_dir.parent / file.replace('../', '')).exists()
            status = f"{Fore.GREEN}✓" if exists else f"{Fore.RED}✗"
            print(f"  {i}. {file}")
            print(f"     {status} {desc}{Style.RESET_ALL}\n")

        print(f"{Fore.CYAN}Options:{Style.RESET_ALL}")
        print(f"  1-{len(docs)}) Open document")
        print(f"  r) Return to menu")

        choice = input(f"\n{Fore.GREEN}Select option: {Style.RESET_ALL}").strip().lower()

        if choice == 'r':
            return
        elif choice.isdigit() and 1 <= int(choice) <= len(docs):
            idx = int(choice) - 1
            doc_path = docs[idx][0]

            if doc_path.startswith('..'):
                full_path = self.base_dir.parent / doc_path.replace('../', '')
            else:
                full_path = self.base_dir / doc_path

            if full_path.exists():
                # Try to open with system default
                try:
                    if sys.platform == 'darwin':  # macOS
                        subprocess.run(['open', str(full_path)])
                    elif sys.platform == 'linux':
                        subprocess.run(['xdg-open', str(full_path)])
                    else:  # Windows
                        os.startfile(str(full_path))
                    print(f"\n{Fore.GREEN}✓ Opened {doc_path}{Style.RESET_ALL}")
                except:
                    # Fallback: display in terminal
                    self.display_file(full_path)
            else:
                print(f"\n{Fore.RED}File not found: {doc_path}{Style.RESET_ALL}")

            time.sleep(1)
            return self.view_documentation()

    def display_file(self, file_path):
        """Display file content in terminal"""
        self.clear_screen()
        print(f"{Fore.CYAN}File: {file_path.name}{Style.RESET_ALL}\n")
        print("=" * 70 + "\n")

        try:
            with open(file_path, 'r') as f:
                content = f.read()
            print(content)
        except Exception as e:
            print(f"{Fore.RED}Error reading file: {e}{Style.RESET_ALL}")

        print("\n" + "=" * 70)
        input("\nPress Enter to continue...")

    def show_system_info(self):
        """Show system and configuration info"""
        self.clear_screen()
        self.print_banner()

        print(f"{Fore.CYAN}System Information{Style.RESET_ALL}\n")

        # Python version
        print(f"Python: {sys.version.split()[0]}")
        print(f"Platform: {sys.platform}")

        # Check dependencies
        missing_deps = self.check_dependencies()
        if missing_deps:
            print(f"Dependencies: {Fore.RED}Missing: {', '.join(missing_deps)}{Style.RESET_ALL}")
        else:
            print(f"Dependencies: {Fore.GREEN}✓ All installed{Style.RESET_ALL}")

        # Configuration
        print(f"\n{Fore.CYAN}Configuration:{Style.RESET_ALL}\n")
        print(f"  Base URL: {self.config['BASE_URL']}")
        print(f"  Test Email: {self.config.get('TEST_EMAIL', '(not set)')}")
        print(f"  .env file: {Fore.GREEN}✓ Exists" if self.env_file.exists() else f"{Fore.YELLOW}! Not found{Style.RESET_ALL}")

        # Test files
        print(f"\n{Fore.CYAN}Test Scripts:{Style.RESET_ALL}\n")
        test_files = list(self.tests_dir.glob('test_*.py'))
        for test in sorted(test_files):
            print(f"  ✓ {test.name}")

        # Result files
        result_files = list(self.base_dir.glob('results_*.json'))
        result_files += list(self.base_dir.glob('security_test_results_*.json'))

        print(f"\n{Fore.CYAN}Test Results:{Style.RESET_ALL}\n")
        if result_files:
            print(f"  {len(result_files)} result file(s) available")
            latest = max(result_files, key=lambda x: x.stat().st_mtime)
            print(f"  Latest: {latest.name}")
        else:
            print(f"  {Fore.YELLOW}No results yet (run tests first){Style.RESET_ALL}")

        input("\nPress Enter to continue...")

    def show_help(self):
        """Show help and usage info"""
        self.clear_screen()
        self.print_banner()

        print(f"{Fore.CYAN}Help & Usage{Style.RESET_ALL}\n")

        print(f"{Fore.YELLOW}How to Use This Tool:{Style.RESET_ALL}\n")
        print(f"1. {Fore.GREEN}Install Dependencies{Style.RESET_ALL}")
        print(f"   Choose option 1 from main menu to install required packages\n")

        print(f"2. {Fore.GREEN}Configure Settings{Style.RESET_ALL}")
        print(f"   Choose option 2 to set your target URL and credentials\n")

        print(f"3. {Fore.GREEN}Run Tests{Style.RESET_ALL}")
        print(f"   Choose option 3 to select and run security tests\n")

        print(f"4. {Fore.GREEN}View Results{Style.RESET_ALL}")
        print(f"   Choose option 4 to view test results and reports\n")

        print(f"{Fore.YELLOW}Understanding Results:{Style.RESET_ALL}\n")
        print(f"  {Fore.RED}[✗ VULNERABLE]{Style.RESET_ALL} - Security issue found (needs fixing)")
        print(f"  {Fore.GREEN}[✓ SECURE]{Style.RESET_ALL} - Test passed (no issue)")
        print(f"  {Fore.YELLOW}[! WARNING]{Style.RESET_ALL} - Potential issue (review needed)\n")

        print(f"{Fore.YELLOW}Quick Commands:{Style.RESET_ALL}\n")
        print(f"  Run all tests:        python run_all_tests.py")
        print(f"  Run single test:      python tests/test_mass_assignment.py")
        print(f"  Install deps:         pip install -r requirements.txt")
        print(f"  Setup config:         cp .env.example .env\n")

        print(f"{Fore.YELLOW}Documentation Files:{Style.RESET_ALL}\n")
        print(f"  QUICK_START.md       - 5-minute guide")
        print(f"  README.md            - Full documentation")
        print(f"  TEST_SUMMARY.md      - Coverage matrix")
        print(f"  INDEX.md             - Overview\n")

        input("Press Enter to continue...")

    def main_menu(self):
        """Display main menu"""
        while True:
            self.clear_screen()
            self.print_banner()

            print(f"{Fore.CYAN}Main Menu{Style.RESET_ALL}\n")

            # Show status indicators
            deps_ok = len(self.check_dependencies()) == 0
            config_ok = self.env_file.exists()

            deps_status = f"{Fore.GREEN}✓" if deps_ok else f"{Fore.RED}✗"
            config_status = f"{Fore.GREEN}✓" if config_ok else f"{Fore.YELLOW}!"

            print(f"  1. {deps_status} Install Dependencies{Style.RESET_ALL}")
            print(f"  2. {config_status} Configure Settings (Target URL, Credentials){Style.RESET_ALL}")
            print(f"  3. 🧪 Run Security Tests")
            print(f"  4. 📊 View Test Results")
            print(f"  5. 📚 View Documentation")
            print(f"  6. ℹ️  System Information")
            print(f"  7. ❓ Help")
            print(f"  8. 🚪 Exit")

            print(f"\n{Fore.CYAN}Quick Info:{Style.RESET_ALL}")
            print(f"  Target: {Fore.YELLOW}{self.config['BASE_URL']}{Style.RESET_ALL}")
            print(f"  Status: {deps_status} Dependencies | {config_status} Configuration{Style.RESET_ALL}")

            choice = input(f"\n{Fore.GREEN}Select option (1-8): {Style.RESET_ALL}").strip()

            if choice == '1':
                self.install_dependencies()
            elif choice == '2':
                self.configure_settings()
            elif choice == '3':
                if not deps_ok:
                    print(f"\n{Fore.RED}Please install dependencies first (option 1){Style.RESET_ALL}")
                    time.sleep(2)
                else:
                    self.select_tests()
            elif choice == '4':
                self.view_results()
            elif choice == '5':
                self.view_documentation()
            elif choice == '6':
                self.show_system_info()
            elif choice == '7':
                self.show_help()
            elif choice == '8':
                self.clear_screen()
                print(f"\n{Fore.CYAN}Thank you for using EvenLeads Security Testing Suite!{Style.RESET_ALL}\n")
                sys.exit(0)
            else:
                print(f"\n{Fore.RED}Invalid option{Style.RESET_ALL}")
                time.sleep(1)

def main():
    """Main entry point"""
    try:
        menu = SecurityTestMenu()
        menu.main_menu()
    except KeyboardInterrupt:
        print(f"\n\n{Fore.YELLOW}Interrupted by user{Style.RESET_ALL}\n")
        sys.exit(0)
    except Exception as e:
        print(f"\n{Fore.RED}Fatal error: {e}{Style.RESET_ALL}\n")
        sys.exit(1)

if __name__ == "__main__":
    main()
