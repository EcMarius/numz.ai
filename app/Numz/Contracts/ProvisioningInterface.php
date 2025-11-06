<?php

namespace App\Numz\Contracts;

interface ProvisioningInterface
{
    public function createAccount(array $params): array;
    public function suspendAccount(array $params): array;
    public function unsuspendAccount(array $params): array;
    public function terminateAccount(array $params): array;
    public function changePassword(array $params): array;
}
