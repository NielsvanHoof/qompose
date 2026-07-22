<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Actions\Accounts\CreateNewUser;
use App\Models\TenantInvitation;
use App\Models\TenantMembership;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

final class RegisterAndAcceptTenantInvitationAction
{
    public function __construct(
        private readonly CreateNewUser $createNewUser,
        private readonly AcceptTenantInvitationAction $acceptTenantInvitation,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string, password_confirmation: string}  $input
     */
    public function handle(TenantInvitation $invitation, array $input): TenantMembership
    {
        // Lock registration email to the invite so accounts cannot be hijacked.
        $input['email'] = $invitation->email;

        $user = $this->createNewUser->create($input);

        // Mark verified — they proved inbox access by opening the invite link.
        $user->forceFill(['email_verified_at' => now()])->save();

        Event::dispatch(new Registered($user));

        Auth::login($user);

        return $this->acceptTenantInvitation->handle($invitation, $user);
    }
}
