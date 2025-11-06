<?php

namespace Database\Seeders;

use App\Models\DomainRegistrar;
use Illuminate\Database\Seeder;

class DomainRegistrarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $registrars = [
            [
                'name' => 'Namecheap',
                'slug' => 'namecheap',
                'description' => 'One of the largest domain registrars with competitive pricing and excellent customer support.',
                'is_enabled' => false,
                'is_available' => true,
                'supported_tlds' => ['.com', '.net', '.org', '.info', '.biz', '.co', '.io', '.app', '.dev', '.tech', '.store', '.online', '.site', '.website', '.space', '.cloud'],
                'capabilities' => ['registration', 'transfer', 'renewal', 'whois_privacy', 'dns', 'nameservers', 'domain_lock'],
                'test_mode' => false,
            ],
            [
                'name' => 'GoDaddy',
                'slug' => 'godaddy',
                'description' => 'World\'s largest domain registrar with extensive TLD support.',
                'is_enabled' => false,
                'is_available' => true,
                'supported_tlds' => ['.com', '.net', '.org', '.info', '.biz', '.co', '.io', '.me', '.app', '.dev', '.store', '.online'],
                'capabilities' => ['registration', 'transfer', 'renewal', 'whois_privacy', 'dns', 'nameservers', 'domain_lock'],
                'test_mode' => false,
            ],
            [
                'name' => 'Enom',
                'slug' => 'enom',
                'description' => 'Wholesale domain registrar popular with resellers.',
                'is_enabled' => false,
                'is_available' => true,
                'supported_tlds' => ['.com', '.net', '.org', '.info', '.biz', '.us', '.co', '.io'],
                'capabilities' => ['registration', 'transfer', 'renewal', 'whois_privacy', 'dns', 'nameservers'],
                'test_mode' => false,
            ],
            [
                'name' => 'ResellerClub',
                'slug' => 'resellerclub',
                'description' => 'Leading domain and hosting reseller platform.',
                'is_enabled' => false,
                'is_available' => true,
                'supported_tlds' => ['.com', '.net', '.org', '.info', '.biz', '.co', '.io', '.in', '.co.uk'],
                'capabilities' => ['registration', 'transfer', 'renewal', 'whois_privacy', 'dns', 'nameservers', 'domain_lock'],
                'test_mode' => false,
            ],
            [
                'name' => 'LogicBoxes',
                'slug' => 'logicboxes',
                'description' => 'Domain registration and management platform for resellers.',
                'is_enabled' => false,
                'is_available' => true,
                'supported_tlds' => ['.com', '.net', '.org', '.info', '.biz', '.co', '.io'],
                'capabilities' => ['registration', 'transfer', 'renewal', 'whois_privacy', 'dns'],
                'test_mode' => false,
            ],
            [
                'name' => 'Name.com',
                'slug' => 'name',
                'description' => 'Simple domain registration with clean interface.',
                'is_enabled' => false,
                'is_available' => true,
                'supported_tlds' => ['.com', '.net', '.org', '.info', '.biz', '.co', '.io', '.me'],
                'capabilities' => ['registration', 'transfer', 'renewal', 'whois_privacy', 'dns', 'nameservers'],
                'test_mode' => false,
            ],
        ];

        foreach ($registrars as $registrar) {
            DomainRegistrar::updateOrCreate(
                ['slug' => $registrar['slug']],
                $registrar
            );
        }
    }
}
