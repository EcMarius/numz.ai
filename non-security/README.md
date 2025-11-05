# EvenLeads Security Testing Suite

**Authorized Penetration Testing Tools**

This directory contains Python scripts for automated security testing of the EvenLeads platform. These tests identify vulnerabilities documented in `SECURITY_VULNERABILITIES_REPORT.md`.

## ⚠️ IMPORTANT

- **AUTHORIZED USE ONLY**: These tools are for testing YOUR OWN systems
- **DO NOT** use against systems you don't own or have permission to test
- Unauthorized testing may be illegal in your jurisdiction
- The owner of this repository is not responsible for misuse

## 📋 Prerequisites

- Python 3.7+
- pip (Python package manager)

## 🚀 Quick Start

### 1. Install Dependencies

```bash
cd non-security
pip install -r requirements.txt
```

### 2. Configure Target

Copy `.env.example` to `.env` and edit:

```bash
cp .env.example .env
nano .env
```

Edit `.env`:
```env
BASE_URL=https://evenleads.com
TEST_EMAIL=your-test@example.com
TEST_PASSWORD=YourTestPassword123
```

### 3. Run All Tests

```bash
python run_all_tests.py
```

Or test a specific URL:
```bash
python run_all_tests.py https://staging.evenleads.com
```

## 📁 Test Modules (6 Test Suites - 25 Total Tests)

### 1. Mass Assignment Tests (`test_mass_assignment.py`)
**Covers: Vulnerabilities #1, #3, #9 | 5 tests**

Tests if sensitive fields can be manipulated during registration/updates.

**What it tests:**
- ✅ Admin privilege escalation via registration (role_id)
- ✅ Bypass flags manipulation (bypass_campaign_sync_limit, etc.)
- ✅ Trial date manipulation (trial_ends_at)
- ✅ Email verification bypass (verified, email_verified_at)
- ✅ Profile update privilege escalation

**Run individually:**
```bash
python tests/test_mass_assignment.py
```

**Output:**
- Console: Detailed test results with pass/fail
- File: `results_mass_assignment.json`

---

### 2. Rate Limiting Tests (`test_rate_limiting.py`)
**Covers: Vulnerabilities #6, #11 | 4 tests**

Tests if endpoints have proper rate limiting to prevent brute force attacks.

**What it tests:**
- ✅ Login endpoint brute force (50 rapid attempts)
- ✅ Registration spam protection (20 rapid attempts)
- ✅ Token enumeration (30 rapid attempts)
- ✅ Parallel request handling (10 concurrent)

**Run individually:**
```bash
python tests/test_rate_limiting.py
```

**Output:**
- Console: Request counts, success/blocked ratios
- File: `results_rate_limiting.json`

---

### 3. Admin Authorization Tests (`test_admin_authorization.py`)
**Covers: Vulnerabilities #2, #7 | 3 tests**

Tests if regular users can access admin-only endpoints.

**What it tests:**
- ✅ Admin schema endpoint access
- ✅ Plugin upload authorization (RCE vector)
- ✅ Admin panel access control

**Run individually:**
```bash
python tests/test_admin_authorization.py
```

**Output:**
- Console: Access test results for each endpoint
- File: `results_admin_authorization.json`

---

### 4. File Upload Tests (`test_file_upload.py`)
**Covers: Vulnerability #4 | 5 tests**

Tests file upload validation and content security.

**What it tests:**
- ✅ PHP shell upload (disguised as .jpg)
- ✅ SVG XSS upload (embedded JavaScript)
- ✅ Executable file upload (.exe, malware)
- ✅ Double extension bypass (shell.php.jpg)
- ✅ Oversized file upload (20MB)

**Run individually:**
```bash
python tests/test_file_upload.py
```

**Output:**
- Console: Upload test results
- File: `results_file_upload.json`

---

### 5. Business Logic Tests (`test_business_logic.py`)
**Covers: Vulnerabilities #8, #9, #12 | 3 tests**

