<?php

namespace Wave\Plugins\Contracts;

interface CampaignFieldProvider
{
    /**
     * Get campaign-specific fields that should be added to campaign create/edit forms.
     *
     * @param string $platformName The platform identifier (e.g., 'linkedin', 'facebook')
     * @param array $platformConfig Platform configuration from database (including plugin_config)
     * @return array Array of field definitions
     *
     * Example return format:
     * [
     *     [
     *         'name' => 'groups',
     *         'label' => 'Target Groups',
     *         'type' => 'textarea', // text, number, checkbox, select, textarea
     *         'placeholder' => 'https://www.facebook.com/groups/example (one per line)',
     *         'help' => 'Enter group URLs to target. Leave empty to scan the entire platform.',
     *         'required' => false,
     *         'default' => null,
     *         'rules' => 'nullable|string', // Laravel validation rules
     *         'column' => 'facebook_groups', // Campaign table column to map to
     *         'conditional' => true, // Whether this field is shown conditionally
     *         'condition_key' => 'allow_group_selection', // Platform config key to check
     *         'options' => [], // For select fields
     *     ]
     * ]
     */
    public function getCampaignFields(string $platformName, array $platformConfig): array;

    /**
     * Get the plugin identifier.
     *
     * @return string
     */
    public function getPluginIdentifier(): string;
}
