#!/bin/bash

# Quick Virtual Environment Setup
# Creates a venv and installs all dependencies

echo ""
echo "=========================================="
echo "  Creating Virtual Environment"
echo "=========================================="
echo ""

# Create venv if it doesn't exist
if [ ! -d "venv" ]; then
    echo "Creating virtual environment..."
    python3 -m venv venv
    echo "✓ Virtual environment created"
else
    echo "✓ Virtual environment already exists"
fi

echo ""
echo "Activating virtual environment..."
source venv/bin/activate

echo "Installing dependencies..."
pip install -r requirements.txt

echo ""
echo "=========================================="
echo "  Setup Complete!"
echo "=========================================="
echo ""
echo "Virtual environment is now active."
echo ""
echo "To run tests:"
echo "  ./start.sh    (will auto-use venv)"
echo ""
echo "Or activate manually:"
echo "  source venv/bin/activate"
echo "  python start.py"
echo ""
