<?php

namespace App\Numz\Modules\Integrations;

class TawkToIntegration
{
    protected $propertyId;
    protected $widgetId;

    public function __construct()
    {
        $this->propertyId = config('numz.integrations.tawkto.property_id');
        $this->widgetId = config('numz.integrations.tawkto.widget_id');
    }

    public function getWidgetScript(?array $userData = null): string
    {
        if (!$this->propertyId || !$this->widgetId) {
            return '';
        }

        $userDataScript = '';
        if ($userData) {
            $name = $userData['name'] ?? '';
            $email = $userData['email'] ?? '';
            $userDataScript = <<<JS
Tawk_API.visitor = {
    name: '{$name}',
    email: '{$email}'
};
JS;
        }

        return <<<HTML
<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
{$userDataScript}
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/{$this->propertyId}/{$this->widgetId}';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->
HTML;
    }

    public function isConfigured(): bool
    {
        return !empty($this->propertyId) && !empty($this->widgetId);
    }
}
