#!/bin/bash

echo "🧹 SMARTDUKA Project Cleanup - Removing Unnecessary Files"
echo "========================================================"

# Remove empty and template files
echo "🗑️ Removing empty and template files..."
rm -f /home/devyanjethwaa/IAP2.2-1/test_callback.php
rm -f /home/devyanjethwaa/IAP2.2-1/config.sample.php
rm -f /home/devyanjethwaa/IAP2.2-1/theme_test.php
rm -f /home/devyanjethwaa/IAP2.2-1/session_debug.php
rm -f /home/devyanjethwaa/IAP2.2-1/USER_ROLES_GUIDE.md
rm -f /home/devyanjethwaa/IAP2.2-1/quick_mpesa_fix.php
rm -f /home/devyanjethwaa/IAP2.2-1/setup_mpesa_dev.sh
rm -f /home/devyanjethwaa/IAP2.2-1/mpesa_payment_page.php

# Remove test and development scripts
echo "🧪 Removing test and development files..."
rm -f /home/devyanjethwaa/IAP2.2-1/test_mpesa_system.php
rm -f /home/devyanjethwaa/IAP2.2-1/deploy_mpesa.sh
rm -f /home/devyanjethwaa/IAP2.2-1/fix_mpesa_final.sh

# Remove helper files that are not needed
echo "🔧 Removing unused helper files..."
rm -rf /home/devyanjethwaa/IAP2.2-1/includes/

# Remove any backup or temporary files
echo "🗂️ Removing backup and temporary files..."
find /home/devyanjethwaa/IAP2.2-1/ -type f \( \
    -name "*_backup*" -o \
    -name "*_old*" -o \
    -name "*_test*" -o \
    -name "*_demo*" -o \
    -name "*_sample*" -o \
    -name "*.tmp" -o \
    -name "*.log" -o \
    -name "*~" -o \
    -name "*.bak" \) -delete

# Remove empty directories
echo "📁 Removing empty directories..."
find /home/devyanjethwaa/IAP2.2-1/ -type d -empty -delete 2>/dev/null || true

# Clean up web directory as well
echo "🌐 Cleaning web directory..."
rm -f /var/www/html/IAP2.2Dev/test_callback.php 2>/dev/null || true
rm -f /var/www/html/IAP2.2Dev/config.sample.php 2>/dev/null || true
rm -f /var/www/html/IAP2.2Dev/theme_test.php 2>/dev/null || true
rm -f /var/www/html/IAP2.2Dev/session_debug.php 2>/dev/null || true
rm -f /var/www/html/IAP2.2Dev/test_mpesa_system.php 2>/dev/null || true

# Remove any backup or temporary files from web directory
find /var/www/html/IAP2.2Dev/ -type f \( \
    -name "*_backup*" -o \
    -name "*_old*" -o \
    -name "*_test*" -o \
    -name "*_demo*" -o \
    -name "*_sample*" -o \
    -name "*.tmp" -o \
    -name "*.log" -o \
    -name "*~" -o \
    -name "*.bak" \) -delete 2>/dev/null || true

echo ""
echo "📊 Current Project Structure:"
echo "============================="
echo "📁 Root Files:"
ls -la /home/devyanjethwaa/IAP2.2-1/*.php | awk '{print "   " $9}' | sed 's|.*/||'

echo ""
echo "📁 Admin Files:"
ls -la /home/devyanjethwaa/IAP2.2-1/admin/*.php | awk '{print "   " $9}' | sed 's|.*/||'

echo ""
echo "📁 Classes:"
ls -la /home/devyanjethwaa/IAP2.2-1/classes/ 2>/dev/null | grep "\.php" | awk '{print "   " $9}' || echo "   No class files found"

echo ""
echo "📁 Abstract:"
ls -la /home/devyanjethwaa/IAP2.2-1/Abstract/ 2>/dev/null | grep "\.php" | awk '{print "   " $9}' || echo "   No abstract files found"

echo ""
echo "✅ Cleanup Complete!"
echo "==================="
echo ""
echo "🎯 Essential Files Retained:"
echo "   ✅ Core PHP application files"
echo "   ✅ Admin panel files (M-Pesa, Orders, Users)"
echo "   ✅ Configuration files (config.php)"
echo "   ✅ Layout and styling (Abstract/Layout.php)"
echo "   ✅ Core functionality (checkout, cart, products)"
echo "   ✅ Documentation (SMARTDUKA_BRAND_UPDATE.md)"
echo ""
echo "🗑️ Files Removed:"
echo "   ❌ Empty template files"
echo "   ❌ Test and debug scripts" 
echo "   ❌ Development setup files"
echo "   ❌ Backup and temporary files"
echo "   ❌ Unused helper directories"
echo ""
echo "🚀 Project is now clean and production-ready!"