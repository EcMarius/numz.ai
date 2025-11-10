<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\TicketReply as TicketReplyModel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketReply
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public TicketReplyModel $reply
    ) {}
}
