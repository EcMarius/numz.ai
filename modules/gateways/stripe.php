<?php
/**
 * Stripe Payment Gateway for NUMZ.AI
 * WHMCS Compatible
 */

function stripe_MetaData()
{
    return [
        'DisplayName' => 'Stripe',
        'APIVersion' => '1.1',
    ];
}

function stripe_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Stripe Payment Gateway',
        ],
        'apiKey' => [
            'FriendlyName' => 'Secret Key',
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'Your Stripe Secret Key',
        ],
        'publishableKey' => [
            'FriendlyName' => 'Publishable Key',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Your Stripe Publishable Key',
        ],
    ];
}

function stripe_capture($params)
{
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    \Stripe\Stripe::setApiKey($params['apiKey']);

    try {
        $charge = \Stripe\Charge::create([
            'amount' => $params['amount'] * 100, // Convert to cents
            'currency' => $params['currency'],
            'source' => $params['token'],
            'description' => "Invoice #{$params['invoiceid']}",
            'metadata' => [
                'invoice_id' => $params['invoiceid'],
                'client_id' => $params['clientdetails']['id'],
            ],
        ]);

        return [
            'status' => 'success',
            'rawdata' => $charge,
            'transid' => $charge->id,
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'rawdata' => $e->getMessage(),
        ];
    }
}
