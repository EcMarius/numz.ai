# Hetzner Module Installation Guide

Complete step-by-step installation instructions for the Hetzner Cloud & Robot API module.

## Prerequisites

- WHMCS 7.0 or higher (8.x recommended)
- PHP 7.4 or higher (8.x recommended)
- PHP cURL extension enabled
- PHP JSON extension enabled
- Active Hetzner Cloud account (for VPS)
- Active Hetzner Robot account (for dedicated servers)

## Installation Steps

### Step 1: Upload Module Files

1. **Download or copy the module files**
   ```bash
   cd /path/to/whmcs/modules/servers/
   ```

2. **Ensure directory structure**
   ```
   hetzner/
   ├── hetzner.php          (main module file)
   ├── hooks.php            (automation hooks)
   ├── logo.svg             (module logo)
   ├── config.example.php   (configuration example)
   ├── README.md            (documentation)
   ├── INSTALL.md           (this file)
   └── templates/
       └── clientarea.tpl   (client area template)
   ```

3. **Set correct permissions**
   ```bash
   chmod 755 /path/to/whmcs/modules/servers/hetzner
   chmod 644 /path/to/whmcs/modules/servers/hetzner/*.php
   chmod 644 /path/to/whmcs/modules/servers/hetzner/templates/*.tpl
   ```

### Step 2: Install Hooks (Optional but Recommended)

1. **Copy hooks file to WHMCS hooks directory**
   ```bash
   cp /path/to/whmcs/modules/servers/hetzner/hooks.php \
      /path/to/whmcs/includes/hooks/hetzner_hooks.php
   ```

2. **Set permissions**
   ```bash
   chmod 644 /path/to/whmcs/includes/hooks/hetzner_hooks.php
   ```

### Step 3: Obtain Hetzner API Credentials

#### For Cloud Services:

