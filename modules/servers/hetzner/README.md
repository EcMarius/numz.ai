# Hetzner Cloud & Robot API Integration Module

Complete WHMCS provisioning module for Hetzner Cloud and Dedicated Servers.

## Features

### Hetzner Cloud API
- Automated VPS provisioning (CX, CPX, CCX series)
- Multiple datacenter locations (Germany, Finland, USA)
- Flexible server sizing and scaling
- Snapshot management
- Firewall configuration
- Volume management
- IPv4 and IPv6 support
- Web console access
- Power management (start, stop, reboot)
- Reverse DNS configuration

### Hetzner Robot API
- Dedicated server management
- Rescue system activation
- Server reset operations
- IPMI console access
- Reverse DNS management
- Subnet configuration
- Server status monitoring

## Installation

1. **Copy Module Files**
   ```bash
   cp -r hetzner /path/to/whmcs/modules/servers/
   ```

2. **Set Permissions**
   ```bash
   chmod 755 /path/to/whmcs/modules/servers/hetzner
   chmod 644 /path/to/whmcs/modules/servers/hetzner/*.php
   ```

3. **Create Product/Service**
   - Navigate to: System Settings → Products/Services → Create New Product
   - Module Settings: Select "Hetzner Cloud & Dedicated"
   - Configure module settings (see Configuration section)

## Configuration

### API Credentials

