<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    |
    | Current version of the application. This should follow semantic versioning.
    | Format: MAJOR.MINOR.PATCH (e.g., 1.0.0)
    |
    */
    'current_version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Update Check Endpoint
    |--------------------------------------------------------------------------
    |
    | The URL endpoint to check for available updates.
    | This can be a GitHub releases API, custom update server, or other source.
    |
    */
    'check_url' => env('UPDATE_CHECK_URL', 'https://api.github.com/repos/yourusername/numz.ai/releases/latest'),

    /*
    |--------------------------------------------------------------------------
    | Update Server Token
    |--------------------------------------------------------------------------
    |
    | Authentication token for accessing the update server (if required).
    | For GitHub, use a personal access token.
    |
    */
    'server_token' => env('UPDATE_SERVER_TOKEN', null),

    /*
    |--------------------------------------------------------------------------
    | Auto Check for Updates
    |--------------------------------------------------------------------------
    |
    | Automatically check for updates daily. Set to false to disable.
    |
    */
    'auto_check' => env('AUTO_CHECK_UPDATES', true),

    /*
    |--------------------------------------------------------------------------
    | Update Check Interval
    |--------------------------------------------------------------------------
    |
    | How often to check for updates (in hours).
    |
    */
    'check_interval' => env('UPDATE_CHECK_INTERVAL', 24),

    /*
    |--------------------------------------------------------------------------
    | Backup Before Update
    |--------------------------------------------------------------------------
    |
    | Create a full backup before applying updates. Highly recommended.
    |
    */
    'backup_before_update' => env('BACKUP_BEFORE_UPDATE', true),

    /*
    |--------------------------------------------------------------------------
    | Backup Retention
    |--------------------------------------------------------------------------
    |
    | Number of backup versions to keep. Older backups will be deleted.
    |
    */
    'backup_retention' => env('BACKUP_RETENTION', 3),

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode During Update
    |--------------------------------------------------------------------------
    |
    | Put the application in maintenance mode during updates.
    |
    */
    'maintenance_mode' => env('MAINTENANCE_MODE_DURING_UPDATE', true),

    /*
    |--------------------------------------------------------------------------
    | Update Download Path
    |--------------------------------------------------------------------------
    |
    | Temporary directory for downloading and extracting updates.
    |
    */
    'download_path' => storage_path('app/updates'),

    /*
    |--------------------------------------------------------------------------
    | Backup Path
    |--------------------------------------------------------------------------
    |
    | Directory for storing backups before updates.
    |
    */
    'backup_path' => storage_path('app/backups'),

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    |
    | Paths to exclude from updates (will not be overwritten).
    |
    */
    'excluded_paths' => [
        'storage/app/public',
        'storage/logs',
        '.env',
        '.env.example',
        'bootstrap/cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | Required PHP Extensions
    |--------------------------------------------------------------------------
    |
    | PHP extensions required for the update process.
    |
    */
    'required_extensions' => [
        'zip',
        'openssl',
        'json',
        'mbstring',
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum PHP Version
    |--------------------------------------------------------------------------
    |
    | Minimum PHP version required.
    |
    */
    'min_php_version' => '8.2.0',

    /*
    |--------------------------------------------------------------------------
    | Update Channels
    |--------------------------------------------------------------------------
    |
    | Available update channels: 'stable', 'beta', 'alpha'
    |
    */
    'channel' => env('UPDATE_CHANNEL', 'stable'),

    /*
    |--------------------------------------------------------------------------
    | Allow Downgrade
    |--------------------------------------------------------------------------
    |
    | Allow downgrading to previous versions. Use with caution.
    |
    */
    'allow_downgrade' => env('ALLOW_DOWNGRADE', false),

    /*
    |--------------------------------------------------------------------------
    | Rollback Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait before automatic rollback on failure.
    |
    */
    'rollback_timeout' => env('ROLLBACK_TIMEOUT', 300),

    /*
    |--------------------------------------------------------------------------
    | Post-Update Commands
    |--------------------------------------------------------------------------
    |
    | Artisan commands to run after successful update.
    |
    */
    'post_update_commands' => [
        'cache:clear',
        'config:clear',
        'route:clear',
        'view:clear',
        'optimize',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure how update notifications are sent.
    |
    */
    'notifications' => [
        'enabled' => true,
        'channels' => ['database', 'mail'], // database, mail, slack
        'notify_users' => ['admin'], // User roles to notify
    ],

    /*
    |--------------------------------------------------------------------------
    | Update Verification
    |--------------------------------------------------------------------------
    |
    | Verify update package integrity using checksums.
    |
    */
    'verify_checksum' => env('VERIFY_UPDATE_CHECKSUM', true),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Log all update operations for debugging and audit.
    |
    */
    'log_updates' => true,
    'log_channel' => 'daily',
];
