<?php

declare(strict_types=1);

namespace App\Data\Tenancy;

/**
 * One workspace member row for the members Inertia page.
 */
final readonly class WorkspaceMemberData
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        public string $email,
        public string $status,
        public ?string $role,
        public ?string $roleLabel,
        public ?string $joinedAt,
        public ?string $lastAccessedAt,
        public bool $isCurrentUser,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     user_id: int,
     *     name: string,
     *     email: string,
     *     status: string,
     *     role: string|null,
     *     role_label: string|null,
     *     joined_at: string|null,
     *     last_accessed_at: string|null,
     *     is_current_user: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'role' => $this->role,
            'role_label' => $this->roleLabel,
            'joined_at' => $this->joinedAt,
            'last_accessed_at' => $this->lastAccessedAt,
            'is_current_user' => $this->isCurrentUser,
        ];
    }
}