#### For Cloud Services:
1. Log in to [Hetzner Cloud Console](https://console.hetzner.cloud/)
2. Select your project
3. Go to Security → API Tokens
4. Generate a new token with Read & Write permissions
5. Copy the token to WHMCS product configuration

#### For Dedicated Servers:
1. Log in to [Hetzner Robot](https://robot.your-server.de/)
2. Go to Settings → Account
3. Note your Robot username and password
4. Add credentials to WHMCS product configuration

### Product Configuration

1. **Service Type**
   - Cloud (VPS): Automated provisioning via Cloud API
   - Dedicated: Manual activation with Robot API management

2. **Server Type/Size**
   - **CX Series** (Intel): Balanced CPU/RAM - Good for general purposes
     - cx11: 1 vCPU, 2GB RAM, 20GB SSD
     - cx21: 2 vCPU, 4GB RAM, 40GB SSD
     - cx31: 2 vCPU, 8GB RAM, 80GB SSD
     - cx41: 4 vCPU, 16GB RAM, 160GB SSD
     - cx51: 8 vCPU, 32GB RAM, 240GB SSD

   - **CPX Series** (AMD): High performance - Better price/performance
     - cpx11: 2 vCPU, 2GB RAM, 40GB SSD
     - cpx21: 3 vCPU, 4GB RAM, 80GB SSD
     - cpx31: 4 vCPU, 8GB RAM, 160GB SSD
     - cpx41: 8 vCPU, 16GB RAM, 240GB SSD
     - cpx51: 16 vCPU, 32GB RAM, 360GB SSD

   - **CCX Series** (Dedicated vCPU): Dedicated resources
     - ccx12: 2 Dedicated vCPU, 8GB RAM, 80GB SSD
     - ccx22: 4 Dedicated vCPU, 16GB RAM, 160GB SSD
     - ccx32: 8 Dedicated vCPU, 32GB RAM, 240GB SSD
     - ccx42: 16 Dedicated vCPU, 64GB RAM, 360GB SSD
     - ccx52: 32 Dedicated vCPU, 128GB RAM, 600GB SSD

3. **Datacenter Locations**
   - **nbg1**: Nuremberg, Germany (Europe)
   - **fsn1**: Falkenstein, Germany (Europe)
   - **hel1**: Helsinki, Finland (Europe)
   - **ash**: Ashburn, Virginia, USA (US East)
   - **hil**: Hillsboro, Oregon, USA (US West)

4. **Operating Systems**
   - Ubuntu 22.04 LTS (Recommended)
   - Ubuntu 20.04 LTS
   - Debian 11 & 12
   - CentOS Stream 9
   - Rocky Linux 9
   - AlmaLinux 9
   - Fedora 38

5. **Additional Options**
   - Enable IPv6: Yes/No
   - Enable Backups: Yes/No (additional cost)
   - SSH Keys: Comma-separated key IDs from Hetzner Cloud
   - Firewall: Enable/Disable automatic firewall creation

## Module Functions

### Standard Functions
- **CreateAccount**: Creates new cloud server or activates dedicated server
- **SuspendAccount**: Powers off cloud server
- **UnsuspendAccount**: Powers on cloud server
- **TerminateAccount**: Deletes cloud server
- **ChangePackage**: Resizes cloud server (upgrade/downgrade)
- **TestConnection**: Validates API credentials

### Admin Custom Buttons
- **Reboot Server**: Graceful reboot
- **Force Reset**: Hard reset (power cycle)
- **Enable Rescue**: Activate rescue system
- **Create Snapshot**: Create server snapshot
- **Get Console**: Generate web console access URL

### Client Area Buttons
- **Power On**: Start the server
- **Power Off**: Shutdown the server
- **Reboot**: Restart the server
- **Request Console**: Access web console

## Client Area Features

### Cloud Servers
- Real-time server status
- Server specifications display
- IP address information (IPv4 & IPv6)
- Power management controls
- Console access
- Snapshot listing
- SSH connection details
- Quick links to Hetzner console

### Dedicated Servers
- Direct links to Robot panel
- Management instructions
- Documentation links

## API Endpoints

### Cloud API
- Base URL: `https://api.hetzner.cloud/v1`
- Documentation: https://docs.hetzner.cloud/

### Robot API
- Base URL: `https://robot-ws.your-server.de`
- Documentation: https://robot.your-server.de/doc/webservice/en.html

## Usage Examples

### Creating SSH Keys
```bash
# Generate SSH key
ssh-keygen -t ed25519 -C "your@email.com"

# Add to Hetzner Cloud Console
# Go to: Security → SSH Keys → Add SSH Key
# Copy the ID and add to product configuration
```

### Firewall Rules Example
```json
[
  {
    "direction": "in",
    "protocol": "tcp",
    "port": "22",
    "source_ips": ["0.0.0.0/0", "::/0"]
  },
  {
    "direction": "in",
    "protocol": "tcp",
    "port": "80",
    "source_ips": ["0.0.0.0/0", "::/0"]
  },
  {
    "direction": "in",
    "protocol": "tcp",
    "port": "443",
    "source_ips": ["0.0.0.0/0", "::/0"]
  }
]
```

### Accessing Server
```bash
# SSH access (password from welcome email)
ssh root@<server-ip>

# Or with SSH key
ssh -i ~/.ssh/id_ed25519 root@<server-ip>
```

## Troubleshooting

### Common Issues

**1. "Server ID not found"**
- Solution: Server may not have been created. Check module log in WHMCS.

**2. "API authentication failed"**
- Solution: Verify API token has read & write permissions.

**3. "Location not available"**
- Solution: Some server types aren't available in all locations.

**4. "Server type not found"**
- Solution: Check if server type is still offered by Hetzner.

**5. Power operations fail**
- Solution: Wait for previous operation to complete. Check server status.

### Debug Mode
Enable module logging in WHMCS:
```
System Settings → System Logs → Module Log
```

All API calls are logged with requests and responses for debugging.

## Pricing Considerations

### Cloud Pricing (as of 2024)
- CX11: ~€3.79/month
- CPX11: ~€4.15/month (better value)
- CCX12: ~€12.15/month (dedicated CPU)
- Add 20% for automated backups
- Traffic: 20TB included, free after

### Dedicated Pricing
- Varies by server type
- Check [Hetzner Server Auction](https://www.hetzner.com/sb)
- Setup fees may apply

## Security Best Practices

1. **API Tokens**
   - Use separate tokens per WHMCS installation
   - Rotate tokens regularly
   - Never share tokens

2. **SSH Keys**
   - Always use SSH keys instead of passwords
   - Use strong key types (ed25519 or RSA 4096)
   - Remove unused keys

3. **Firewalls**
   - Enable firewall for all servers
   - Only allow required ports
   - Restrict SSH to known IPs if possible

4. **Backups**
   - Enable automated backups for critical servers
   - Test restore procedures regularly
   - Consider off-site backups

5. **Updates**
   - Keep operating system updated
   - Enable automatic security updates
   - Monitor security advisories

## Support

### Hetzner Support
- Cloud Console: https://console.hetzner.cloud/
- Robot Panel: https://robot.your-server.de/
- Documentation: https://docs.hetzner.com/
- Status Page: https://status.hetzner.com/
- Support: Available via console/robot

### Module Support
- Check WHMCS module logs
- Verify API credentials
- Test API connection in product settings
- Review error messages in admin area

## Changelog

### Version 1.0 (2024)
- Initial release
- Full Hetzner Cloud API integration
- Hetzner Robot API support
- Automated provisioning
- Power management
- Snapshot support
- Console access
- Firewall management
- Reverse DNS support
- Comprehensive client area
- Admin management tools

## License

This module is provided as-is for use with WHMCS installations.

## Credits

Developed for complete Hetzner Cloud and Robot API integration with WHMCS.

## Additional Resources

- [Hetzner Cloud API Documentation](https://docs.hetzner.cloud/)
- [Hetzner Robot API Documentation](https://robot.your-server.de/doc/webservice/en.html)
- [WHMCS Module Development Guide](https://developers.whmcs.com/provisioning-modules/)
- [Hetzner Cloud Pricing](https://www.hetzner.com/cloud)
- [Hetzner Server Auction](https://www.hetzner.com/sb)
