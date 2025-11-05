<?php

namespace App\Livewire\Leads;

use Livewire\Component;
use App\Models\LeadMessage;
use App\Services\LeadMessagingService;
use Wave\Plugins\EvenLeads\Models\Lead;
use Wave\Plugins\EvenLeads\Services\PlanLimitService;
use Illuminate\Support\Facades\Auth;

class LeadChat extends Component
{
    public $lead;
    public $messages = [];
    public $newMessage = '';
    public $generatingAI = false;
    public $hasAIAccess = false;
    public $aiQuota = null;

    protected $listeners = ['messageReceived' => 'loadMessages'];

    public function mount($leadId)
    {
        $this->lead = Lead::findOrFail($leadId);

        // Verify user owns this lead
        if ($this->lead->user_id !== Auth::id()) {
            abort(403);
        }

        $this->loadMessages();
        $this->checkAIAccess();
    }

    protected function checkAIAccess()
    {
        $service = app(LeadMessagingService::class);
        $this->hasAIAccess = $service->checkAIChatAccess(Auth::user());

        if ($this->hasAIAccess) {
            $limitService = app(PlanLimitService::class);
            $this->aiQuota = $limitService->getRemainingAIReplies(Auth::user());
        }
    }

    public function loadMessages()
    {
        $service = app(LeadMessagingService::class);
        $this->messages = $service->getMessageHistory($this->lead);
    }

    public function sendMessage()
    {
        $this->validate([
            'newMessage' => 'required|string|max:5000',
        ]);

        try {
            $service = app(LeadMessagingService::class);
            $message = $service->sendMessageToLead($this->lead, $this->newMessage, Auth::id());

            if ($message) {
                $this->newMessage = '';
                $this->loadMessages();

                session()->flash('success', 'Message sent successfully!');
            } else {
                session()->flash('error', 'Failed to send message. Please try again.');
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function generateAIMessage()
    {
        if (!$this->hasAIAccess) {
            session()->flash('error', 'AI Chat is not available on your plan.');
            return;
        }

        $this->generatingAI = true;

        try {
            $service = app(LeadMessagingService::class);
            $result = $service->generateAIMessage($this->lead, $this->newMessage);

            if ($result['success']) {
                $this->newMessage = $result['message'];
                $this->checkAIAccess(); // Refresh quota
            } else {
                session()->flash('error', $result['error']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'AI generation failed: ' . $e->getMessage());
        } finally {
            $this->generatingAI = false;
        }
    }

    public function saveDraft()
    {
        if (empty($this->newMessage)) {
            return;
        }

        try {
            $service = app(LeadMessagingService::class);
            $service->saveDraft($this->lead, $this->newMessage, Auth::id());

            session()->flash('success', 'Draft saved');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save draft');
        }
    }

    public function render()
    {
        return view('livewire.leads.lead-chat');
    }
}
