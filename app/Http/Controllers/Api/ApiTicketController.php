<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\Request;

class ApiTicketController extends Controller
{
    /**
     * Get all tickets
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $userId = $request->input('user_id');
        $status = $request->input('status');
        $department = $request->input('department');

        $query = SupportTicket::with(['user', 'assignedTo'])
            ->withCount('replies');

        // Filter by user
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        // Filter by department
        if ($department) {
            $query->where('department', $department);
        }

        // Order by latest
        $query->orderBy('created_at', 'desc');

        $tickets = $query->paginate($perPage);

        return TicketResource::collection($tickets);
    }

    /**
     * Get single ticket
     */
    public function show($id)
    {
        $ticket = SupportTicket::with(['user', 'assignedTo', 'replies.user'])
            ->withCount('replies')
            ->findOrFail($id);

        return new TicketResource($ticket);
    }

    /**
     * Create new ticket
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'department' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user_id,
            'ticket_number' => SupportTicket::generateTicketNumber(),
            'subject' => $request->subject,
            'department' => $request->department ?? 'general',
            'priority' => $request->priority ?? 'normal',
            'status' => 'open',
        ]);

        // Create first reply (the message)
        $ticket->replies()->create([
            'user_id' => $request->user_id,
            'message' => $request->message,
            'is_staff_reply' => false,
        ]);

        $ticket->update(['last_reply_at' => now()]);

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('ticket.created', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'user_id' => $ticket->user_id,
            'subject' => $ticket->subject,
            'department' => $ticket->department,
            'priority' => $ticket->priority,
        ], $ticket->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Ticket created successfully',
            'data' => new TicketResource($ticket->load(['user', 'replies'])),
        ], 201);
    }

    /**
     * Reply to ticket
     */
    public function reply(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $request->validate([
            'message' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'is_staff_reply' => 'boolean',
        ]);

        $reply = $ticket->replies()->create([
            'user_id' => $request->user_id,
            'message' => $request->message,
            'is_staff_reply' => $request->is_staff_reply ?? false,
        ]);

        $ticket->update(['last_reply_at' => now()]);

        // Update status if it was closed
        if ($ticket->status === 'closed') {
            $ticket->reopen();
        }

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('ticket.replied', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'user_id' => $ticket->user_id,
            'reply_user_id' => $request->user_id,
            'is_staff_reply' => $request->is_staff_reply ?? false,
        ], $ticket->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Reply added successfully',
            'data' => new TicketResource($ticket->load(['user', 'replies'])),
        ]);
    }

    /**
     * Close ticket
     */
    public function close($id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $ticket->close();

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('ticket.closed', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'user_id' => $ticket->user_id,
        ], $ticket->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Ticket closed successfully',
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * Reopen ticket
     */
    public function reopen($id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $ticket->reopen();

        return response()->json([
            'success' => true,
            'message' => 'Ticket reopened successfully',
            'data' => new TicketResource($ticket),
        ]);
    }
}
