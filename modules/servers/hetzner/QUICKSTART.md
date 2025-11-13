# Hetzner Module Quick Start Guide

Get up and running with the Hetzner module in 10 minutes!

## Prerequisites

- WHMCS 7.0+ installed and running
- PHP 7.4+ with cURL and JSON extensions
- Active Hetzner Cloud account

## Step 1: Upload Files (2 minutes)

```bash
# Upload module to WHMCS
cd /path/to/whmcs/modules/servers/
# Copy the hetzner directory here

# Set permissions
chmod 755 hetzner
chmod 644 hetzner/*.php
```

## Step 2: Get API Token (2 minutes)

1. Go to https://console.hetzner.cloud/
2. Select your project (or create new)
3. Navigate: **Security → API Tokens**
4. Click **Generate API Token**
5. Name: "WHMCS Integration"
6. Permissions: **Read & Write**
7. **Copy the token** (save it securely!)

## Step 3: Create Product (3 minutes)

1. WHMCS Admin → **Setup → Products/Services**
2. Click **Create a New Product**
3. Fill in:
   - **Product Name**: "Cloud VPS - CX11"
   - **Product Group**: Select or create
   - **Product Type**: Other

4. **Module Settings** tab:
   - **Module Name**: Select "Hetzner Cloud & Dedicated"
   - **API Token**: Paste your token
   - **Service Type**: Cloud (VPS)
   - **Server Type**: cx11
   - **Location**: fsn1 (Falkenstein)
   - **OS Image**: ubuntu-22.04
   - **Enable IPv6**: Yes
   - **Enable Backups**: No
   - **Firewall**: Yes

5. **Pricing** tab:
   - Monthly: $5.00 (or your price)
   - Click **Save Changes**

## Step 4: Test Connection (1 minute)

1. Edit your product
2. Go to **Module Settings** tab
3. Click **Test Connection**
4. Should show: "Connection successful"

## Step 5: Test Order (2 minutes)

1. Create test order:
   - Admin → **Orders → Add New Order**
   - Select test client
   - Add your Hetzner product
   - Complete order
   - Mark as paid

2. Wait 30-60 seconds for provisioning

3. Check service:
   - **Clients → View/Search Clients**
   - Find client → View Products/Services
   - Click the service
   - Should show server details and IP

## Step 6: Verify (1 minute)

1. **In WHMCS Client Area**:
   - Login as test client
   - View the service
   - Should see server status, IP, controls

2. **In Hetzner Console**:
   - Go to https://console.hetzner.cloud/
   - Check if server appears
   - Should be running

## Done!

Your module is now working! 🎉

## Quick Configuration Guide

### Popular Server Types

**Budget VPS**:
- cx11: 1 vCPU, 2GB RAM, 20GB SSD (~€3.79/mo)
- cpx11: 2 vCPU, 2GB RAM, 40GB SSD (~€4.15/mo) ⭐ Best value

**Small Business**:
- cx21: 2 vCPU, 4GB RAM, 40GB SSD
- cpx21: 3 vCPU, 4GB RAM, 80GB SSD ⭐ Recommended

**Medium Business**:
- cx31: 2 vCPU, 8GB RAM, 80GB SSD
- cpx31: 4 vCPU, 8GB RAM, 160GB SSD ⭐ Best performance

**High Performance**:
- ccx12: 2 Dedicated vCPU, 8GB RAM
- ccx22: 4 Dedicated vCPU, 16GB RAM ⭐ Dedicated resources

### Locations

**Europe** (lowest latency for EU):
- fsn1: Falkenstein, Germany ⭐ Recommended
- nbg1: Nuremberg, Germany
- hel1: Helsinki, Finland

**USA**:
- ash: Ashburn, Virginia (East Coast)
- hil: Hillsboro, Oregon (West Coast)

### Operating Systems

**Recommended**:
- ubuntu-22.04 ⭐ Most popular, LTS
- debian-12 (Stable, secure)
- rocky-9 (RHEL alternative)

**Others**:
- ubuntu-20.04 (Older LTS)
- debian-11
- centos-stream-9
- alma-9
- fedora-38

## Common Pricing Examples

| Product | Hetzner Cost | Your Price | Markup |
|---------|--------------|------------|--------|
| CX11 | €3.79 | $5/mo | 32% |
| CPX11 | €4.15 | $6/mo | 45% |
| CX21 | €5.99 | $8/mo | 34% |
| CPX21 | €7.49 | $10/mo | 34% |
| CX31 | €11.99 | $15/mo | 25% |
| CPX31 | €13.99 | $18/mo | 29% |

*Prices as of 2024, verify current Hetzner pricing*

## Enable Auto-Provisioning

For automatic server creation on payment:

1. Edit product → **Module Settings**
2. Check: ✓ **"Automatically setup the product as soon as an order is placed"**
3. Save

## Enable SSH Keys (Recommended)

1. Generate SSH key:
   ```bash
   ssh-keygen -t ed25519 -C "hosting@yourdomain.com"
   ```

2. Add to Hetzner:
   - Console → Security → SSH Keys
   - Add SSH Key
   - Paste public key
   - Note the **Key ID** (e.g., 12345)

3. Add to product:
   - Edit product → Module Settings
   - **SSH Key IDs**: Enter ID (e.g., "12345")
   - Save

Now all servers will have this SSH key automatically!

## Optional: Install Hooks

For advanced automation (welcome emails, monitoring, etc.):

```bash
cp /path/to/whmcs/modules/servers/hetzner/hooks.php \
   /path/to/whmcs/includes/hooks/hetzner_hooks.php

chmod 644 /path/to/whmcs/includes/hooks/hetzner_hooks.php
```

## Troubleshooting

### "Module not showing"
- Check file permissions (644)
- Verify path: `modules/servers/hetzner/hetzner.php`
- Clear WHMCS cache

### "Connection failed"
- Verify API token is correct
- Check token has Read & Write permissions
- Test manually:
  ```bash
  curl -H "Authorization: Bearer YOUR_TOKEN" \
       https://api.hetzner.cloud/v1/locations
  ```

### "Server creation failed"
- Check module log: System Settings → System Logs → Module Log
- Verify server type available in selected location
- Check Hetzner account limits
- Try different location

### "IP not showing"
- Wait 60 seconds
- Refresh page
- Check module log for errors

## Need More Help?

- **Full Documentation**: See `README.md`
- **Installation Guide**: See `INSTALL.md`
- **Test Module**: Run `php test.php`
- **Module Log**: System Settings → System Logs → Module Log
- **Hetzner Status**: https://status.hetzner.com/

## Next Steps

1. ✓ Create more products (different server sizes)
2. ✓ Set up welcome email templates
3. ✓ Configure automatic suspension/termination
4. ✓ Install hooks for automation
5. ✓ Test all features (suspend, unsuspend, terminate)
6. ✓ Go live!

---

**Ready to sell Hetzner servers!** 🚀

For detailed information, see the complete README.md and INSTALL.md files.
