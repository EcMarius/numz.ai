# Quick Start Guide - Security Testing

## 🚀 5-Minute Setup

### Step 1: Install Dependencies (30 seconds)

```bash
cd non-security
pip install -r requirements.txt
```

### Step 2: Configure Target (1 minute)

```bash
cp .env.example .env
nano .env
```

Change these values:
```env
BASE_URL=https://evenleads.com    # Your target URL
TEST_EMAIL=test@example.com        # Optional: for authenticated tests
TEST_PASSWORD=Password123          # Optional: matching password
```

### Step 3: Run Tests (2-3 minutes)

```bash
python run_all_tests.py
```

That's it! 🎉

---

## 📊 Reading Results

### Color Guide

- 🔴 **RED "VULNERABLE"** = Security issue found (BAD)
- 🟢 **GREEN "SECURE"** = Test passed (GOOD)
- 🟡 **YELLOW** = Information

### Example Output

```
[✗ VULNERABLE] Admin Escalation (Registration)
  └─ Registration succeeded with role_id=1! Account: test@example.com
```

This means: **CRITICAL - Fix immediately!**

```
[✓ SECURE] Login Rate Limiting
  └─ Rate limiting active: 45/50 blocked
```

This means: **Working correctly, no issue**

---

## 🎯 Common Test Commands

### Test Everything
```bash
python run_all_tests.py
```

### Test Specific Issues

**Mass Assignment (Admin escalation, bypass flags):**
```bash
python tests/test_mass_assignment.py
```

**Rate Limiting (Brute force protection):**
```bash
python tests/test_rate_limiting.py
```

**Admin Access (Authorization checks):**
```bash
python tests/test_admin_authorization.py
```

### Test Different Environment

```bash
# Production
python run_all_tests.py https://evenleads.com

# Staging
python run_all_tests.py https://staging.evenleads.com

# Local
python run_all_tests.py http://localhost:8000
```

---

## 🔧 Quick Fixes for Common Issues

### Issue 1: "ModuleNotFoundError: No module named 'requests'"

**Fix:**
```bash
pip install -r requirements.txt
```

### Issue 2: "Connection Error"

**Fix:**
```bash
# Check if site is up
curl -I https://evenleads.com

# If down, wait or use different environment
python run_all_tests.py https://staging.evenleads.com
```

### Issue 3: Tests taking too long

**Fix:** Edit test files and reduce attempts:
```python
# In test_rate_limiting.py, change:
def test_login_rate_limit(self, attempts=50):  # ← Change to 10
```

### Issue 4: "Token extraction failed"

**Fix:** Check login endpoint:
```bash
curl -X POST https://evenleads.com/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@example.com","password":"pass"}'
```

If response format is different, update token extraction in test files.

---

## 📈 What to Do With Results

### If Tests Show "VULNERABLE"

1. **Read the details**: Each vulnerability shows what went wrong
2. **Check severity**:
   - Admin escalation = CRITICAL (fix now!)
   - Rate limiting = HIGH (fix today)
3. **Refer to main report**: See `SECURITY_VULNERABILITIES_REPORT.md`
4. **Implement fixes**: Follow recommended fixes in report
5. **Re-test**: Run tests again to verify

### Example Fix Flow

1. **Test shows vulnerability:**
```
[✗ VULNERABLE] Admin Escalation (Registration)
  └─ Registration succeeded with role_id=1!
```

2. **Implement fix in code:**
```php
// In app/Models/User.php
protected $fillable = ['name', 'username', 'avatar']; // Removed role_id
protected $guarded = ['role_id', 'verified', 'bypass_*'];
```

3. **Re-test:**
```bash
python tests/test_mass_assignment.py
```

4. **Confirm fix:**
```
[✓ SECURE] Admin Escalation (Registration)
  └─ Registration rejected or failed
```

---

## 💡 Pro Tips

### Tip 1: Save Results

Tests automatically save to JSON:
- `results_mass_assignment.json`
- `results_rate_limiting.json`
- `results_admin_authorization.json`
- `security_test_results_TIMESTAMP.json` (complete report)

### Tip 2: Compare Before/After

```bash
# Before fixes
python run_all_tests.py > before.txt

# After fixes
python run_all_tests.py > after.txt

# Compare
diff before.txt after.txt
```

### Tip 3: Automate Regular Testing

```bash
# Create a weekly cron job
0 2 * * 0 cd /path/to/non-security && python run_all_tests.py
```

### Tip 4: Test in CI/CD

Add to your CI pipeline:
```yaml
# .github/workflows/security-test.yml
- name: Run Security Tests
  run: |
    cd non-security
    pip install -r requirements.txt
    python run_all_tests.py https://staging.yoursite.com
```

---

## 📞 Need Help?

1. **Check README.md** - Detailed documentation
2. **Check SECURITY_VULNERABILITIES_REPORT.md** - All vulnerabilities explained
3. **Review test source code** - Tests are well-commented
4. **Check JSON output** - Detailed results with timestamps

---

## ⚠️ Important Reminders

- ✅ Only test systems you own or have permission to test
- ✅ Test on staging/dev first, then production
- ✅ Notify your team before testing
- ✅ Run during low-traffic periods
- ✅ Save all test outputs
- ❌ Don't test without authorization
- ❌ Don't run on peak traffic times
- ❌ Don't forget to clean up test accounts

---

**Ready to secure your platform? Run the tests now!**

```bash
python run_all_tests.py
```
