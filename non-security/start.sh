#!/bin/bash

# EvenLeads Security Testing - Easy Launcher
# This script provides an interactive interface for security testing

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Banner
echo ""
echo -e "${CYAN}======================================${NC}"
echo -e "${YELLOW}  🔐 EvenLeads Security Testing${NC}"
echo -e "${CYAN}======================================${NC}"
echo ""

# Check for virtual environment
if [ -d "venv" ]; then
    echo -e "${GREEN}✓ Virtual environment found${NC}"
    echo "Activating venv..."
    source venv/bin/activate
    echo ""
fi

# Check if Python 3 is installed
if ! command -v python3 &> /dev/null; then
    echo -e "${RED}✗ Python 3 not found${NC}"
    echo "Please install Python 3.7 or higher"
    echo ""
    exit 1
fi

echo -e "${GREEN}✓ Python 3:${NC} $(python3 --version)"

# Check if pip3 is available
if command -v pip3 &> /dev/null; then
    echo -e "${GREEN}✓ pip3:${NC} available"
else
    echo -e "${YELLOW}! pip3 not found (will use python3 -m pip)${NC}"
fi

echo ""

# Launch interactive menu
exec python3 start.py "$@"
