<?php

namespace App\Numz\Contracts;

interface PaymentGatewayInterface
{
    public function charge(array $params): array;
    public function refund(string $transactionId, float $amount): array;
    public function getConfig(): array;
    public function validateWebhook(array $payload): bool;
}
