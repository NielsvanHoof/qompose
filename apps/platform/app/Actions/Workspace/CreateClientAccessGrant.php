<?php

declare(strict_types=1);

namespace App\Actions\Workspace;

use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Support\Str;

final class CreateClientAccessGrant
{
    /**
     * Create a hashed client portal grant and return the one-time plain token.
     *
     * @return array{grant: ClientAccessGrant, plain_text_token: string}
     */
    public function __invoke(Dossier $dossier, User $createdBy, int $expiresInDays = 7): array
    {
        $plainTextToken = Str::random(64);

        $grant = $dossier->clientAccessGrants()->create([
            'token' => ClientAccessGrant::hashToken($plainTextToken),
            'expires_at' => now()->addDays($expiresInDays),
            'created_by' => $createdBy->id,
        ]);

        return [
            'grant' => $grant,
            'plain_text_token' => $plainTextToken,
        ];
    }
}
