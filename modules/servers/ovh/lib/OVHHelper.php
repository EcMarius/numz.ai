<?php
/**
 * OVH Helper Class
 *
 * Utility functions for OVH module operations
 */

namespace OVH\Module;

class OVHHelper
{
    /**
     * Format bytes to human-readable format
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Format bandwidth for display
     */
    public static function formatBandwidth($bitsPerSecond)
    {
        $units = ['bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps'];
        $value = $bitsPerSecond;

        for ($i = 0; $value > 1000 && $i < count($units) - 1; $i++) {
            $value /= 1000;
        }

        return round($value, 2) . ' ' . $units[$i];
    }

    /**
     * Parse OVH service name format
     */
    public static function parseServiceName($serviceName)
    {
        // Extract components from service name (e.g., ns123456.ip-1-2-3.eu)
        $parts = explode('.', $serviceName);

        return [
            'id' => $parts[0] ?? '',
            'ip_segment' => $parts[1] ?? '',
            'region' => $parts[2] ?? '',
            'full' => $serviceName,
        ];
    }

    /**
     * Validate IP address
     */
    public static function validateIP($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate IPv4 address
     */
    public static function validateIPv4($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validate IPv6 address
     */
    public static function validateIPv6($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Get OVH datacenter locations
     */
    public static function getDatacenters()
    {
        return [
            // Europe
            'bhs' => ['name' => 'Beauharnois, Canada', 'continent' => 'NA', 'country' => 'CA'],
            'gra' => ['name' => 'Gravelines, France', 'continent' => 'EU', 'country' => 'FR'],
            'lon' => ['name' => 'London, United Kingdom', 'continent' => 'EU', 'country' => 'GB'],
            'par' => ['name' => 'Paris, France', 'continent' => 'EU', 'country' => 'FR'],
            'rbx' => ['name' => 'Roubaix, France', 'continent' => 'EU', 'country' => 'FR'],
            'sbg' => ['name' => 'Strasbourg, France', 'continent' => 'EU', 'country' => 'FR'],
            'waw' => ['name' => 'Warsaw, Poland', 'continent' => 'EU', 'country' => 'PL'],

            // Americas
            'vin' => ['name' => 'Vint Hill, USA', 'continent' => 'NA', 'country' => 'US'],
            'hil' => ['name' => 'Hillsboro, USA', 'continent' => 'NA', 'country' => 'US'],

            // Asia-Pacific
            'sgp' => ['name' => 'Singapore', 'continent' => 'AS', 'country' => 'SG'],
            'syd' => ['name' => 'Sydney, Australia', 'continent' => 'OC', 'country' => 'AU'],
        ];
    }

    /**
     * Get datacenter name from code
     */
    public static function getDatacenterName($code)
    {
        $datacenters = self::getDatacenters();
        return $datacenters[$code]['name'] ?? $code;
    }

    /**
     * Sanitize hostname
     */
    public static function sanitizeHostname($hostname)
    {
        // Remove invalid characters
        $hostname = strtolower($hostname);
        $hostname = preg_replace('/[^a-z0-9.-]/', '', $hostname);

        // Remove leading/trailing dots and hyphens
        $hostname = trim($hostname, '.-');

        return $hostname;
    }

    /**
     * Generate random password
     */
    public static function generatePassword($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    /**
     * Parse OVH API error
     */
    public static function parseAPIError($error)
    {
        if (is_string($error)) {
            return $error;
        }

        if (is_array($error)) {
            return $error['message'] ?? $error['error'] ?? json_encode($error);
        }

        if (is_object($error) && isset($error->message)) {
            return $error->message;
        }

        return 'Unknown error occurred';
    }

    /**
     * Get OS display name
     */
    public static function getOSDisplayName($osCode)
    {
        $osNames = [
            'debian11_64' => 'Debian 11 (Bullseye)',
            'debian12_64' => 'Debian 12 (Bookworm)',
            'ubuntu2004_64' => 'Ubuntu 20.04 LTS',
            'ubuntu2204_64' => 'Ubuntu 22.04 LTS',
            'ubuntu2404_64' => 'Ubuntu 24.04 LTS',
            'centos7_64' => 'CentOS 7',
            'centos8_64' => 'CentOS Stream 8',
            'centos9_64' => 'CentOS Stream 9',
            'almalinux8_64' => 'AlmaLinux 8',
            'almalinux9_64' => 'AlmaLinux 9',
            'rockylinux8_64' => 'Rocky Linux 8',
            'rockylinux9_64' => 'Rocky Linux 9',
            'fedora38_64' => 'Fedora 38',
            'fedora39_64' => 'Fedora 39',
            'windows2019_64' => 'Windows Server 2019',
            'windows2022_64' => 'Windows Server 2022',
        ];

        return $osNames[$osCode] ?? $osCode;
    }

    /**
     * Check if OS is Windows
     */
    public static function isWindowsOS($osCode)
    {
        return strpos(strtolower($osCode), 'windows') !== false;
    }

    /**
     * Check if OS is Linux
     */
    public static function isLinuxOS($osCode)
    {
        return !self::isWindowsOS($osCode);
    }

    /**
     * Get server status icon
     */
    public static function getStatusIcon($state)
    {
        $icons = [
            'running' => '✓',
            'active' => '✓',
            'stopped' => '✗',
            'halted' => '✗',
            'error' => '⚠',
            'unknown' => '?',
        ];

        return $icons[strtolower($state)] ?? $icons['unknown'];
    }

    /**
     * Get server status color
     */
    public static function getStatusColor($state)
    {
        $colors = [
            'running' => '#28a745',
            'active' => '#28a745',
            'stopped' => '#dc3545',
            'halted' => '#dc3545',
            'error' => '#ffc107',
            'unknown' => '#6c757d',
        ];

        return $colors[strtolower($state)] ?? $colors['unknown'];
    }

    /**
     * Format uptime
     */
    public static function formatUptime($seconds)
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];

        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }
        if ($minutes > 0 || empty($parts)) {
            $parts[] = $minutes . 'm';
        }

        return implode(' ', $parts);
    }

    /**
     * Validate OVH service name format
     */
    public static function validateServiceName($serviceName)
    {
        // Dedicated server format: nsXXXXXX.ip-X-X-X.xx
        if (preg_match('/^ns\d+\.ip-\d+-\d+-\d+\.\w{2}$/', $serviceName)) {
            return true;
        }

        // VPS format: vpsXXXXX.vps.ovh.net or similar
        if (preg_match('/^vps\d+\./', $serviceName)) {
            return true;
        }

        // Cloud project ID format (UUID)
        if (preg_match('/^[a-f0-9]{32}$/', $serviceName)) {
            return true;
        }

        // Generic format
        if (strlen($serviceName) > 3 && strlen($serviceName) < 100) {
            return true;
        }

        return false;
    }

    /**
     * Get task status display
     */
    public static function getTaskStatus($status)
    {
        $statuses = [
            'init' => 'Initializing',
            'todo' => 'Pending',
            'doing' => 'In Progress',
            'done' => 'Completed',
            'cancelled' => 'Cancelled',
            'error' => 'Failed',
        ];

        return $statuses[strtolower($status)] ?? ucfirst($status);
    }

    /**
     * Calculate price from OVH pricing
     */
    public static function calculatePrice($ovhPrice, $markup = 20)
    {
        // Add markup percentage
        return round($ovhPrice * (1 + $markup / 100), 2);
    }

    /**
     * Convert OVH date format to timestamp
     */
    public static function parseOVHDate($dateString)
    {
        try {
            $date = new \DateTime($dateString);
            return $date->getTimestamp();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format date for display
     */
    public static function formatDate($timestamp, $format = 'Y-m-d H:i:s')
    {
        if (empty($timestamp)) {
            return 'N/A';
        }

        return date($format, $timestamp);
    }

    /**
     * Get relative time (e.g., "2 hours ago")
     */
    public static function getRelativeTime($timestamp)
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes != 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
        } else {
            return date('Y-m-d', $timestamp);
        }
    }

    /**
     * Mask sensitive data for logging
     */
    public static function maskSensitiveData($data, $fields = ['password', 'secret', 'key'])
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                foreach ($fields as $field) {
                    if (stripos($key, $field) !== false) {
                        $data[$key] = '***MASKED***';
                    }
                }

                if (is_array($value)) {
                    $data[$key] = self::maskSensitiveData($value, $fields);
                }
            }
        }

        return $data;
    }

    /**
     * Validate OVH API credentials format
     */
    public static function validateCredentials($applicationKey, $applicationSecret, $consumerKey)
    {
        // Basic validation
        if (empty($applicationKey) || empty($applicationSecret) || empty($consumerKey)) {
            return false;
        }

        // Check minimum length
        if (strlen($applicationKey) < 10 || strlen($applicationSecret) < 10 || strlen($consumerKey) < 10) {
            return false;
        }

        return true;
    }

    /**
     * Get OVH manager URL for service
     */
    public static function getServiceManagerURL($endpoint, $serviceType, $serviceName)
    {
        $baseUrls = [
            'ovh-eu' => 'https://www.ovh.com/manager/',
            'ovh-ca' => 'https://ca.ovh.com/manager/',
            'ovh-us' => 'https://us.ovhcloud.com/manager/',
        ];

        $baseUrl = $baseUrls[$endpoint] ?? $baseUrls['ovh-eu'];

        switch ($serviceType) {
            case 'dedicated':
                return $baseUrl . 'dedicated/server/' . $serviceName;
            case 'vps':
                return $baseUrl . 'vps/' . $serviceName;
            case 'cloud':
                return $baseUrl . 'public-cloud/project/' . $serviceName;
            default:
                return $baseUrl;
        }
    }

    /**
     * Check if service supports feature
     */
    public static function supportsFeature($serviceType, $feature)
    {
        $features = [
            'dedicated' => ['reboot', 'reinstall', 'rescue', 'ipmi', 'monitoring', 'backup_ftp'],
            'vps' => ['reboot', 'reinstall', 'rescue', 'snapshot', 'backup', 'console'],
            'cloud' => ['reboot', 'reinstall', 'snapshot', 'console'],
        ];

        return in_array($feature, $features[$serviceType] ?? []);
    }

    /**
     * Get recommended OS for service type
     */
    public static function getRecommendedOS($serviceType)
    {
        $recommendations = [
            'dedicated' => 'ubuntu2204_64',
            'vps' => 'ubuntu2204_64',
            'cloud' => 'ubuntu2204_64',
        ];

        return $recommendations[$serviceType] ?? 'ubuntu2204_64';
    }
}
