<?php

namespace App\WHMCS;

use App\Models\Server;
use App\Models\Service;
use Illuminate\Support\Facades\File;

class ModuleCompat
{
    /**
     * Load and execute a WHMCS server module
     */
    public static function callServerModule($moduleName, $function, $params)
    {
        $modulePath = base_path("modules/servers/{$moduleName}/{$moduleName}.php");
        
        if (!File::exists($modulePath)) {
            // Try NUMZ.AI native modules
            $modulePath = app_path("Modules/Servers/{$moduleName}.php");
        }

        if (File::exists($modulePath)) {
            require_once $modulePath;
            
            $functionName = "{$moduleName}_{$function}";
            if (function_exists($functionName)) {
                return $functionName($params);
            }
        }

        throw new \Exception("Module function not found: {$moduleName}_{$function}");
    }

    /**
     * Get module configuration options
     */
    public static function getModuleConfig($moduleName, $type = 'server')
    {
        $modulePath = base_path("modules/{$type}s/{$moduleName}/{$moduleName}.php");
        
        if (File::exists($modulePath)) {
            require_once $modulePath;
            
            $configFunction = "{$moduleName}_ConfigOptions";
            if (function_exists($configFunction)) {
                return $configFunction();
            }
        }

        return [];
    }

    /**
     * Get registrar configuration
     */
    public static function getRegistrarConfig($registrar)
    {
        return self::getModuleConfig($registrar, 'registrar');
    }

    /**
     * Get payment gateway configuration
     */
    public static function getGatewayConfig($gateway)
    {
        return self::getModuleConfig($gateway, 'gateway');
    }

    /**
     * Provision a service
     */
    public static function provisionService(Service $service)
    {
        $product = $service->product;
        $server = $service->server;

        if (!$product->module_name) {
            return ['success' => false, 'message' => 'No module configured'];
        }

        $params = self::buildModuleParams($service);

        try {
            $result = self::callServerModule($product->module_name, 'CreateAccount', $params);
            
            if ($result === 'success' || (is_array($result) && ($result['success'] ?? false))) {
                $service->update(['status' => 'active']);
                return ['success' => true, 'message' => 'Account created successfully'];
            }

            return ['success' => false, 'message' => $result['message'] ?? 'Failed to create account'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Build module parameters (WHMCS format)
     */
    protected static function buildModuleParams(Service $service)
    {
        $product = $service->product;
        $server = $service->server;
        $client = $service->client;

        return [
            'serviceid' => $service->id,
            'pid' => $product->id,
            'domain' => $service->domain,
            'username' => $service->username,
            'password' => $service->password,
            'clientsdetails' => [
                'firstname' => $client->firstname,
                'lastname' => $client->lastname,
                'email' => $client->email,
                'country' => $client->country,
            ],
            'customfields' => $service->configoptions ?? [],
            'configoptions' => $service->configoptions ?? [],
            'server' => $server ? $server->hostname : '',
            'serverip' => $server ? $server->ip_address : '',
            'serverusername' => $server ? decrypt($server->username) : '',
            'serverpassword' => $server ? decrypt($server->password) : '',
            'serveraccesshash' => $server ? decrypt($server->access_hash) : '',
            'serversecure' => $server ? $server->secure : true,
            'serverport' => $server ? $server->port : 2087,
        ];
    }
}
