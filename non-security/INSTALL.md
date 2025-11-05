# Installation Guide

## macOS Users (Homebrew Python)

If you see "externally-managed-environment" error:

### Option 1: User Installation (Recommended)
```bash
pip3 install -r requirements.txt --user
```

### Option 2: Virtual Environment (Best Practice)
```bash
# Create virtual environment
python3 -m venv venv

# Activate it
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Run tests
./start.sh
```

### Option 3: System-Wide (Not Recommended)
```bash
pip3 install -r requirements.txt --break-system-packages
```

## Linux/Ubuntu Users

```bash
pip3 install -r requirements.txt --user
```

## Windows Users

```bash
pip install -r requirements.txt
```

## Then Run

```bash
./start.sh
```

Or directly:
```bash
python3 start.py
```
