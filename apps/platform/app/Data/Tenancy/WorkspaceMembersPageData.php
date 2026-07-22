<?php

declare(strict_types=1);

namespace App\Data\Tenancy;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Full payload for the workspace members Inertia page.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class WorkspaceMembersPageData implements Arrayable
{
    /**
     * @param  list<WorkspaceMemberData>  $members
     * @param  list<WorkspaceInvitationData>  $invitations
     * @param  list<WorkspaceRoleOptionData>  $roleOptions
     */
    public function __construct(
        public array $members,
        public array $invitations,
        public array $roleOptions,
        public bool $canAssignOwner,
    ) {}

    /**
     * @return array{
     *     members: list<array{
     *         id: int,
     *         user_id: int,
     *         name: string,
     *         email: string,
     *         status: string,
     *         role: string|null,
     *         role_label: string|null,
     *         joined_at: string|null,
     *         last_accessed_at: string|null,
     *         is_current_user: bool
     *     }>,
     *     invitations: list<array{
     *         id: int,
     *         email: string,
     *         role: string,
     *         role_label: string,
     *         invited_at: string,
     *         expires_at: string
     *     }>,
     *     role_options: list<array{value: string, label: string}>,
     *     can_assign_owner: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'members' => array_map(
                static fn (WorkspaceMemberData $member): array => $member->toArray(),
                $this->members,
            ),
            'invitations' => array_map(
                static fn (WorkspaceInvitationData $invitation): array => $invitation->toArray(),
                $this->invitations,
            ),
            'role_options' => array_map(
                static fn (WorkspaceRoleOptionData $option): array => $option->toArray(),
                $this->roleOptions,
            ),
            'can_assign_owner' => $this->canAssignOwner,
        ];
    }
}
