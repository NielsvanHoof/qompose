<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Enums\Role;
use App\Enums\TenantMembershipStatus;
use App\Models\Activity;
use App\Models\TenantInvitation;
use App\Models\TenantMembership;
use App\Models\User;
use App\Notifications\Tenancy\WorkspaceMemberInviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('owners can view the members page', function () {
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();

    $this->actingAs($owner)
        ->get(workspaceRoute('workspaces.members.index', $tenant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/members/index')
            ->has('members', 1)
            ->has('invitations', 0)
            ->has('role_options'));
});

test('read-only members cannot manage workspace members', function () {
    ['tenant' => $tenant] = provisionWorkspace();
    $reader = workspaceMember($tenant, Role::ReadOnly);

    $this->actingAs($reader)
        ->get(workspaceRoute('workspaces.members.index', $tenant))
        ->assertForbidden();

    $this->actingAs($reader)
        ->post(workspaceRoute('workspaces.members.store', $tenant), [
            'email' => 'colleague@example.com',
            'role' => Role::Adviser->value,
        ])
        ->assertForbidden();
});

test('owners can invite a member and send a notification', function () {
    Notification::fake();

    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();

    $this->actingAs($owner)
        ->post(workspaceRoute('workspaces.members.store', $tenant), [
            'email' => 'colleague@example.com',
            'role' => Role::Adviser->value,
        ])
        ->assertRedirect(workspaceRoute('workspaces.members.index', $tenant));

    $invitation = TenantInvitation::query()
        ->where('email', 'colleague@example.com')
        ->firstOrFail();

    expect($invitation->role)->toBe(Role::Adviser)
        ->and($invitation->isPending())->toBeTrue();

    Notification::assertSentOnDemand(WorkspaceMemberInviteNotification::class);

    expect(Activity::query()
        ->where('event', AuditEvent::MemberInvited->value)
        ->exists())->toBeTrue();
});

test('administrators can invite but cannot assign the owner role', function () {
    ['tenant' => $tenant] = provisionWorkspace();
    $admin = workspaceMember($tenant, Role::Administrator);

    $this->actingAs($admin)
        ->post(workspaceRoute('workspaces.members.store', $tenant), [
            'email' => 'boss@example.com',
            'role' => Role::Owner->value,
        ])
        ->assertSessionHasErrors('role');
});

test('an existing user can accept an invitation', function () {
    Notification::fake();

    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();
    $invitee = User::factory()->create(['email' => 'colleague@example.com']);

    $response = $this->actingAs($owner)
        ->post(workspaceRoute('workspaces.members.store', $tenant), [
            'email' => $invitee->email,
            'role' => Role::Reviewer->value,
        ]);

    $response->assertRedirect();

    $invitation = TenantInvitation::query()
        ->where('email', $invitee->email)
        ->firstOrFail();

    // Recover the plain token from the notification accept URL.
    Notification::assertSentOnDemand(
        WorkspaceMemberInviteNotification::class,
        function (WorkspaceMemberInviteNotification $notification) use (&$token): bool {
            $token = basename(parse_url($notification->acceptUrl, PHP_URL_PATH) ?: '');

            return $token !== '';
        },
    );

    expect($token ?? null)->not->toBeEmpty();

    $this->actingAs($invitee)
        ->post(route('invitations.accept', ['token' => $token]))
        ->assertRedirect(workspaceRoute('workspaces.dashboard', $tenant));

    $membership = TenantMembership::query()
        ->where('tenant_id', $tenant->id)
        ->where('user_id', $invitee->id)
        ->firstOrFail();

    expect($membership->status)->toBe(TenantMembershipStatus::Active);

    actingInWorkspace($tenant);
    expect($invitee->fresh()->hasRole(Role::Reviewer->value))->toBeTrue();

    expect(Activity::query()
        ->where('event', AuditEvent::MemberInvitationAccepted->value)
        ->exists())->toBeTrue();
});

test('a guest can register from an invitation', function () {
    Notification::fake();

    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();

    $this->actingAs($owner)
        ->post(workspaceRoute('workspaces.members.store', $tenant), [
            'email' => 'newhire@example.com',
            'role' => Role::Adviser->value,
        ])
        ->assertRedirect();

    $token = null;

    Notification::assertSentOnDemand(
        WorkspaceMemberInviteNotification::class,
        function (WorkspaceMemberInviteNotification $notification) use (&$token): bool {
            $token = basename(parse_url($notification->acceptUrl, PHP_URL_PATH) ?: '');

            return true;
        },
    );

    auth()->logout();

    $this->post(route('invitations.register', ['token' => $token]), [
        'name' => 'New Hire',
        'email' => 'newhire@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(workspaceRoute('workspaces.dashboard', $tenant));

    $user = User::query()->where('email', 'newhire@example.com')->firstOrFail();

    expect($user->email_verified_at)->not->toBeNull()
        ->and(TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('status', TenantMembershipStatus::Active->value)
            ->exists())->toBeTrue();

    actingInWorkspace($tenant);
    expect($user->fresh()->hasRole(Role::Adviser->value))->toBeTrue();
});

test('owners can change a member role and it is audited', function () {
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();
    $member = workspaceMember($tenant, Role::Adviser);
    $membership = TenantMembership::query()
        ->where('tenant_id', $tenant->id)
        ->where('user_id', $member->id)
        ->firstOrFail();

    $this->actingAs($owner)
        ->patch(workspaceRoute('workspaces.members.update', $tenant, [
            'membership' => $membership,
        ]), [
            'role' => Role::Reviewer->value,
        ])
        ->assertRedirect(workspaceRoute('workspaces.members.index', $tenant));

    actingInWorkspace($tenant);
    expect($member->fresh()->hasRole(Role::Reviewer->value))->toBeTrue()
        ->and($member->fresh()->hasRole(Role::Adviser->value))->toBeFalse();

    expect(Activity::query()
        ->where('event', AuditEvent::MemberRoleChanged->value)
        ->exists())->toBeTrue();
});

test('the last owner cannot be demoted or removed', function () {
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();
    $membership = TenantMembership::query()
        ->where('tenant_id', $tenant->id)
        ->where('user_id', $owner->id)
        ->firstOrFail();

    $this->actingAs($owner)
        ->patch(workspaceRoute('workspaces.members.update', $tenant, [
            'membership' => $membership,
        ]), [
            'role' => Role::Administrator->value,
        ])
        ->assertSessionHasErrors('role');

    $this->actingAs($owner)
        ->delete(workspaceRoute('workspaces.members.destroy', $tenant, [
            'membership' => $membership,
        ]))
        ->assertForbidden();
});

test('owners can suspend and remove another member', function () {
    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();
    $member = workspaceMember($tenant, Role::Adviser);
    $membership = TenantMembership::query()
        ->where('tenant_id', $tenant->id)
        ->where('user_id', $member->id)
        ->firstOrFail();

    $this->actingAs($owner)
        ->patch(workspaceRoute('workspaces.members.update', $tenant, [
            'membership' => $membership,
        ]), [
            'status' => TenantMembershipStatus::Suspended->value,
        ])
        ->assertRedirect(workspaceRoute('workspaces.members.index', $tenant));

    expect($membership->fresh()->status)->toBe(TenantMembershipStatus::Suspended);

    $this->actingAs($member)
        ->get(workspaceRoute('workspaces.dashboard', $tenant))
        ->assertNotFound();

    $this->actingAs($owner)
        ->delete(workspaceRoute('workspaces.members.destroy', $tenant, [
            'membership' => $membership->id,
        ]))
        ->assertRedirect(workspaceRoute('workspaces.members.index', $tenant));

    expect(TenantMembership::query()->whereKey($membership->id)->exists())->toBeFalse();

    expect(Activity::query()
        ->where('event', AuditEvent::MemberSuspended->value)
        ->exists())->toBeTrue()
        ->and(Activity::query()
            ->where('event', AuditEvent::MemberRemoved->value)
            ->exists())->toBeTrue();
});

test('invitations can be resent and revoked', function () {
    Notification::fake();

    ['owner' => $owner, 'tenant' => $tenant] = provisionWorkspace();

    $this->actingAs($owner)
        ->post(workspaceRoute('workspaces.members.store', $tenant), [
            'email' => 'pending@example.com',
            'role' => Role::ReadOnly->value,
        ]);

    $invitation = TenantInvitation::query()
        ->where('email', 'pending@example.com')
        ->firstOrFail();

    Notification::fake();

    $this->actingAs($owner)
        ->post(workspaceRoute('workspaces.members.invitations.resend', $tenant, [
            'invitation' => $invitation,
        ]))
        ->assertRedirect(workspaceRoute('workspaces.members.index', $tenant));

    Notification::assertSentOnDemand(WorkspaceMemberInviteNotification::class);

    $this->actingAs($owner)
        ->delete(workspaceRoute('workspaces.members.invitations.destroy', $tenant, [
            'invitation' => $invitation,
        ]))
        ->assertRedirect(workspaceRoute('workspaces.members.index', $tenant));

    expect($invitation->fresh()->isRevoked())->toBeTrue();

    expect(Activity::query()
        ->where('event', AuditEvent::MemberInvitationRevoked->value)
        ->exists())->toBeTrue();
});

test('members cannot manage another tenant workspace', function () {
    ['owner' => $ownerA, 'tenant' => $tenantA] = provisionWorkspace('Firm A');
    ['owner' => $ownerB, 'tenant' => $tenantB] = provisionWorkspace('Firm B');

    $this->actingAs($ownerA)
        ->post(workspaceRoute('workspaces.members.store', $tenantB), [
            'email' => 'intruder@example.com',
            'role' => Role::Adviser->value,
        ])
        ->assertNotFound();

    expect(TenantInvitation::query()
        ->withoutGlobalScopes()
        ->where('tenant_id', $tenantB->id)
        ->where('email', 'intruder@example.com')
        ->exists())->toBeFalse();

    // Keep ownerB referenced so the second workspace stays provisioned.
    expect($ownerB->belongsToTenant($tenantB))->toBeTrue()
        ->and($ownerA->belongsToTenant($tenantA))->toBeTrue();
});
