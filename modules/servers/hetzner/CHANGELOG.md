# Changelog

All notable changes to the Hetzner Cloud & Robot API module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-11-13

### Added
- Initial release of Hetzner Cloud & Robot API integration module
- Full Hetzner Cloud API v1 support
- Hetzner Robot API support for dedicated servers
- Automated server provisioning for Cloud instances
- Support for all server types (CX, CPX, CCX series)
- Multiple datacenter location support (Germany, Finland, USA)
- All major operating systems (Ubuntu, Debian, CentOS, Rocky, Alma, Fedora)
- Power management (start, stop, reboot, reset)
- Snapshot creation and management
- Web console access integration
- Firewall management and configuration
- Volume attachment support
- IPv4 and IPv6 networking
- Reverse DNS management
- SSH key integration
- Package upgrade/downgrade (resize) functionality
- Suspension/unsuspension support
- Termination/deletion support
- Admin area custom buttons (reboot, reset, rescue, snapshot, console)
- Client area custom buttons (power on/off, reboot, console)
- Comprehensive admin service tab with server details
- Client area template with server information and controls
- API connection testing
- Detailed module logging
- Error handling and validation
- Comprehensive documentation (README.md, INSTALL.md)
- Configuration example file
- Hooks for automation (welcome emails, logging, monitoring)
- Admin dashboard widget for server statistics
- Pre-upgrade snapshot automation
- Weekly snapshot cleanup automation
- Daily health check automation
- Module logo (SVG)

### Features
- **Cloud Server Management**
  - Create servers with custom configuration
  - Power on/off/reboot
  - Force reset (power cycle)
  - Access web console
  - Create snapshots on demand
  - Restore from snapshots
  - Resize servers (upgrade/downgrade)
  - Configure firewalls
  - Attach volumes
  - Enable/disable IPv6
  - Configure reverse DNS
  - SSH key integration

- **Dedicated Server Support**
  - Manual activation workflow
  - Rescue system activation
  - Server reset operations
  - IPMI console access
  - Reverse DNS management
  - Subnet management
  - Robot panel integration

- **Automation**
  - Auto-provisioning on payment
  - Auto-suspension on non-payment
  - Auto-termination after grace period
  - Welcome email with server details
  - Pre-upgrade snapshots
  - Snapshot cleanup (retention policy)
  - Daily health checks
  - Activity logging

- **Admin Features**
  - Test API connection
  - View server details and specs
  - Power controls
  - Rescue mode activation
  - Snapshot management
  - Console access
  - Force reset
  - Dashboard statistics widget
  - Comprehensive logging

- **Client Features**
  - View server status and details
  - Power management controls
  - Access web console
  - View snapshots
  - SSH connection details
  - Direct links to Hetzner console
  - Documentation links

### Technical Details
- Written in PHP 7.4+ compatible code
- Follows WHMCS module standards
- RESTful API implementation
- JSON data handling
- Comprehensive error handling
- Secure credential management
- cURL-based HTTP client
- Smarty template integration
- Hook system integration
- Database interaction via WHMCS functions

### Documentation
- Complete README with all features
- Detailed installation guide (INSTALL.md)
- Configuration examples
- Troubleshooting guide
- API endpoint documentation
- Security best practices
- Pricing recommendations
- Usage examples

### Security
- Secure API token handling
- Password encryption
- Input validation and sanitization
- Error message sanitization
- Secure API communication (HTTPS)
- Token rotation recommendations
- SSH key security guidelines
- Firewall best practices

## [Unreleased]

### Planned Features
- Load balancer integration
- Floating IP management
- Network management (private networks)
- Volume snapshot support
- Automated backup scheduling
- Billing integration for usage-based pricing
- Server metrics and graphs
- Alert notifications
- Multi-language support
- Custom OS image support
- Placement group support
- Server labels and tagging
- Cost optimization suggestions
- Automated scaling
- Server templates
- One-click applications
- Database cluster support
- Object storage integration

### Known Issues
- None reported yet

### Deprecations
- None

## Version History

### Version Numbering
- **Major** (1.x.x): Breaking changes, major new features
- **Minor** (x.1.x): New features, backward compatible
- **Patch** (x.x.1): Bug fixes, minor improvements

## Upgrade Notes

### Upgrading to 1.0.0
- Initial release - fresh installation only

## Support

For support, issues, or feature requests:
- Check documentation (README.md, INSTALL.md)
- Review troubleshooting guide
- Check WHMCS module logs
- Verify Hetzner API status
- Contact support with detailed information

## Credits

Developed for seamless Hetzner Cloud and Robot API integration with WHMCS.

## License

This module is provided for use with WHMCS installations.
