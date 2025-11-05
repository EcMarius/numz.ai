#!/bin/bash

echo "=========================================="
echo "  EvenLeads Security Testing Setup"
echo "=========================================="
echo ""

# Check Python
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 not found. Please install Python 3.7+"
    exit 1
fi

echo "✓ Python 3 found: $(python3 --version)"

# Install dependencies
echo ""
echo "Installing dependencies..."
pip install -r requirements.txt

# Setup .env
if [ ! -f .env ]; then
    echo ""
    echo "Creating .env file..."
    cp .env.example .env
    echo "✓ .env created (please edit BASE_URL if needed)"
else
    echo ""
    echo "✓ .env already exists"
fi

echo ""
echo "=========================================="
echo "  Setup Complete!"
echo "=========================================="
echo ""
echo "To run all tests:"
echo "  python run_all_tests.py"
echo ""
echo "To run individual tests:"
echo "  python tests/test_mass_assignment.py"
echo "  python tests/test_rate_limiting.py"
echo "  python tests/test_file_upload.py"
echo ""
echo "For help, read:"
echo "  - QUICK_START.md (5-min guide)"
echo "  - README.md (full docs)"
echo ""
