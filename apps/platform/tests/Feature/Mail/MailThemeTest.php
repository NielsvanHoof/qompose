<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Notifications\Tenancy\WorkspaceMemberInviteNotification;

/**
 * Rendered Markdown mail should pick up the published Qompose theme
 * (coral primary CTA + shield logo in the header).
 */
test('markdown mail theme uses qompose brand color and shield logo', function () {
    $mail = (new WorkspaceMemberInviteNotification(
        invitationId: 1,
        firmName: 'Acme Accountants',
        role: Role::Adviser,
        acceptUrl: 'https://example.test/invitations/accept',
        expiresAt: now()->addDays(7),
    ))->toMail(new stdClass);

    $html = (string) $mail->render();

    expect($html)
        ->toContain('#ef5734')
        ->toContain('images/brand/shield-primary.png');
});
