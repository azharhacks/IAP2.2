#!/bin/bash

echo "💳 Deploying M-Pesa Status Check Fixes"
echo "======================================"

# Copy M-Pesa files
cp /home/devyanjethwaa/IAP2.2-1/mpesa_payment.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/debug_mpesa_status.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/mpesa_payment.php
chmod 644 /var/www/html/IAP2.2Dev/debug_mpesa_status.php

echo "✅ M-Pesa status check fixes deployed!"
echo ""
echo "🔧 What was fixed:"
echo "   ✅ Created comprehensive mpesa_payment.php handler"
echo "   ✅ Added proper status checking with simulated M-Pesa flow"
echo "   ✅ Created debug tool to test status checking"
echo "   ✅ Added transaction logging and status updates"
echo ""
echo "🧪 Test the M-Pesa status checking:"
echo "   Debug Tool: http://localhost/IAP2.2Dev/debug_mpesa_status.php"
echo "   Payment Page: http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=14"
echo ""
echo "💡 How it works now:"
echo "   1. Payment initiated → Creates transaction record"
echo "   2. Status checking → Simulates M-Pesa API calls"
echo "   3. After 30 seconds → Randomly completes (80% success rate)"
echo "   4. Order updated → Status changes to 'paid' and 'processing'"
echo "   5. Redirect → Goes to order confirmation page"
echo ""
echo "🔍 Use the debug tool to:"
echo "   • Test payment initiation"
echo "   • Check transaction status"
echo "   • Simulate complete payment flow"
echo "   • View transaction history"