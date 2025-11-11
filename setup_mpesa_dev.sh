#!/bin/bash

# M-Pesa Development Setup Script
# Sets up ngrok tunnel and updates M-Pesa callback URLs

echo "🚀 Setting up M-Pesa Development Environment with ngrok..."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if ngrok is installed
if ! command -v ngrok &> /dev/null; then
    print_error "ngrok is not installed or not in PATH"
    exit 1
fi

print_status "ngrok is available"

# Start ngrok in background
print_info "Starting ngrok tunnel on port 80..."
ngrok http 80 --log=stdout > /tmp/ngrok.log 2>&1 &
NGROK_PID=$!

# Wait for ngrok to start
sleep 3

# Get the ngrok URL
print_info "Getting ngrok public URL..."
NGROK_URL=""
for i in {1..10}; do
    NGROK_URL=$(curl -s http://localhost:4040/api/tunnels | python3 -c "
import sys, json
try:
    data = json.load(sys.stdin)
    for tunnel in data['tunnels']:
        if tunnel['proto'] == 'https':
            print(tunnel['public_url'])
            break
except:
    pass
" 2>/dev/null)
    
    if [ ! -z "$NGROK_URL" ]; then
        break
    fi
    sleep 1
done

if [ -z "$NGROK_URL" ]; then
    print_error "Could not get ngrok URL. Trying alternative method..."
    
    # Alternative method using curl to ngrok API
    sleep 2
    NGROK_URL=$(curl -s http://localhost:4040/api/tunnels | grep -o '"public_url":"https://[^"]*' | cut -d'"' -f4 | head -1)
fi

if [ ! -z "$NGROK_URL" ]; then
    print_status "ngrok tunnel established: $NGROK_URL"
    
    # Update config.php with the new ngrok URL
    CONFIG_FILE="/var/www/html/IAP2.2Dev/config.php"
    
    if [ -f "$CONFIG_FILE" ]; then
        print_info "Updating M-Pesa callback URLs in config.php..."
        
        # Create backup
        sudo cp "$CONFIG_FILE" "${CONFIG_FILE}.backup"
        
        # Update callback URL
        CALLBACK_URL="${NGROK_URL}/IAP2.2Dev/mpesa_callback.php"
        TIMEOUT_URL="${NGROK_URL}/IAP2.2Dev/mpesa_timeout.php"
        
        # Use sed to update the URLs
        sudo sed -i "s|'callback_url' => '[^']*'|'callback_url' => '$CALLBACK_URL'|g" "$CONFIG_FILE"
        sudo sed -i "s|'timeout_url' => '[^']*'|'timeout_url' => '$TIMEOUT_URL'|g" "$CONFIG_FILE"
        
        print_status "Configuration updated successfully!"
        print_info "Callback URL: $CALLBACK_URL"
        print_info "Timeout URL: $TIMEOUT_URL"
        
        # Test the updated configuration
        print_info "Testing M-Pesa API with new configuration..."
        cd /home/devyanjethwaa/IAP2.2-1
        php debug_mpesa.php | tail -10
        
    else
        print_error "Config file not found at $CONFIG_FILE"
        exit 1
    fi
    
    echo ""
    print_status "🎉 M-Pesa development environment is ready!"
    echo ""
    print_info "📋 Instructions:"
    echo "  1. Keep this terminal window open (ngrok tunnel active)"
    echo "  2. Visit: http://localhost/IAP2.2Dev/"
    echo "  3. Test M-Pesa payments with phone: 254708374149"
    echo "  4. Monitor callbacks at: $NGROK_URL/IAP2.2Dev/mpesa_callback.php"
    echo ""
    print_warning "⚠️  Important:"
    echo "  - Don't close this terminal (ngrok will stop)"
    echo "  - ngrok URL changes when restarted"
    echo "  - For production, use a permanent domain"
    echo ""
    
    # Keep ngrok running
    print_info "Press Ctrl+C to stop ngrok and restore original config..."
    
    # Trap Ctrl+C to cleanup
    trap 'print_warning "Stopping ngrok and restoring config..."; sudo cp "${CONFIG_FILE}.backup" "$CONFIG_FILE" 2>/dev/null; kill $NGROK_PID 2>/dev/null; exit 0' INT
    
    # Wait for user to stop
    wait $NGROK_PID
    
else
    print_error "Failed to establish ngrok tunnel"
    kill $NGROK_PID 2>/dev/null
    exit 1
fi
