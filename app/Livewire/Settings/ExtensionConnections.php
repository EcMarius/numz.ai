<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class ExtensionConnections extends Component
{
    public $extensionTokens = [];

    public function mount()
    {
        $this->loadExtensionTokens();
    }

    protected function loadExtensionTokens()
    {
        $user = Auth::user();

        // Get all personal access tokens that are extension-related
        // These could be named 'browser-extension', 'extension-token', or custom client_id
        $tokens = PersonalAccessToken::where('tokenable_type', get_class($user))
            ->where('tokenable_id', $user->id)
            ->where(function ($query) {
                $query->where('name', 'like', '%extension%')
                      ->orWhere('name', 'browser-extension')
                      ->orWhere('name', 'extension-token');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $this->extensionTokens = $tokens->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'created_at' => $token->created_at->format('M d, Y'),
                'created_at_human' => $token->created_at->diffForHumans(),
                'last_used_at' => $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never',
                'abilities' => $token->abilities ?? [],
            ];
        })->toArray();
    }

    public function revokeAccess($tokenId)
    {
        $user = Auth::user();

        $token = PersonalAccessToken::where('id', $tokenId)
            ->where('tokenable_type', get_class($user))
            ->where('tokenable_id', $user->id)
            ->first();

        if (!$token) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Extension connection not found.'
            ]);
            return;
        }

        $token->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Extension access has been revoked successfully.'
        ]);

        $this->loadExtensionTokens();
    }

    public function revokeAll()
    {
        $user = Auth::user();

        $deletedCount = PersonalAccessToken::where('tokenable_type', get_class($user))
            ->where('tokenable_id', $user->id)
            ->where(function ($query) {
                $query->where('name', 'like', '%extension%')
                      ->orWhere('name', 'browser-extension')
                      ->orWhere('name', 'extension-token');
            })
            ->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "All extension access tokens have been revoked ({$deletedCount} tokens)."
        ]);

        $this->loadExtensionTokens();
    }

    public function render()
    {
        return view('livewire.settings.extension-connections');
    }
}
