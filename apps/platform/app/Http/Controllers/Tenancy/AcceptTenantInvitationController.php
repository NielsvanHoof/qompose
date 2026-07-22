<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Actions\Tenancy\AcceptTenantInvitationAction;
use App\Actions\Tenancy\RegisterAndAcceptTenantInvitationAction;
use App\Actions\Tenancy\ResolveTenantInvitationFromTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenancy\RegisterFromInvitationRequest;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class AcceptTenantInvitationController extends Controller
{
    public function show(
        string $token,
        Request $request,
        ResolveTenantInvitationFromTokenAction $resolveInvitation,
    ): Response {
        $invitation = $this->resolvePendingInvitation($token, $resolveInvitation);
        $invitation->loadMissing('tenant');

        $tenant = $invitation->tenant;

        if (! $tenant instanceof Tenant) {
            abort(404);
        }

        $user = $request->user();
        $emailMatches = $user instanceof User
            && mb_strtolower($user->email) === mb_strtolower($invitation->email);

        return Inertia::render('invitations/show', [
            'token' => $token,
            'firm' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'invitation' => [
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'role_label' => $invitation->role->label(),
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ],
            'auth' => [
                'authenticated' => $user instanceof User,
                'email_matches' => $emailMatches,
                'user_email' => $user instanceof User ? $user->email : null,
            ],
            // Invite-only signup stays available even when Fortify registration is off.
            'can_register' => true,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function accept(
        string $token,
        Request $request,
        ResolveTenantInvitationFromTokenAction $resolveInvitation,
        AcceptTenantInvitationAction $acceptInvitation,
    ): RedirectResponse {
        $invitation = $this->resolvePendingInvitation($token, $resolveInvitation);

        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->guest(route('invitations.show', ['token' => $token]));
        }

        $membership = $acceptInvitation->handle($invitation, $user);
        $tenant = $membership->tenant;

        if (! $tenant instanceof Tenant) {
            $membership->loadMissing('tenant');
            $tenant = $membership->tenant;
        }

        if (! $tenant instanceof Tenant) {
            abort(404);
        }

        $request->session()->put('active_tenant_id', $tenant->id);
        $request->session()->flash('inertia.refresh.workspaces', true);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Welcome to :firm.', ['firm' => $tenant->name]),
        ]);

        return to_route('workspaces.dashboard', ['tenant' => $tenant]);
    }

    public function register(
        string $token,
        RegisterFromInvitationRequest $request,
        ResolveTenantInvitationFromTokenAction $resolveInvitation,
        RegisterAndAcceptTenantInvitationAction $registerAndAccept,
    ): RedirectResponse {
        $invitation = $this->resolvePendingInvitation($token, $resolveInvitation);

        // Force the invite email so registration cannot use a different address.
        $input = $request->registrationInput();
        $input['email'] = $invitation->email;

        $membership = $registerAndAccept->handle($invitation, $input);
        $tenant = $membership->tenant;

        if (! $tenant instanceof Tenant) {
            $membership->loadMissing('tenant');
            $tenant = $membership->tenant;
        }

        if (! $tenant instanceof Tenant) {
            abort(404);
        }

        $request->session()->put('active_tenant_id', $tenant->id);
        $request->session()->flash('inertia.refresh.workspaces', true);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Welcome to :firm.', ['firm' => $tenant->name]),
        ]);

        return to_route('workspaces.dashboard', ['tenant' => $tenant]);
    }

    private function resolvePendingInvitation(
        string $token,
        ResolveTenantInvitationFromTokenAction $resolveInvitation,
    ): TenantInvitation {
        $invitation = $resolveInvitation->handle($token);

        if (! $invitation instanceof TenantInvitation || ! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation is no longer valid.'),
            ]);
        }

        return $invitation;
    }
}
