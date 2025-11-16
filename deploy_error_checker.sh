#!/bin/bash

echo "🔍 Deploying M-Pesa Error Checker"
echo "================================="

# Copy error checker to web directory
cp /home/devyanjethwaa/IAP2.2-1/check_mpesa_errors.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/check_mpesa_errors.php

echo "✅ Error checker deployed successfully!"
echo ""
echo "🌐 Access the error checker at:"
echo "   http://localhost/IAP2.2Dev/check_mpesa_errors.php?order=14"
echo ""
echo "This will show you exactly what's wrong with the M-Pesa payment page."