1. Log in to [Hetzner Cloud Console](https://console.hetzner.cloud/)
2. Create a new project or select existing one
3. Navigate to **Security → API Tokens**
4. Click **Generate API Token**
5. Settings:
   - **Description**: "WHMCS Integration"
   - **Permissions**: **Read & Write**
6. **Copy the token** (you won't see it again!)
7. **Save it securely**

#### For Dedicated Servers:

1. Log in to [Hetzner Robot](https://robot.your-server.de/)
2. Go to **Settings → Account**
3. Note your Robot username
4. Generate or retrieve Robot password
5. Keep credentials secure

### Step 4: Create Product in WHMCS

1. **Navigate to Products**
   - Setup → Products/Services → Create a New Product

2. **Product Type**
   - Select: **Other** (for Cloud VPS)
   - Or: **Dedicated/VPS** (for Dedicated Servers)

3. **Product Details**
   - **Product Name**: "Hetzner Cloud VPS CX11" (example)
   - **Product Group**: Create or select group
   - **Description**: Add marketing description

4. **Module Settings Tab**
   - **Module**: Select **Hetzner Cloud & Dedicated**
   - **API Token**: Paste your Cloud API token
   - OR
   - **Username**: Robot username (for dedicated)
   - **Password**: Robot password (for dedicated)

5. **Configure Module Options**

   **Service Type**:
   - Select: "Cloud (VPS)" or "Dedicated Server"

   **Server Type/Size**:
   - For starter VPS: cx11
   - For better performance: cpx21
   - For dedicated CPU: ccx12
   - See README.md for full list

   **Location/Datacenter**:
   - Europe: fsn1 (Falkenstein) or nbg1 (Nuremberg)
   - US: ash (Ashburn) or hil (Hillsboro)

   **Operating System**:
   - Recommended: ubuntu-22.04
   - Others: debian-12, rocky-9, alma-9, etc.

   **Additional Options**:
   - Enable IPv6: Yes (recommended)
   - Enable Backups: Customer choice
   - SSH Keys: Leave blank or add key IDs
   - Firewall: Yes (recommended)

6. **Pricing Tab**
   - Set your pricing (monthly/annually)
   - Factor in Hetzner's costs + markup
   - Example: CX11 = €3.79 → charge $5/month

7. **Save Product**

### Step 5: Test Configuration

1. **Test API Connection**
   - Setup → Products/Services
   - Edit your Hetzner product
   - Go to Module Settings tab
   - Click **Test Connection**
   - Should show: "Connection successful" with API version

2. **Create Test Order**
   - Place a test order for the product
   - Complete payment (or mark paid manually)
   - Wait for provisioning (usually 30-60 seconds)
   - Check if server was created

3. **Verify in Hetzner Console**
   - Log in to Hetzner Cloud Console
   - Check if server appears in your project
   - Verify server is running

4. **Check Client Area**
   - Log in as test client
   - View the service
   - Verify all information displays correctly
   - Test power controls

### Step 6: Configure Automation

1. **Enable Auto-Provisioning**
   - Setup → Products/Services → Edit Product
   - Other tab → Module Settings
   - Check: "Automatically setup the product as soon as an order is placed"

2. **Configure Suspension**
   - Setup → Products/Services → Edit Product
   - Other tab → Module Settings
   - Check: "Automatically suspend on due date"

3. **Configure Termination**
   - Setup → Products/Services → Edit Product
   - Other tab → Module Settings
   - Check: "Automatically terminate on due date + X days"

4. **Test Automation**
   - Create test order
   - Verify automatic provisioning
   - Test suspension (optional)
   - Test unsuspension (optional)

### Step 7: Create Email Templates (Optional)

1. **Navigate to Email Templates**
   - Setup → Email Templates → Create New Email Template

2. **Create Welcome Email**
   - **Type**: Product/Service
   - **Name**: "Hetzner Server Welcome"
   - **Subject**: "Your Hetzner Server is Ready!"
   - **Body**:
   ```
   Dear {$client_name},

   Your Hetzner Cloud Server has been successfully provisioned!

   Server Details:
   - Server ID: {$service_custom_field_1}
   - IP Address: {$service_dedicated_ip}
   - Username: root
   - Password: {$service_password}
   - Location: {$service_custom_field_2}

   SSH Access:
   ssh root@{$service_dedicated_ip}

   Console Access:
   https://console.hetzner.cloud/

   Documentation:
   https://docs.hetzner.com/cloud/

   Thank you for choosing our services!
   ```

3. **Assign to Product**
   - Edit product → Emails tab
   - Select your welcome email template

### Step 8: Additional Configuration

#### SSH Keys Setup

1. Generate SSH key pair:
   ```bash
   ssh-keygen -t ed25519 -C "whmcs@yourdomain.com"
   ```

2. Add to Hetzner Cloud:
   - Console → Security → SSH Keys → Add SSH Key
   - Paste public key
   - Note the Key ID

3. Add to product configuration:
   - Edit product → Module Settings
   - SSH Key IDs: Enter key ID (e.g., "12345")
   - For multiple keys: "12345,67890"

#### Firewall Configuration

1. Default rules are applied automatically if enabled
2. To customize, edit `hetzner.php` file:
   ```php
   // Find createFirewall function
   // Modify rules as needed
   ```

3. Common ports to allow:
   - 22: SSH
   - 80: HTTP
   - 443: HTTPS
   - 3306: MySQL (if needed)
   - Custom application ports

### Step 9: Verify Everything Works

**Checklist:**

- [ ] Module appears in WHMCS
- [ ] API connection test passes
- [ ] Test order provisions successfully
- [ ] Server appears in Hetzner Console
- [ ] Client can see server details
- [ ] Power controls work (on/off/reboot)
- [ ] Console access works
- [ ] Suspension works
- [ ] Unsuspension works
- [ ] Termination works
- [ ] Package upgrade/downgrade works
- [ ] Email notifications sent (if configured)
- [ ] Hooks execute properly (if installed)

## Troubleshooting

### Module Not Appearing

**Problem**: Module doesn't show in product configuration

**Solutions**:
1. Check file permissions (should be 644)
2. Verify file location: `/modules/servers/hetzner/hetzner.php`
3. Check PHP syntax: `php -l hetzner.php`
4. Clear WHMCS cache: Utilities → System → Purge Template Cache

### API Connection Fails

**Problem**: "Connection failed" or "Authentication failed"

**Solutions**:
1. Verify API token is correct
2. Check token has Read & Write permissions
3. Ensure token hasn't expired
4. Test API manually:
   ```bash
   curl -H "Authorization: Bearer YOUR_TOKEN" \
        https://api.hetzner.cloud/v1/locations
   ```
5. Check firewall allows outbound HTTPS (443)
6. Verify cURL extension is enabled in PHP

### Server Creation Fails

**Problem**: "Server creation failed" or "Unknown error"

**Solutions**:
1. Check module log: System Settings → System Logs → Module Log
2. Verify server type is available in selected location
3. Check Hetzner account has available resources
4. Verify project limits haven't been reached
5. Check image/OS is available
6. Try different location

### IP Not Showing

**Problem**: IP address not displayed in admin/client area

**Solutions**:
1. Wait 60 seconds after creation
2. Check service details in database: `tblhosting` table
3. Verify API response includes IP address
4. Check module log for errors
5. Manually sync by clicking refresh

### Power Controls Don't Work

**Problem**: Power buttons don't respond or error

**Solutions**:
1. Check server ID is stored correctly (in domain field)
2. Verify API token permissions
3. Wait for previous operation to complete
4. Check server status in Hetzner Console
5. Review module log for API errors

### Client Area Shows Error

**Problem**: Client area displays error or blank

**Solutions**:
1. Check template file exists: `templates/clientarea.tpl`
2. Verify Smarty syntax is correct
3. Check file permissions (644)
4. Clear template cache
5. Review WHMCS error logs

## Security Recommendations

1. **API Token Security**
   - Never expose token in client-facing areas
   - Rotate tokens periodically (every 3-6 months)
   - Use separate tokens per WHMCS installation
   - Revoke tokens when no longer needed

2. **SSH Access**
   - Always use SSH keys instead of passwords
   - Disable password authentication on servers
   - Use strong key types (ed25519 or RSA 4096)
   - Rotate keys periodically

3. **Firewall**
   - Always enable firewall for production servers
   - Only allow necessary ports
   - Restrict SSH to known IPs if possible
   - Review rules regularly

4. **Backups**
   - Enable automated backups for critical servers
   - Test restore procedures regularly
   - Consider off-site backup solutions
   - Document backup/restore process

5. **Monitoring**
   - Monitor server status regularly
   - Set up alerts for downtime
   - Review logs periodically
   - Track resource usage

## Support

### Module Issues
- Check WHMCS module log
- Review error messages
- Verify configuration
- Test API connection

### Hetzner Issues
- Hetzner Status: https://status.hetzner.com/
- Hetzner Support: Via console/robot
- Documentation: https://docs.hetzner.com/

### WHMCS Issues
- WHMCS Support: https://www.whmcs.com/support/
- Forums: https://forum.whmcs.com/
- Documentation: https://docs.whmcs.com/

## Maintenance

### Regular Tasks

**Daily**:
- Monitor server provisioning
- Check for failed orders
- Review error logs

**Weekly**:
- Review server status
- Check snapshot usage
- Verify backups
- Update pricing if needed

**Monthly**:
- Rotate API tokens (optional)
- Review and cleanup old snapshots
- Check Hetzner invoices vs WHMCS
- Update module if new version available

**Quarterly**:
- Review security settings
- Update documentation
- Test disaster recovery
- Audit access permissions

## Upgrading

To upgrade the module:

1. **Backup current files**
   ```bash
   cp -r /path/to/whmcs/modules/servers/hetzner \
         /path/to/backup/hetzner-backup-$(date +%Y%m%d)
   ```

2. **Upload new files**
   - Replace all module files
   - Keep configuration intact

3. **Test thoroughly**
   - Test connection
   - Create test order
   - Verify all functions work

4. **Clear cache**
   - Utilities → System → Purge Template Cache

## Uninstallation

To remove the module:

1. **Disable Products**
   - Disable all products using the module
   - Or reassign to different module

2. **Remove Files**
   ```bash
   rm -rf /path/to/whmcs/modules/servers/hetzner
   rm -f /path/to/whmcs/includes/hooks/hetzner_hooks.php
   ```

3. **Cleanup Database** (optional)
   - Remove configuration from products
   - Keep service records for historical data

## Additional Resources

- Module README: See `README.md`
- Configuration Example: See `config.example.php`
- Hetzner Cloud API Docs: https://docs.hetzner.cloud/
- Hetzner Robot API Docs: https://robot.your-server.de/doc/webservice/en.html
- WHMCS Module Dev Guide: https://developers.whmcs.com/provisioning-modules/

## Getting Help

If you encounter issues:

1. Check this installation guide
2. Review README.md
3. Check WHMCS module log
4. Review Hetzner API status
5. Test API connection manually
6. Contact support with:
   - WHMCS version
   - PHP version
   - Error messages
   - Module log entries
   - Steps to reproduce

---

**Installation completed successfully?** Start provisioning servers!
