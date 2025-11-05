#!/bin/bash

echo ""
echo "=========================================="
echo "  Quick Test - No Setup Required"
echo "=========================================="
echo ""

# Check if venv exists
if [ -d "venv" ]; then
    echo "✓ Using existing virtual environment"
    source venv/bin/activate
else
    echo "Creating virtual environment..."
    python3 -m venv venv
    source venv/bin/activate
    
    echo "Installing dependencies..."
    pip install -r requirements.txt -q
    echo "✓ Dependencies installed"
fi

echo ""
echo "Running tests..."
echo ""

python3 run_all_tests.py

echo ""
echo "=========================================="
echo "  Tests Complete!"
echo "=========================================="
echo ""
