<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Models\DocumentRequest;
use App\Transitions\DocumentRequestTransitions;

final class SubmitQuestionnaireAnswer
{
    public function __construct(
        private readonly DocumentRequestTransitions $documentRequestTransitions,
    ) {}

    /**
     * Store a text or boolean answer and mark the request as submitted.
     */
    public function handle(
        DocumentRequest $documentRequest,
        ?string $answerText = null,
        ?bool $answerBoolean = null,
    ): DocumentRequest {
        $this->documentRequestTransitions->submitAnswer(
            $documentRequest,
            $answerText,
            $answerBoolean,
        );

        return $documentRequest->fresh() ?? $documentRequest;
    }
}
