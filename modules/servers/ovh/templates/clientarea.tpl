<div class="ovh-client-area">
    <style>
        .ovh-client-area {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .ovh-service-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .ovh-info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .ovh-info-row:last-child {
            border-bottom: none;
        }
        .ovh-info-label {
            font-weight: 600;
            color: #495057;
        }
        .ovh-info-value {
            color: #212529;
        }
        .ovh-status-running {
            color: #28a745;
            font-weight: 600;
        }
        .ovh-status-stopped {
            color: #dc3545;
            font-weight: 600;
        }
        .ovh-status-unknown {
            color: #6c757d;
            font-weight: 600;
        }
        .ovh-actions {
            margin-top: 20px;
        }
        .ovh-actions h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #212529;
        }
        .ovh-action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        .ovh-action-button {
            display: inline-block;
            padding: 12px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            text-align: center;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .ovh-action-button:hover {
            background: #0056b3;
            color: white;
            text-decoration: none;
        }
        .ovh-action-button.danger {
            background: #dc3545;
        }
        .ovh-action-button.danger:hover {
            background: #c82333;
        }
        .ovh-manager-link {
            margin-top: 20px;
            padding: 15px;
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        .ovh-manager-link a {
            color: #007bff;
            font-weight: 600;
            text-decoration: none;
        }
        .ovh-manager-link a:hover {
            text-decoration: underline;
        }
        .ovh-error {
            padding: 15px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            color: #721c24;
        }
        .ovh-ip-list {
            margin: 5px 0;
        }
        .ovh-ip-item {
            display: inline-block;
            padding: 4px 8px;
            margin: 2px;
            background: #e9ecef;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
        }
    </style>

    {if $error}
        <div class="ovh-error">
            <strong>Error:</strong> {$error}
        </div>
    {else}
        <div class="ovh-service-info">
            <h2 style="margin-top: 0; color: #212529;">Service Information</h2>

            {if $service_info.type}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">Service Type:</span>
                    <span class="ovh-info-value">{$service_info.type}</span>
                </div>
            {/if}

            {if $service_info.name}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">Service Name:</span>
                    <span class="ovh-info-value">{$service_info.name}</span>
                </div>
            {/if}

            {if $service_info.state}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">Status:</span>
                    <span class="ovh-info-value">
                        {if $service_info.state == 'running' || $service_info.state == 'active'}
                            <span class="ovh-status-running">● Running</span>
                        {elseif $service_info.state == 'stopped' || $service_info.state == 'halted'}
                            <span class="ovh-status-stopped">● Stopped</span>
                        {else}
                            <span class="ovh-status-unknown">● {$service_info.state|ucfirst}</span>
                        {/if}
                    </span>
                </div>
            {/if}

            {if $service_info.ip}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">IP Address:</span>
                    <span class="ovh-info-value">
                        <span class="ovh-ip-item">{$service_info.ip}</span>
                    </span>
                </div>
            {/if}

            {if $service_info.ips}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">IP Addresses:</span>
                    <span class="ovh-info-value">
                        <div class="ovh-ip-list">
                            {foreach from=$service_info.ips item=ip}
                                <span class="ovh-ip-item">{$ip}</span>
                            {/foreach}
                        </div>
                    </span>
                </div>
            {/if}

            {if $service_info.reverse}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">Reverse DNS:</span>
                    <span class="ovh-info-value">{$service_info.reverse}</span>
                </div>
            {/if}

            {if $service_info.datacenter}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">Datacenter:</span>
                    <span class="ovh-info-value">{$service_info.datacenter}</span>
                </div>
            {/if}

            {if $service_info.model}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">Model:</span>
                    <span class="ovh-info-value">{$service_info.model}</span>
                </div>
            {/if}

            {if $service_info.memory}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">Memory:</span>
                    <span class="ovh-info-value">{$service_info.memory}</span>
                </div>
            {/if}

            {if $service_info.vcores}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">vCores:</span>
                    <span class="ovh-info-value">{$service_info.vcores}</span>
                </div>
            {/if}

            {if $service_info.zone}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">Zone:</span>
                    <span class="ovh-info-value">{$service_info.zone}</span>
                </div>
            {/if}

            {if $service_info.monitoring !== null}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">Monitoring:</span>
                    <span class="ovh-info-value">
                        {if $service_info.monitoring}
                            <span class="ovh-status-running">✓ Enabled</span>
                        {else}
                            <span class="ovh-status-stopped">✗ Disabled</span>
                        {/if}
                    </span>
                </div>
            {/if}

            {if $service_info.project_id}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">Project ID:</span>
                    <span class="ovh-info-value">{$service_info.project_id}</span>
                </div>
            {/if}

            {if $service_info.error}
                <div class="ovh-info-row">
                    <span class="ovh-info-label">Error:</span>
                    <span class="ovh-info-value" style="color: #dc3545;">{$service_info.error}</span>
                </div>
            {/if}
        </div>

        {if $actions}
            <div class="ovh-actions">
                <h3>Available Actions</h3>
                <div class="ovh-action-grid">
                    {foreach from=$actions item=action}
                        <button class="ovh-action-button {if $action.action == 'reinstall'}danger{/if}"
                                onclick="performOVHAction('{$action.action}', '{$action.label}')">
                            {$action.label}
                        </button>
                    {/foreach}
                </div>
            </div>

            <script>
            function performOVHAction(action, label) {
                if (action === 'reinstall' || action === 'rescue') {
                    if (!confirm('Are you sure you want to ' + label + '? This action may cause downtime.')) {
                        return;
                    }
                }

                // In a real implementation, this would call the WHMCS module function
                alert('Action "' + label + '" initiated. Please check your email for updates.');

                // For actual implementation, you would use AJAX to call the module function
                // Example:
                // jQuery.post('clientarea.php?action=productdetails', {
                //     id: '{$serviceid}',
                //     modop: 'custom',
                //     a: action
                // }).done(function(response) {
                //     alert('Action completed');
                //     location.reload();
                // });
            }
            </script>
        {/if}

        {if $ovh_manager_url}
            <div class="ovh-manager-link">
                <strong>OVH Manager:</strong>
                For advanced management options, please visit the
                <a href="{$ovh_manager_url}" target="_blank" rel="noopener noreferrer">OVH Manager</a>
            </div>
        {/if}
    {/if}
</div>