Tests business logic flaws and subscription bypass.

**What it tests:**
- ✅ Organization role manipulation (team_role)
- ✅ Plan cache timing attack detection
- ✅ Trial reset via profile update

**Run individually:**
```bash
python tests/test_business_logic.py
```

**Output:**
- Console: Business logic test results
- File: `results_business_logic.json`

---

### 6. Configuration Security Tests (`test_config_security.py`)
**Covers: Vulnerabilities #5, #10 | 5 tests**

Tests configuration and infrastructure security.

**What it tests:**
- ✅ .env.example file accessibility (credential exposure)
- ✅ Debug mode detection (stack trace exposure)
- ✅ Information disclosure (API endpoints)
- ✅ Security headers validation
- ✅ CORS misconfiguration

**Run individually:**
```bash
python tests/test_config_security.py
```

**Output:**
- Console: Configuration issue results
- File: `results_config_security.json`

## 📊 Understanding Results

### Console Output

Tests use color-coded output:
- 🔴 **RED "VULNERABLE"**: Security issue detected
- 🟢 **GREEN "SECURE"**: Test passed, no vulnerability
- 🟡 **YELLOW**: Information or warnings

### JSON Output Files

Each test module generates a JSON file with detailed results:

```json
{
  "test": "Admin Escalation (Registration)",
  "success": true,
  "details": "Registration succeeded with role_id=1!",
  "timestamp": "2025-10-29T12:34:56"
}
```

- `success: true` = Vulnerability found
- `success: false` = Security control working correctly

### Final Report

`run_all_tests.py` generates `security_test_results_TIMESTAMP.json`:

```json
{
  "target": "https://evenleads.com",
  "timestamp": "2025-10-29T12:34:56",
  "summary": {
    "total_tests": 12,
    "vulnerable": 8,
    "secure": 4
  },
  "test_suites": {
    "Mass Assignment Vulnerabilities": {
      "vulnerable_count": 5,
      "total_count": 5
    }
  }
}
```

## 🎯 Example Test Scenarios

### Scenario 1: Test Complete Platform

```bash
# Run all tests against production
python run_all_tests.py https://evenleads.com
```

Expected output:
```
============================================================
  FINAL SECURITY REPORT
============================================================

Overall Statistics:
  Total Tests Run:   12
  Vulnerable:        8
  Secure:            4

⚠️  CRITICAL: 8 security vulnerabilities found!
```

### Scenario 2: Test Specific Vulnerability

```bash
# Test only mass assignment issues
python tests/test_mass_assignment.py

# Test only rate limiting
python tests/test_rate_limiting.py
```

### Scenario 3: Test with Authentication

```bash
# Set credentials in .env
echo "TEST_EMAIL=mytest@example.com" >> .env
echo "TEST_PASSWORD=MyPassword123" >> .env

# Run tests that require authentication
python run_all_tests.py
```

## 🔧 Customization

### Adjust Test Parameters

Edit test files to change:

**Rate limiting thresholds:**
```python
# In test_rate_limiting.py
def test_login_rate_limit(self, attempts=50):  # Change to 100
```

**Request delays:**
```python
time.sleep(0.1)  # Increase to 0.5 for slower testing
```

### Add New Tests

Create new test file in `tests/`:

```python
#!/usr/bin/env python3
"""
Test Custom Vulnerability
"""

import requests
from colorama import Fore, Style, init

init(autoreset=True)

class CustomTester:
    def __init__(self, base_url):
        self.base_url = base_url
        self.results = []

    def test_my_vulnerability(self):
        """Test description"""
        print(f"\n{Fore.CYAN}TEST: My Custom Test{Style.RESET_ALL}")

        # Your test logic here
        response = requests.get(f"{self.base_url}/endpoint")

        if response.status_code == 200:
            print(f"{Fore.RED}VULNERABLE{Style.RESET_ALL}")
            return True
        else:
            print(f"{Fore.GREEN}SECURE{Style.RESET_ALL}")
            return False

    def run_all_tests(self):
        self.test_my_vulnerability()
        return self.results

if __name__ == "__main__":
    tester = CustomTester("https://evenleads.com")
    tester.run_all_tests()
```

