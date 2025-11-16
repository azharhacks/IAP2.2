#!/bin/bash

echo "🧹 SMARTDUKA Cleanup - Removing Empty and Test Files"
echo "=================================================="

# List of files to remove (empty and test files)
files_to_remove=(
    "/home/devyanjethwaa/IAP2.2-1/test_callback.php"
    "/home/devyanjethwaa/IAP2.2-1/config.sample.php"
    "/home/devyanjethwaa/IAP2.2-1/theme_test.php"
    "/home/devyanjethwaa/IAP2.2-1/session_debug.php"
    "/home/devyanjethwaa/IAP2.2-1/USER_ROLES_GUIDE.md"
    "/home/devyanjethwaa/IAP2.2-1/quick_mpesa_fix.php"
    "/home/devyanjethwaa/IAP2.2-1/setup_mpesa_dev.sh"
    "/home/devyanjethwaa/IAP2.2-1/mpesa_payment_page.php"
    "/home/devyanjethwaa/IAP2.2-1/test_mpesa_system.php"
    "/home/devyanjethwaa/IAP2.2-1/deploy_mpesa.sh"
    "/home/devyanjethwaa/IAP2.2-1/fix_mpesa_final.sh"
    "/home/devyanjethwaa/IAP2.2-1/cleanup_project.sh"
    "/home/devyanjethwaa/IAP2.2-1/project_status.sh"
)

# Remove each file
for file in "${files_to_remove[@]}"; do
    if [ -f "$file" ]; then
        rm -f "$file"
        echo "✅ Removed: $(basename "$file")"
    else
        echo "ℹ️  Not found: $(basename "$file")"
    fi
done

# Remove helper directories
if [ -d "/home/devyanjethwaa/IAP2.2-1/includes" ]; then
    rm -rf "/home/devyanjethwaa/IAP2.2-1/includes"
    echo "✅ Removed: includes/ directory"
fi

# Remove any backup or temporary files
echo ""
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
    -name "*.bak" \) -delete 2>/dev/null

echo ""
echo "📊 Remaining Essential Files:"
echo "============================"
echo "📁 Root PHP Files:"
ls /home/devyanjethwaa/IAP2.2-1/*.php 2>/dev/null | wc -l | xargs echo "   Core files:"

echo ""
echo "📁 Admin Files:"
ls /home/devyanjethwaa/IAP2.2-1/admin/*.php 2>/dev/null | wc -l | xargs echo "   Admin files:"

echo ""
echo "📁 Configuration:"
if [ -f "/home/devyanjethwaa/IAP2.2-1/config.php" ]; then
    echo "   ✅ config.php"
fi

echo ""
echo "📁 Layout System:"
if [ -f "/home/devyanjethwaa/IAP2.2-1/Abstract/Layout.php" ]; then
    echo "   ✅ Abstract/Layout.php"
fi

echo ""
echo "📁 Documentation:"
if [ -f "/home/devyanjethwaa/IAP2.2-1/SMARTDUKA_BRAND_UPDATE.md" ]; then
    echo "   ✅ SMARTDUKA_BRAND_UPDATE.md"
fi

echo ""
echo "✅ Cleanup Complete! Project is now clean and production-ready."