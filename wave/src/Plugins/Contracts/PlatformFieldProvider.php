<?php

namespace Wave\Plugins\Contracts;

interface PlatformFieldProvider
{
    /**
     * Get platform-specific configuration fields for the admin panel.
     *
     * @param string $platformName The platform identifier (e.g., 'linkedin', 'facebook')
     * @return array Array of field definitions
     *
     * Example return format:
     * [
     *     [
     *         'name' => 'apify_actor_id',
     *         'label' => 'Apify Actor ID',
     *         'type' => 'text', // text, number, checkbox, select, textarea
     *         'placeholder' => 'Enter Apify actor ID',
     *         'help' => 'The Apify actor ID for scraping',
     *         'required' => false,
     *         'default' => null,
     *         'rules' => 'nullable|string', // Laravel validation rules
     *         'options' => [], // For select fields
     *         'group' => 'Apify Configuration', // Optional grouping
     *     ]
     * ]
     */
    public function getPlatformFields(string $platformName): array;

    /**
     * Get the plugin identifier.
     *
     * @return string
     */
    public function getPluginIdentifier(): string;
}
