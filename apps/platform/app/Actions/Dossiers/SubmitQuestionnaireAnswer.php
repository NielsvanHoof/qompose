<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Models\DocumentRequest;

final class SubmitQuestionnaireAnswer
{
    /**
     * Store a text or boolean answer and mark the request as submitted.
     */
    public function handle(
        DocumentRequest $documentRequest,
        ?string $answerText = null,
        ?bool $answerBoolean = null,
    ): DocumentRequest {
        $documentRequest->submitAnswer($answerText, $answerBoolean);

        return $documentRequest->fresh() ?? $documentRequest;
    }
}
