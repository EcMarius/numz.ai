<?php

namespace App\Events;

use App\Models\HostingService;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public HostingService $service) {}
}
