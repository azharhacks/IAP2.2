# 🔧 M-Pesa Callback URL Issue - Solutions

## ❌ **Issue Identified:**
```
HTTP Code: 400
Error Code: 400.002.02  
Error Message: Bad Request - Invalid CallBackURL
```

**Root Cause:** The callback URL `http://localhost/IAP2.2Dev/mpesa_callback.php` is not publicly accessible from the internet. Safaricom's M-Pesa API servers cannot reach your local development server.

---

## ✅ **Solutions:**

### **Solution 1: Use ngrok (Recommended for Testing)**

**Step 1:** Install ngrok
```bash
# Download ngrok from https://ngrok.com/
# Or install via package manager (if available)
sudo dnf install ngrok  # For Fedora
```

**Step 2:** Expose your local server
```bash
# Run this in a new terminal window
ngrok http 80
```

**Step 3:** Update callback URL in config.php
```php
// Copy the https URL from ngrok (e.g., https://abc123.ngrok.io)
$conf['mpesa']['callback_url'] = 'https://YOUR_NGROK_URL.ngrok.io/IAP2.2Dev/mpesa_callback.php';
$conf['mpesa']['timeout_url'] = 'https://YOUR_NGROK_URL.ngrok.io/IAP2.2Dev/mpesa_timeout.php';
```

### **Solution 2: Use a Test Callback URL Service**

**Step 1:** Use webhook.site for testing
```php
// Update config.php with a test webhook URL
$conf['mpesa']['callback_url'] = 'https://webhook.site/unique-uuid-here';
$conf['mpesa']['timeout_url'] = 'https://webhook.site/unique-uuid-here';
```

**Step 2:** Monitor callbacks at webhook.site
- Visit https://webhook.site/
- Copy the unique URL
- Use it as your callback URL
- Monitor incoming M-Pesa callbacks in real-time

### **Solution 3: Deploy to a Public Server**

**Option A:** Use a cloud service (DigitalOcean, AWS, etc.)
**Option B:** Use a free hosting service (Heroku, Netlify, etc.)
**Option C:** Use a VPS with a public IP

### **Solution 4: Temporarily Use a Mock Callback (Development Only)**

For development testing, we can modify the M-Pesa class to simulate successful callbacks.

---

## 🚀 **Quick Fix with ngrok (Recommended)**

Let me help you set up ngrok:
