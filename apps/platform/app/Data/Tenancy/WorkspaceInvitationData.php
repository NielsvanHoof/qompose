<?php

declare(strict_types=1);

namespace App\Data\Tenancy;

/**
 * One pending invitation row for the members Inertia page.
 */
final readonly class WorkspaceInvitationData
{
    public function __construct(
        public int $id,
        public string $email,
        public string $role,
        public string $roleLabel,
        public string $invitedAt,
        public string $expiresAt,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     email: string,
     *     role: string,
     *     role_label: string,
     *     invited_at: string,
     *     expires_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role,
            'role_label' => $this->roleLabel,
            'invited_at' => $this->invitedAt,
            'expires_at' => $this->expiresAt,
        ];
    }
}
