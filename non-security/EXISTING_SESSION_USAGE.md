# Using Existing Browser Sessions for Security Tests

## Overview

The security test suite now supports using existing browser sessions instead of requiring programmatic login. This allows you to:

- **Test faster** - Skip login overhead
- **Use real sessions** - Test with actual logged-in users
- **Test OAuth** - Use social login accounts
- **Test roles** - Use accounts with specific permissions

## Quick Start

### 1. Log In to Your Application

Open your application in a browser and log in with the account you want to test with.

### 2. Get Your Session Cookie

**Chrome/Edge:**
1. Press `F12` to open Developer Tools
2. Go to **Application** tab
3. Expand **Cookies** in left sidebar
4. Click on your domain
5. Find the cookie named `laravel_session` or `evenleads_session`
6. **Copy the Value** (long random string)

**Firefox:**
1. Press `F12` to open Developer Tools
2. Go to **Storage** tab
3. Expand **Cookies** in left sidebar
4. Click on your domain
5. Find the `laravel_session` cookie
6. **Copy the Value**

### 3. Set Environment Variables

Create a `.env` file or export environment variables:

```bash
# Enable existing session mode
export ACCOUNT_EXISTING=true

# Set your session cookie
export LARAVEL_SESSION="your-copied-session-value-here"

# Optional: XSRF token for extra security
export XSRF_TOKEN="your-xsrf-token"
```

Or add to `.env` file:
```
ACCOUNT_EXISTING=true
LARAVEL_SESSION=abc123xyz789...
```

### 4. Run Tests

```bash
python run_all_tests.py
```

That's it! The tests will use your existing browser session.

## Configuration Methods

### Method 1: Laravel Session Cookie (Recommended)

This is the simplest and most reliable method.

```bash
ACCOUNT_EXISTING=true
LARAVEL_SESSION=your-session-cookie-value
```

### Method 2: API Token

If you have a Bearer token:

```bash
ACCOUNT_EXISTING=true
API_TOKEN=your-bearer-token
```

### Method 3: Multiple Cookies (Advanced)

For complex setups with multiple cookies:

```bash
ACCOUNT_EXISTING=true
SESSION_COOKIES='{"laravel_session":"abc123","XSRF-TOKEN":"xyz789","other_cookie":"value"}'
```

## Usage Examples

### Example 1: Test with Admin Account

```bash
# Log in as admin in browser, get session cookie
export ACCOUNT_EXISTING=true
export LARAVEL_SESSION="admin-session-cookie-value"
python run_all_tests.py
```

### Example 2: Test with Regular User

```bash
# Log in as regular user, get session cookie
export ACCOUNT_EXISTING=true
export LARAVEL_SESSION="user-session-cookie-value"
python run_all_tests.py
```

### Example 3: Test Specific Suite

```bash
export ACCOUNT_EXISTING=true
export LARAVEL_SESSION="your-session-value"
python tests/test_mass_assignment.py
```

## Fallback to Traditional Login

If `ACCOUNT_EXISTING` is not set or `false`, tests will use traditional login:

```bash
# This still works as before
BASE_URL=https://evenleads.com
TEST_EMAIL=test@example.com
TEST_PASSWORD=password123
python run_all_tests.py
```

## Troubleshooting

### Session Expired

If you see authentication errors:
- Your session cookie may have expired
- Log in again and get a fresh cookie
- Check that you copied the complete cookie value

### Wrong User

If tests are running with wrong user:
- Make sure you're logged in as the correct user in browser
- Clear browser cookies and log in again
- Get a fresh session cookie

### No Session Data

If you see "No session data found":
- Check that `ACCOUNT_EXISTING=true` is set
- Verify you set either `LARAVEL_SESSION` or `API_TOKEN`
- Check for typos in environment variable names

### Permission Denied

If tests fail with permission errors:
- The logged-in user may not have required permissions
- Try logging in as admin or use `TEST_EMAIL`/`TEST_PASSWORD` instead

## Security Notes

⚠️ **Important Security Considerations:**

1. **Never commit session cookies to version control**
2. **Session cookies are as sensitive as passwords**
3. **Don't share session cookies**
4. **Use `.env` file and add it to `.gitignore`**
5. **Session cookies expire - get fresh ones regularly**
6. **Only use on authorized test environments**

## Implementation Details

The `session_helper.py` module handles:
- Loading session data from environment variables
- Making authenticated HTTP requests
- Supporting both cookie and token authentication
- Fallback to traditional login if needed

Modified test files:
- `test_mass_assignment.py` ✓
- `test_rate_limiting.py` (add session support)
- `test_admin_authorization.py` (add session support)
- `test_file_upload.py` (add session support)
- `test_business_logic.py` (add session support)
- `test_config_security.py` (add session support)

## Next Steps

To add session support to remaining test files:

1. Import SessionManager:
   ```python
   import sys
   import os
   sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
   from session_helper import SessionManager
   ```

2. Initialize in `__init__`:
   ```python
   def __init__(self, base_url):
       self.base_url = base_url
       self.session_manager = SessionManager(base_url)
   ```

3. Replace login code with:
   ```python
   success, auth_data, message = self.session_manager.login_if_needed(email, password)
   ```

4. Use authenticated requests:
   ```python
   response = self.session_manager.make_authenticated_request('post', url, json=data)
   ```

## Help

For detailed instructions on session setup:
```bash
python -c "from session_helper import print_session_setup_instructions; print_session_setup_instructions()"
```

For questions or issues, refer to `session_helper.py` source code.
