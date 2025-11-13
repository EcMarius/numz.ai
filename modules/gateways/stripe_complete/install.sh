#!/bin/bash
# Stripe Complete Gateway Installation Script
# This script installs the Stripe PHP library

echo "Installing Stripe PHP library..."

cd "$(dirname "$0")"

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "Error: Composer is not installed."
    echo "Please install Composer from https://getcomposer.org/"
    exit 1
fi

# Install dependencies
composer install --no-dev --optimize-autoloader

if [ $? -eq 0 ]; then
    echo "✓ Stripe PHP library installed successfully!"
    echo "✓ Gateway is ready to use"
else
    echo "✗ Installation failed"
    exit 1
fi
