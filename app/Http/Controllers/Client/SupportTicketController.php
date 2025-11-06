<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\SupportTicketAttachment;
use App\Models\HostingService;
use App\Models\DomainRegistration;
use App\Models\Invoice;
use App\Mail\TicketCreated;
use App\Mail\TicketReply as TicketReplyMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * List all tickets for current user
     */
    public function index(Request $request)
    {
        $query = SupportTicket::where('user_id', auth()->id())
            ->with(['replies' => function($q) {
                $q->latest()->limit(1);
            }]);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'open') {
                $query->whereIn('status', ['open', 'in_progress', 'waiting_customer', 'waiting_staff']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by department
        if ($request->has('department') && $request->department !== 'all') {
            $query->where('department', $request->department);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('ticket_number', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('last_reply_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total' => SupportTicket::where('user_id', auth()->id())->count(),
            'open' => SupportTicket::where('user_id', auth()->id())
                ->whereIn('status', ['open', 'in_progress', 'waiting_customer', 'waiting_staff'])
                ->count(),
            'closed' => SupportTicket::where('user_id', auth()->id())
                ->where('status', 'closed')
                ->count(),
        ];

        return view('client.tickets.index', compact('tickets', 'stats'));
    }

    /**
     * Show ticket creation form
     */
    public function create()
    {
        // Get user's services and domains for linking
        $services = HostingService::where('user_id', auth()->id())
            ->with('product')
            ->get();

        $domains = DomainRegistration::where('user_id', auth()->id())->get();

        $invoices = Invoice::where('user_id', auth()->id())
            ->where('status', 'unpaid')
            ->get();

        $departments = [
            'general' => 'General Support',
            'technical' => 'Technical Support',
            'billing' => 'Billing',
            'sales' => 'Sales',
        ];

        return view('client.tickets.create', compact('services', 'domains', 'invoices', 'departments'));
    }

    /**
     * Store new ticket
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'department' => 'required|in:general,technical,billing,sales',
            'priority' => 'required|in:low,normal,high,urgent',
            'message' => 'required|string|min:10',
            'related_service_id' => 'nullable|exists:hosting_services,id',
            'related_domain_id' => 'nullable|exists:domain_registrations,id',
            'related_invoice_id' => 'nullable|exists:invoices,id',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip',
        ]);

        // Create ticket
        $ticket = SupportTicket::create([
            'user_id' => auth()->id(),
            'ticket_number' => SupportTicket::generateTicketNumber(),
            'subject' => $request->subject,
            'department' => $request->department,
            'priority' => $request->priority,
            'status' => 'open',
            'related_service_id' => $request->related_service_id,
            'related_domain_id' => $request->related_domain_id,
            'related_invoice_id' => $request->related_invoice_id,
            'last_reply_at' => now(),
        ]);

        // Create first reply (initial message)
        $reply = SupportTicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
            'is_staff_reply' => false,
            'is_internal_note' => false,
        ]);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('support/attachments', $filename, 'private');

                SupportTicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'reply_id' => $reply->id,
                    'filename' => $filename,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'storage_path' => $path,
                ]);
            }
        }

        // Send email notification to admins
        try {
            // TODO: Get admin emails from settings
            $adminEmails = ['admin@numz.ai'];
            foreach ($adminEmails as $email) {
                Mail::to($email)->send(new TicketCreated($ticket));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send ticket created email: ' . $e->getMessage());
        }

        return redirect()->route('client.tickets.show', $ticket->id)
            ->with('success', 'Ticket #' . $ticket->ticket_number . ' created successfully');
    }

    /**
     * Show single ticket
     */
    public function show($id)
    {
        $ticket = SupportTicket::where('user_id', auth()->id())
            ->with([
                'replies' => function($q) {
                    $q->where('is_internal_note', false) // Hide internal notes from customers
                        ->with(['user', 'attachmentFiles'])
                        ->orderBy('created_at', 'asc');
                },
                'relatedService.product',
                'relatedDomain',
                'relatedInvoice',
                'assignedTo'
            ])
            ->findOrFail($id);

        return view('client.tickets.show', compact('ticket'));
    }

    /**
     * Reply to ticket
     */
    public function reply(Request $request, $id)
    {
        $ticket = SupportTicket::where('user_id', auth()->id())
            ->findOrFail($id);

        // Check if ticket is closed
        if ($ticket->isClosed()) {
            return back()->with('error', 'Cannot reply to a closed ticket. Please reopen it first.');
        }

        $request->validate([
            'message' => 'required|string|min:10',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip',
        ]);

        // Create reply
        $reply = SupportTicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
            'is_staff_reply' => false,
            'is_internal_note' => false,
        ]);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('support/attachments', $filename, 'private');

                SupportTicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'reply_id' => $reply->id,
                    'filename' => $filename,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'storage_path' => $path,
                ]);
            }
        }

        // Send email notification to assigned staff or all admins
        try {
            if ($ticket->assignedTo) {
                Mail::to($ticket->assignedTo->email)->send(new TicketReplyMail($ticket, $reply));
            } else {
                // TODO: Send to all admin emails
                $adminEmails = ['admin@numz.ai'];
                foreach ($adminEmails as $email) {
                    Mail::to($email)->send(new TicketReplyMail($ticket, $reply));
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send ticket reply email: ' . $e->getMessage());
        }

        return redirect()->route('client.tickets.show', $ticket->id)
            ->with('success', 'Reply added successfully');
    }

    /**
     * Close ticket
     */
    public function close($id)
    {
        $ticket = SupportTicket::where('user_id', auth()->id())
            ->findOrFail($id);

        $ticket->close();

        return redirect()->route('client.tickets.show', $ticket->id)
            ->with('success', 'Ticket closed successfully');
    }

    /**
     * Reopen ticket
     */
    public function reopen($id)
    {
        $ticket = SupportTicket::where('user_id', auth()->id())
            ->findOrFail($id);

        $ticket->reopen();

        return redirect()->route('client.tickets.show', $ticket->id)
            ->with('success', 'Ticket reopened successfully');
    }

    /**
     * Download attachment
     */
    public function downloadAttachment($ticketId, $attachmentId)
    {
        $ticket = SupportTicket::where('user_id', auth()->id())
            ->findOrFail($ticketId);

        $attachment = SupportTicketAttachment::where('ticket_id', $ticket->id)
            ->findOrFail($attachmentId);

        return Storage::download($attachment->storage_path, $attachment->original_filename);
    }
}