## 📝 Interpreting Vulnerabilities

### CRITICAL Findings

If you see these, **fix immediately**:

1. **Admin Escalation**: Users can set `role_id=1` during registration
   - **Impact**: Full system compromise
   - **Fix**: Remove `role_id` from `$fillable` in User model

2. **Bypass Flags**: Users can set `bypass_campaign_sync_limit=true`
   - **Impact**: Unlimited free API usage
   - **Fix**: Remove all `bypass_*` fields from `$fillable`

3. **Plugin Upload**: Regular users can upload plugins
   - **Impact**: Remote Code Execution (RCE)
   - **Fix**: Add admin role check to plugin upload endpoint

### HIGH Findings

Fix within 24-48 hours:

1. **No Login Rate Limiting**: Brute force attacks possible
   - **Impact**: Account compromise
   - **Fix**: Add `->middleware('throttle:5,1')` to login route

2. **Admin Endpoints**: Regular users can access admin functions
   - **Impact**: Data manipulation
   - **Fix**: Add role checks to admin routes

### How to Verify Fixes

After implementing fixes:

1. Re-run affected tests:
```bash
python tests/test_mass_assignment.py
```

2. Check for "SECURE" results:
```
[✓ SECURE] Admin Escalation (Registration)
  └─ Registration rejected or failed
```

3. Run full suite to confirm:
```bash
python run_all_tests.py
```

Expected after fixes:
```
✓ ALL TESTS PASSED
No vulnerabilities detected in tested areas.
```

## 🛡️ Best Practices

### Before Testing

1. **Get Authorization**: Ensure you have written permission
2. **Use Test Environment**: Test on staging/dev first
3. **Backup Data**: Ensure you have backups before testing
4. **Notify Team**: Let your team know testing is happening

### During Testing

1. **Monitor Impact**: Watch server resources
2. **Test Off-Peak**: Run during low-traffic periods
3. **Gradual Escalation**: Start with gentle tests
4. **Document Findings**: Save all test outputs

### After Testing

1. **Clean Up**: Remove test accounts created
2. **Review Results**: Analyze JSON output files
3. **Prioritize Fixes**: Critical → High → Medium
4. **Verify Fixes**: Re-run tests after patching
5. **Update Documentation**: Document changes made

## 🔍 Troubleshooting

### "Connection Error"

```bash
# Check if target is accessible
curl -I https://evenleads.com

# Check if you have internet connection
ping google.com
```

### "ModuleNotFoundError"

```bash
# Reinstall dependencies
pip install -r requirements.txt --force-reinstall
```

### "Token Extraction Failed"

```bash
# Check login response format
curl -X POST https://evenleads.com/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@example.com","password":"pass"}'

# May need to update token extraction logic in tests
```

### Tests Running Too Slow

```bash
# Reduce number of attempts
# Edit test files and change:
attempts=50  # to
attempts=10
```

## 📚 Additional Resources

- **Main Report**: `../SECURITY_VULNERABILITIES_REPORT.md`
- **Environment Setup**: `.env.example`
- **Requirements**: `requirements.txt`

## 🤝 Contributing

To add new tests:

1. Create test file in `tests/` directory
2. Follow existing pattern (TestClass with run_all_tests method)
3. Use colorama for colored output
4. Save results to JSON file
5. Update this README with new test description

## ⚖️ Legal Disclaimer

These tools are provided for authorized security testing only. The authors assume no liability for misuse or damage caused by these tools. Always obtain proper authorization before testing any system you do not own.

## 📞 Support

For questions about these tests:
- Review `SECURITY_VULNERABILITIES_REPORT.md`
- Check test output JSON files
- Review test source code for implementation details

---

**Last Updated**: October 29, 2025
**Version**: 1.0.0
