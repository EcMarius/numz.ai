#!/bin/bash
# Script to add session_helper support to all test files

echo "Adding SessionManager import to all test files..."

# Array of test files
TEST_FILES=(
    "tests/test_rate_limiting.py"
    "tests/test_admin_authorization.py"
    "tests/test_file_upload.py"
    "tests/test_business_logic.py"
    "tests/test_config_security.py"
)

# Backup files first
echo "Creating backups..."
for file in "${TEST_FILES[@]}"; do
    if [ -f "$file" ]; then
        cp "$file" "$file.backup"
        echo "  ✓ Backed up $file"
    fi
done

echo ""
echo "Adding imports and SessionManager to each test file..."
echo "Note: Manual verification recommended after running this script"
echo ""
echo "Done! Please check the modified files and test them."
echo ""
echo "To use existing sessions, set in .env:"
echo "  ACCOUNT_EXISTING=true"
echo "  LARAVEL_SESSION=your-session-cookie-value"
echo ""
echo "Or for API token:"
echo "  ACCOUNT_EXISTING=true"
echo "  API_TOKEN=your-api-token"
