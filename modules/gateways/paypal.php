<?php
/**
 * PayPal Payment Gateway for NUMZ.AI
 * WHMCS Compatible
 */

function paypal_MetaData()
{
    return [
        'DisplayName' => 'PayPal',
        'APIVersion' => '1.1',
    ];
}

function paypal_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'PayPal Payments Standard',
        ],
        'email' => [
            'FriendlyName' => 'PayPal Email',
            'Type' => 'text',
            'Size' => '50',
        ],
        'sandbox' => [
            'FriendlyName' => 'Sandbox Mode',
            'Type' => 'yesno',
        ],
    ];
}

function paypal_link($params)
{
    $url = $params['sandbox'] ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' 
                               : 'https://www.paypal.com/cgi-bin/webscr';

    $fields = [
        'cmd' => '_xclick',
        'business' => $params['email'],
        'item_name' => $params['companyname'] . " - Invoice #" . $params['invoiceid'],
        'amount' => $params['amount'],
        'currency_code' => $params['currency'],
        'return' => $params['returnurl'],
        'cancel_return' => $params['returnurl'],
        'notify_url' => $params['systemurl'] . '/modules/gateways/callback/paypal.php',
        'invoice' => $params['invoiceid'],
        'custom' => $params['invoiceid'],
    ];

    $form = '<form method="post" action="' . $url . '">';
    foreach ($fields as $key => $value) {
        $form .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '" />';
    }
    $form .= '<input type="submit" value="Pay with PayPal" class="btn btn-primary" />';
    $form .= '</form>';

    return $form;
}
