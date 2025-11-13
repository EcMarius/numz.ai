<div class="cpanel-client-area card">
    <div class="card-header">
        <h3 class="card-title">cPanel Account Information</h3>
    </div>
    <div class="card-body">
        {if $error}
            <div class="alert alert-danger">
                <strong>Error:</strong> {$error}
            </div>
        {else}
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td><strong>Username:</strong></td>
                                <td>{$username}</td>
                            </tr>
                            <tr>
                                <td><strong>Domain:</strong></td>
                                <td>{$domain}</td>
                            </tr>
                            <tr>
                                <td><strong>Server IP:</strong></td>
                                <td>{$server_ip}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6 text-center">
                    <div class="mb-3">
                        <a href="{$cpanel_url}" target="_blank" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Login to cPanel
                        </a>
                    </div>
                    <p class="text-muted small">
                        Click the button above to access your cPanel control panel.
                    </p>
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-12">
                    <h5>Quick Links</h5>
                    <div class="btn-group" role="group">
                        <a href="{$cpanel_url}" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-tachometer-alt"></i> cPanel Dashboard
                        </a>
                        <a href="{$cpanel_url|replace:':2083':':2096'}" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-envelope"></i> Webmail
                        </a>
                        <a href="{$cpanel_url}?app=file_manager" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-folder"></i> File Manager
                        </a>
                        <a href="{$cpanel_url}?app=mysql" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-database"></i> MySQL Databases
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <div class="alert alert-info">
                    <strong><i class="fas fa-info-circle"></i> Help:</strong>
                    <ul class="mb-0">
                        <li>Use the cPanel control panel to manage your hosting account</li>
                        <li>Access webmail to check your email accounts</li>
                        <li>Manage files, databases, email accounts, and more</li>
                        <li>For support, please contact our team</li>
                    </ul>
                </div>
            </div>
        {/if}
    </div>
</div>

<style>
.cpanel-client-area .card {
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.cpanel-client-area .btn-lg {
    padding: 12px 30px;
    font-size: 18px;
}

.cpanel-client-area .btn-group {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.cpanel-client-area .btn-group .btn {
    flex: 1;
    min-width: 150px;
}
</style>
