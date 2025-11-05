<?php
/**
 * Tawk.to Live Chat Integration for NUMZ.AI
 * WHMCS Compatible Addon Module
 */

function tawkto_config()
{
    return [
        'name' => 'Tawk.to Live Chat',
        'description' => 'Integrate Tawk.to live chat into your NUMZ.AI installation',
        'version' => '1.0',
        'author' => 'NUMZ.AI',
        'fields' => [
            'propertyId' => [
                'FriendlyName' => 'Property ID',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Your Tawk.to Property ID',
            ],
            'widgetId' => [
                'FriendlyName' => 'Widget ID',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Your Tawk.to Widget ID',
            ],
            'adminOnly' => [
                'FriendlyName' => 'Admin Area Only',
                'Type' => 'yesno',
                'Description' => 'Show only in admin area',
            ],
            'clientOnly' => [
                'FriendlyName' => 'Client Area Only',
                'Type' => 'yesno',
                'Description' => 'Show only in client area',
            ],
        ],
    ];
}

function tawkto_activate()
{
    return [
        'status' => 'success',
        'description' => 'Tawk.to module activated successfully',
    ];
}

function tawkto_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'Tawk.to module deactivated successfully',
    ];
}

function tawkto_output($vars)
{
    echo '<h2>Tawk.to Live Chat Configuration</h2>';
    echo '<p>Configure your Tawk.to settings using the fields in the addon settings page.</p>';
    echo '<p>Property ID: ' . htmlspecialchars($vars['propertyId']) . '</p>';
    echo '<p>Widget ID: ' . htmlspecialchars($vars['widgetId']) . '</p>';
    
    echo '<h3>Integration Code</h3>';
    echo '<p>The following code will be automatically injected into your pages:</p>';
    echo '<pre><code>';
    echo htmlspecialchars(tawkto_getScript($vars));
    echo '</code></pre>';
}

function tawkto_clientarea($vars)
{
    if ($vars['adminOnly']) {
        return '';
    }

    return tawkto_getScript($vars);
}

function tawkto_adminarea($vars)
{
    if ($vars['clientOnly']) {
        return '';
    }

    return tawkto_getScript($vars);
}

function tawkto_getScript($vars)
{
    if (empty($vars['propertyId']) || empty($vars['widgetId'])) {
        return '';
    }

    return <<<SCRIPT
<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/{$vars['propertyId']}/{$vars['widgetId']}';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->
SCRIPT;
}
