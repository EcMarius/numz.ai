<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    /**
     * Show organization setup form
     */
    public function setup()
    {
        $user = auth()->user()->fresh(['organization', 'ownedOrganization']);

        // If user already has an organization, show organization details
        if ($user->organization) {
            $organization = $user->organization;
            return view('theme::pages.organization.setup', compact('organization'));
        }

        // Redirect if user doesn't need organization setup (not on a seated plan)
        if (!$user->needsOrganizationSetup()) {
            return redirect('/dashboard');
        }

        return view('theme::pages.organization.setup');
    }

    /**
     * Store organization
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Check if user already has an organization
        if (!$user || $user->organization_id !== null) {
            return redirect('/dashboard')
                ->with('error', 'You already have an organization set up.');
        }

        // Validate request with better validation rules
        $validated = $request->validate([
            'name' => 'required|string|max:255|min:2',
            'address' => 'nullable|string|max:500',
            'domain' => [
                'required',
                'string',
                'max:255',
                'min:2',
                'regex:/^[a-z0-9.-]+$/i',
                'unique:organizations,domain'
            ],
        ], [
            'domain.regex' => 'Domain can only contain letters, numbers, dots, and hyphens.',
            'domain.unique' => 'This domain is already taken. Please choose another one.',
        ]);

        try {
            // Create organization in a transaction for safety
            \DB::beginTransaction();

            $organization = Organization::create([
                'name' => $validated['name'],
                'address' => $validated['address'] ?? null,
                'domain' => Str::slug($validated['domain']),
                'owner_id' => $user->id,
            ]);

            // Update user to belong to this organization
            $user->organization_id = $organization->id;
            $user->team_role = 'owner';
            $user->save();

            // Update subscription seats_used
            $subscription = \Wave\Subscription::where('billable_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($subscription) {
                $subscription->seats_used = 1; // Owner takes 1 seat
                $subscription->save();
            }

            \DB::commit();

            \Log::info('Organization created successfully', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'organization_name' => $organization->name
            ]);

            // Redirect to team page after successful setup
            return redirect('/team')
                ->with('success', 'Organization setup complete! Welcome to ' . $organization->name);

        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('Failed to create organization', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create organization. Please try again or contact support.');
        }
    }

    /**
     * Show team management page
     */
    public function team()
    {
        $user = auth()->user()->fresh(['organization', 'ownedOrganization']);

        // Redirect to organization setup if needed
        if ($user->needsOrganizationSetup()) {
            return redirect('/organization/setup')
                ->with('info', 'Please complete your organization setup first.');
        }

        // Only organization owners can access this
        if (!$user->isOrganizationOwner()) {
            abort(403, 'Only organization owners can manage teams.');
        }

        $organization = $user->ownedOrganization;
        $teamMembers = $organization->teamMembers()->with('roles')->get();
        $subscription = $user->subscription('default');

        return view('theme::pages.organization.team', compact('organization', 'teamMembers', 'subscription'));
    }

    /**
     * Show add team member form
     */
    public function addMemberForm()
    {
        $user = auth()->user();

        if (!$user->isOrganizationOwner()) {
            abort(403);
        }

        $organization = $user->ownedOrganization;
        $subscription = $user->subscription('default');

        // Check if there are available seats
        if ($organization->available_seats <= 0) {
            return redirect()->route('organization.team')
                ->with('error', 'No available seats. Please purchase more seats first.');
        }

        return view('theme::pages.organization.add-member', compact('organization', 'subscription'));
    }

    /**
     * Store team member
     */
    public function storeMember(Request $request)
    {
        $user = auth()->user();

        if (!$user->isOrganizationOwner()) {
            abort(403);
        }

        $organization = $user->ownedOrganization;

        // Check if there are available seats
        if ($organization->available_seats <= 0) {
            return redirect()->route('organization.team')
                ->with('error', 'No available seats. Please purchase more seats first.');
        }

        // Validate request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create team member user
        $teamMember = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'organization_id' => $organization->id,
            'team_role' => 'member',
        ]);

        // Assign default user role to team member
        // Note: Team members should have the 'user' role, not copied from owner
        $teamMember->assignRole('user');

        // Increment seats_used
        $subscription = $user->subscription('default');
        if ($subscription) {
            $subscription->seats_used = $subscription->seats_used + 1;
            $subscription->save();
        }

        return redirect()->route('organization.team')
            ->with('success', 'Team member added successfully!');
    }

    /**
     * Show team member detail
     */
    public function showMember(User $member)
    {
        $user = auth()->user();

        if (!$user->isOrganizationOwner()) {
            abort(403);
        }

        // Ensure member belongs to this organization
        if ($member->organization_id !== $user->ownedOrganization->id) {
            abort(404);
        }

        // Get member's campaigns and leads
        $campaigns = $member->campaigns()->with('leads')->get();

        return view('theme::pages.organization.member-detail', compact('member', 'campaigns'));
    }

    /**
     * Delete team member
     */
    public function destroyMember(User $member)
    {
        $user = auth()->user();

        if (!$user->isOrganizationOwner()) {
            abort(403);
        }

        // Ensure member belongs to this organization and is not the owner
        if ($member->organization_id !== $user->ownedOrganization->id || $member->id === $user->id) {
            abort(403, 'Cannot delete this user.');
        }

        // Decrement seats_used
        $subscription = $user->subscription('default');
        if ($subscription) {
            $subscription->seats_used = max(1, $subscription->seats_used - 1);
            $subscription->save();
        }

        // Delete user
        $member->delete();

        return redirect()->route('organization.team')
            ->with('success', 'Team member removed successfully.');
    }
}
