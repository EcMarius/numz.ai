# NUMZ.AI Admin Guide - Module Management System

## Overview

NUMZ.AI features a comprehensive module management system that allows you to configure **payment gateways, provisioning modules, domain registrars, and integrations** entirely through the admin panel - **no .env file editing required!**

## Accessing Module Management

Navigate to: **Admin Panel → System → Modules**

## Module Types

### 1. Payment Gateways
Accept payments from customers through various payment processors.

**Available Gateways:**
- ✅ **Stripe** - Credit cards, subscriptions, global payments
- ✅ **PayPal** - PayPal accounts and credit cards
- ✅ **Authorize.Net** - Traditional credit card processing
- ✅ **Square** - Modern payment processing
- ✅ **Mollie** - European payments (20+ methods)
- ✅ **Razorpay** - Indian market (UPI, cards, wallets)
- ✅ **Coinbase Commerce** - Cryptocurrency payments
- ✅ **Paysafecard** - Prepaid payment vouchers
- ✅ **2Checkout** - Global payment processing

### 2. Provisioning Modules
Automatically create and manage hosting accounts.

**Available Modules:**
- ✅ **cPanel/WHM** - Most popular hosting control panel
- ✅ **Plesk** - Windows and Linux hosting
- ✅ **DirectAdmin** - Lightweight control panel
- ✅ **OneProvider** - Dedicated servers and VPS

### 3. Domain Registrars
Register and manage domain names.

**Available Registrars:**
- ✅ **DomainNameAPI** - Multi-registrar integration

### 4. Integrations
Connect with third-party services.

**Available Integrations:**
- ✅ **Tawk.to** - Free live chat widget
- ✅ **Slack** - Team notifications
- ✅ **Google Analytics** - Website analytics

---

## How to Configure a Module

### Step 1: Navigate to Modules
Go to **Admin Panel → System → Modules**

### Step 2: Find Your Module
All available modules are pre-loaded. Simply click **Edit** on the module you want to configure.

### Step 3: Configure Settings

#### Basic Information
- **Display Name**: How this module appears to users
- **Description**: What this module does
- **Enabled**: Turn on/off the module
- **Test Mode**: Use sandbox/test environment (if supported)
- **Sort Order**: Display priority (lower = first)

#### Module Settings (Configuration Tab)
Add key-value pairs for module-specific settings:
- Timeout values
- API endpoints
- Feature flags
- Custom options

**Example for Stripe:**
```
webhook_tolerance: 300
capture_method: automatic
payment_method_types: ["card", "sepa_debit"]
```

#### Credentials (Credentials Tab)
**🔒 Automatically Encrypted!** Add sensitive API credentials:

**Stripe Example:**
```
secret_key: sk_live_xxxxxxxxxxxxx
publishable_key: pk_live_xxxxxxxxxxxxx
webhook_secret: whsec_xxxxxxxxxxxxx
```

**cPanel Example:**
```
hostname: server1.example.com
port: 2087
username: root
access_key: XXXXXXXXXXXXXXXXXXXXXXXX
```

**PayPal Example:**
```
client_id: YOUR_CLIENT_ID
secret: YOUR_SECRET
sandbox: true
```

### Step 4: Test Connection
Click the **Test** button to verify your credentials work correctly.
- ✅ Green = Success
- ❌ Red = Failed (check error message)

### Step 5: Enable Module
Toggle **Enabled** to **ON** to start using the module.

---

## System Settings

Navigate to: **Admin Panel → System → Settings**

Configure all system-wide settings through tabs:

### General Settings
- Company name and URL
- Support email
- Default currency and timezone

### Billing Settings
- Invoice prefix and due days
- Auto-suspend/terminate timings
- Late fee percentage
- Enable credit system
- Allow partial payments

### Email Settings
- Mail driver (SMTP, Sendmail, Mailgun, etc.)
- From address and name
- Enable/disable notifications

### AI & Automation
- Enable AI features
- Choose AI provider (OpenAI or Anthropic)
- Select AI model
- Enable specific AI features:
  - AI Chatbot
  - Churn prediction
  - Fraud detection
  - Sentiment analysis

### Security Settings
- Enforce 2FA for admins
- IP whitelist
- Session lifetime
- Password requirements
- Audit logging

### Support Settings
- Enable support tickets
- Enable live chat
- Enable knowledge base
- Default ticket priority

---

## Real-World Configuration Examples

### Example 1: Configure Stripe (Production)

