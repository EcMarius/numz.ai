<?php

namespace App\Numz\Contracts;

interface RegistrarInterface
{
    public function registerDomain(array $params): array;
    public function transferDomain(array $params): array;
    public function renewDomain(string $domain, int $years): array;
    public function getNameservers(string $domain): array;
    public function setNameservers(string $domain, array $nameservers): array;
}
