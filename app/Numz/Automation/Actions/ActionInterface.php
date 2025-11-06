<?php

namespace App\Numz\Automation\Actions;

interface ActionInterface
{
    /**
     * Execute the action
     *
     * @param array $params Action parameters
     * @param array $data Trigger data
     * @return array Result with success status and message
     */
    public function execute(array $params, array $data): array;

    /**
     * Get action name
     */
    public function getName(): string;

    /**
     * Get action description
     */
    public function getDescription(): string;

    /**
     * Get required parameters
     */
    public function getRequiredParams(): array;
}
