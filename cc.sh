#!/bin/bash

# EvenLeads - Clear Cache and Optimize Script
# This script clears all Laravel caches and optimizes the application
# Usage:
#   ./cc.sh              - Clear caches, optimize, restart PHP-FPM and queue worker
#   ./cc.sh without-queue - Clear caches, optimize, restart PHP-FPM only (skip queue worker)

SKIP_QUEUE=false

# Check for without-queue parameter
if [ "$1" = "without-queue" ]; then
    SKIP_QUEUE=true
fi

echo "🧹 Clearing Laravel caches..."
php artisan optimize:clear

echo ""
echo "⚡ Optimizing Laravel..."
php artisan optimize

echo ""
echo "🔄 Restarting PHP-FPM 8.4..."
systemctl restart php-fpm-84

if [ "$SKIP_QUEUE" = false ]; then
    echo ""
    echo "🔄 Restarting Queue Worker..."
    systemctl restart evenleads-queue
    echo ""
    echo "✅ Done! All caches cleared, optimized, PHP-FPM and queue worker restarted."
else
    echo ""
    echo "⏭️  Skipping queue worker restart (without-queue flag)"
    echo ""
    echo "✅ Done! All caches cleared, optimized, and PHP-FPM restarted (queue worker still running)."
fi
