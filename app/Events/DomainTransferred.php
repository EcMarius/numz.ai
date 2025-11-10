<?php

namespace App\Events;

use App\Models\Domain;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DomainTransferred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Domain $domain
    ) {}
}
