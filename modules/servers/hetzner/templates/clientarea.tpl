<div class="hetzner-server-panel">
    <style>
        .hetzner-server-panel {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .hetzner-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .hetzner-card h3 {
            margin-top: 0;
            color: #d50c2d;
            border-bottom: 2px solid #d50c2d;
            padding-bottom: 10px;
        }
        .hetzner-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }
        .status-running {
            background: #d4edda;
            color: #155724;
        }
        .status-off {
            background: #f8d7da;
            color: #721c24;
        }
        .status-starting, .status-stopping {
            background: #fff3cd;
            color: #856404;
        }
        .hetzner-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .hetzner-info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #d50c2d;
        }
        .hetzner-info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .hetzner-info-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .hetzner-btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        .hetzner-btn-primary {
            background: #d50c2d;
            color: white;
        }
        .hetzner-btn-primary:hover {
            background: #b00a26;
            color: white;
        }
        .hetzner-btn-secondary {
            background: #6c757d;
            color: white;
        }
        .hetzner-btn-secondary:hover {
            background: #545b62;
            color: white;
        }
        .hetzner-btn-success {
            background: #28a745;
            color: white;
        }
        .hetzner-btn-success:hover {
            background: #218838;
            color: white;
        }
        .hetzner-btn-danger {
            background: #dc3545;
            color: white;
        }
        .hetzner-btn-danger:hover {
            background: #c82333;
            color: white;
        }
        .hetzner-snapshot-list {
            list-style: none;
            padding: 0;
        }
        .hetzner-snapshot-item {
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #007bff;
        }
        .hetzner-snapshot-name {
            font-weight: bold;
            color: #333;
        }
        .hetzner-snapshot-date {
            color: #666;
            font-size: 12px;
        }
        .hetzner-action-buttons {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
        }
    </style>

    {if $service_type eq 'cloud'}
        <!-- Cloud Server Management -->
        <div class="hetzner-card">
            <h3>Server Status & Information</h3>

            <div style="margin: 20px 0;">
                <strong>Current Status:</strong>
                <span class="hetzner-status status-{$server.status}">{$server.status|ucfirst}</span>
            </div>

            <div class="hetzner-info-grid">
                <div class="hetzner-info-item">
                    <div class="hetzner-info-label">Server Name</div>
                    <div class="hetzner-info-value">{$server.name}</div>
                </div>

                <div class="hetzner-info-item">
                    <div class="hetzner-info-label">Server ID</div>
                    <div class="hetzner-info-value">#{$server.id}</div>
                </div>

                <div class="hetzner-info-item">
                    <div class="hetzner-info-label">IPv4 Address</div>
                    <div class="hetzner-info-value">{$ip_address}</div>
                </div>

                <div class="hetzner-info-item">
                    <div class="hetzner-info-label">IPv6 Address</div>
                    <div class="hetzner-info-value" style="font-size: 14px;">{$ipv6_address}</div>
                </div>

                <div class="hetzner-info-item">
                    <div class="hetzner-info-label">Server Type</div>
                    <div class="hetzner-info-value">{$server.server_type.name}</div>
                </div>

                <div class="hetzner-info-item">
                    <div class="hetzner-info-label">Location</div>
                    <div class="hetzner-info-value">{$server.datacenter.location.name}</div>
                </div>

                <div class="hetzner-info-item">
                    <div class="hetzner-info-label">CPU Cores</div>
                    <div class="hetzner-info-value">{$server.server_type.cores}</div>
                </div>

                <div class="hetzner-info-item">
                    <div class="hetzner-info-label">RAM</div>
                    <div class="hetzner-info-value">{$server.server_type.memory} GB</div>
                </div>

                <div class="hetzner-info-item">
                    <div class="hetzner-info-label">Disk Space</div>
                    <div class="hetzner-info-value">{$server.server_type.disk} GB</div>
                </div>
            </div>
        </div>

        <!-- Power Control -->
        <div class="hetzner-card">
            <h3>Power Management</h3>
            <div class="hetzner-action-buttons">
                <form method="post" action="" style="display: inline;">
                    <input type="hidden" name="modop" value="custom">
                    <input type="hidden" name="a" value="poweron">
                    <button type="submit" class="hetzner-btn hetzner-btn-success" {if $server.status eq 'running'}disabled{/if}>
                        <i class="fas fa-power-off"></i> Power On
                    </button>
                </form>

                <form method="post" action="" style="display: inline;">
                    <input type="hidden" name="modop" value="custom">
                    <input type="hidden" name="a" value="poweroff">
                    <button type="submit" class="hetzner-btn hetzner-btn-danger" {if $server.status eq 'off'}disabled{/if}>
                        <i class="fas fa-power-off"></i> Power Off
                    </button>
                </form>

                <form method="post" action="" style="display: inline;">
                    <input type="hidden" name="modop" value="custom">
                    <input type="hidden" name="a" value="clientreboot">
                    <button type="submit" class="hetzner-btn hetzner-btn-primary" {if $server.status neq 'running'}disabled{/if}>
                        <i class="fas fa-sync"></i> Reboot
                    </button>
                </form>

                <form method="post" action="" style="display: inline;">
                    <input type="hidden" name="modop" value="custom">
                    <input type="hidden" name="a" value="clientconsole">
                    <button type="submit" class="hetzner-btn hetzner-btn-secondary">
                        <i class="fas fa-terminal"></i> Access Console
                    </button>
                </form>
            </div>
        </div>

        <!-- Snapshots -->
        {if $snapshots}
        <div class="hetzner-card">
            <h3>Available Snapshots</h3>
            <ul class="hetzner-snapshot-list">
                {foreach from=$snapshots item=snapshot}
                <li class="hetzner-snapshot-item">
                    <div class="hetzner-snapshot-name">
                        <i class="fas fa-camera"></i> {$snapshot.description}
                    </div>
                    <div class="hetzner-snapshot-date">
                        Created: {$snapshot.created|date_format:"%Y-%m-%d %H:%M:%S"} |
                        Size: {$snapshot.image_size} GB |
                        ID: {$snapshot.id}
                    </div>
                </li>
                {/foreach}
            </ul>
        </div>
        {/if}

        <!-- Access Information -->
        <div class="hetzner-card">
            <h3>Access Information</h3>
            <div style="background: #fff3cd; padding: 15px; border-radius: 6px; border-left: 4px solid #ffc107;">
                <p><strong>SSH Access:</strong></p>
                <code style="background: #f8f9fa; padding: 10px; display: block; border-radius: 4px;">
                    ssh root@{$ip_address}
                </code>
                <p style="margin-top: 15px;">
                    <small>
                        <i class="fas fa-info-circle"></i>
                        Use the password provided in your welcome email or your SSH key if configured.
                    </small>
                </p>
            </div>
        </div>

        <!-- Useful Links -->
        <div class="hetzner-card">
            <h3>Useful Links</h3>
            <div style="padding: 10px;">
                <a href="https://console.hetzner.cloud/" target="_blank" class="hetzner-btn hetzner-btn-primary">
                    <i class="fas fa-external-link-alt"></i> Hetzner Cloud Console
                </a>
                <a href="https://docs.hetzner.com/cloud/" target="_blank" class="hetzner-btn hetzner-btn-secondary">
                    <i class="fas fa-book"></i> Documentation
                </a>
                <a href="https://status.hetzner.com/" target="_blank" class="hetzner-btn hetzner-btn-secondary">
                    <i class="fas fa-heartbeat"></i> Status Page
                </a>
            </div>
        </div>

    {elseif $service_type eq 'dedicated'}
        <!-- Dedicated Server -->
        <div class="hetzner-card">
            <h3>Dedicated Server Management</h3>
            <div style="background: #d1ecf1; padding: 20px; border-radius: 6px; border-left: 4px solid #0c5460;">
                <p><strong>Your dedicated server is managed via the Hetzner Robot panel.</strong></p>
                <p>Please use the Robot panel for all server management tasks including:</p>
                <ul>
                    <li>Server resets and reboots</li>
                    <li>Rescue system access</li>
                    <li>IP management and reverse DNS</li>
                    <li>Firewall configuration</li>
                    <li>Traffic graphs</li>
                </ul>
                <a href="https://robot.your-server.de/" target="_blank" class="hetzner-btn hetzner-btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-external-link-alt"></i> Open Hetzner Robot Panel
                </a>
            </div>
        </div>

        <div class="hetzner-card">
            <h3>Support & Documentation</h3>
            <div style="padding: 10px;">
                <a href="https://docs.hetzner.com/robot/" target="_blank" class="hetzner-btn hetzner-btn-secondary">
                    <i class="fas fa-book"></i> Robot Documentation
                </a>
                <a href="https://robot.your-server.de/support/index" target="_blank" class="hetzner-btn hetzner-btn-secondary">
                    <i class="fas fa-question-circle"></i> Support Center
                </a>
            </div>
        </div>
    {/if}
</div>