1. Go to **Modules** → Edit **Stripe**
2. **Credentials** tab:
   ```
   secret_key: sk_live_51Hxxxxxxxxxxxxx
   publishable_key: pk_live_51Hxxxxxxxxxxxxx
   webhook_secret: whsec_xxxxxxxxxxxxxx
   ```
3. Set **Test Mode** to **OFF**
4. Click **Test** to verify
5. Enable the module

### Example 2: Configure cPanel Provisioning

1. Go to **Modules** → Edit **cPanel/WHM**
2. **Credentials** tab:
   ```
   hostname: server1.myhosting.com
   port: 2087
   username: root
   access_key: YOUR_WHM_API_TOKEN
   ```
3. **Configuration** tab:
   ```
   default_nameservers: ["ns1.myhosting.com", "ns2.myhosting.com"]
   create_subdomain: true
   ```
4. Click **Test** to verify connection
5. Enable the module

### Example 3: Enable AI Features

1. Go to **Settings** → **AI & Automation** tab
2. Toggle **Enable AI Features** to ON
3. Select **AI Provider**: OpenAI or Anthropic
4. Select **AI Model**: GPT-4, Claude 3, etc.
5. Enable specific features:
   - ✅ AI Chatbot
   - ✅ Churn Prediction
   - ✅ Fraud Detection
6. Go to **System → API Credentials** (if available)
7. Add your API key for the chosen provider
8. Click **Save Settings**

---

## Module Capabilities

Each module shows what features it supports:

**Payment Gateway Capabilities:**
- `cards` - Credit/debit card processing
- `subscriptions` - Recurring billing
- `refunds` - Process refunds
- `webhooks` - Real-time notifications
- `test_mode` - Sandbox environment
- `crypto` - Cryptocurrency payments

**Provisioning Capabilities:**
- `create_account` - Create new hosting accounts
- `suspend` - Suspend services
- `unsuspend` - Unsuspend services
- `terminate` - Delete services
- `change_package` - Upgrade/downgrade plans
- `change_password` - Reset passwords

---

## Best Practices

### 🔒 Security
1. **Always use test mode first** before going live
2. **Test connections** after configuration
3. **Rotate API keys** regularly
4. **Enable 2FA** for admin accounts
5. **Review audit logs** periodically

### ⚡ Performance
1. **Only enable modules you use** to reduce overhead
2. **Set appropriate timeouts** in configuration
3. **Monitor webhook delivery** for failures
4. **Use test mode** for development/staging

### 📊 Monitoring
1. **Check "Last Tested"** column regularly
2. **Review failed tests** immediately
3. **Monitor webhook success rates**
4. **Check automation execution logs**

---

## Troubleshooting

### Module Test Fails
1. **Check credentials** are correct
2. **Verify IP whitelisting** (some APIs require it)
3. **Check firewall rules** allow outbound connections
4. **Review test error message** for specific issues
5. **Contact provider support** if needed

### Webhooks Not Working
1. **Verify webhook URL** is accessible publicly
2. **Check webhook secret** matches
3. **Review webhook logs** in module details
4. **Test webhook manually** using provider's dashboard
5. **Check SSL certificate** is valid

### Provisioning Failures
1. **Test server connection** first
2. **Verify API credentials** have correct permissions
3. **Check server capacity** isn't exceeded
4. **Review provisioning logs** for errors
5. **Check nameservers** are configured correctly

---

## Migration from .env Configuration

If you previously configured modules via `.env`, here's how to migrate:

### Old Way (.env):
```env
STRIPE_SECRET_KEY=sk_live_xxxxx
STRIPE_PUBLISHABLE_KEY=pk_live_xxxxx
PAYPAL_CLIENT_ID=xxxxx
PAYPAL_SECRET=xxxxx
```

### New Way (Admin Panel):
1. Go to **Modules** → **Stripe**
2. Add credentials in the **Credentials** tab
3. Enable the module
4. Remove from `.env` file
5. **Much better UX!** ✅

---

## Quick Start Checklist

- [ ] Seed modules: `php artisan db:seed --class=ModuleConfigurationSeeder`
- [ ] Configure at least one payment gateway
- [ ] Test payment gateway connection
- [ ] Configure provisioning for your servers
- [ ] Test provisioning connection
- [ ] Configure system settings
- [ ] Enable AI features (optional)
- [ ] Set up email configuration
- [ ] Configure automation rules
- [ ] Review security settings

---

## Support

For additional help:
- 📧 Check system logs in **Admin → System → Logs**
- 📊 Review audit trail in **Admin → System → Activity**
- 🔧 Check module test results
- 📖 Refer to provider documentation for API details

---

**Your billing system, your way - no code required!** 🚀
