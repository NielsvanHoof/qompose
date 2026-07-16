<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Enums\DocumentRequestStatus;
use App\Enums\QuestionnaireItemType;
use App\Models\DocumentRequest;
use InvalidArgumentException;

final class SubmitQuestionnaireAnswer
{
    /**
     * Store a text or boolean answer and mark the request as submitted.
     */
    public function __invoke(
        DocumentRequest $documentRequest,
        ?string $answerText = null,
        ?bool $answerBoolean = null,
    ): DocumentRequest {
        if ($documentRequest->type === QuestionnaireItemType::File) {
            throw new InvalidArgumentException('File items must be answered via upload.');
        }

        if ($documentRequest->type === QuestionnaireItemType::Text) {
            if ($answerText === null || mb_trim($answerText) === '') {
                throw new InvalidArgumentException('A text answer is required.');
            }

            $documentRequest->update([
                'answer_text' => mb_trim($answerText),
                'answer_boolean' => null,
                'answered_at' => now(),
                'status' => DocumentRequestStatus::Submitted,
            ]);

            return $documentRequest->fresh() ?? $documentRequest;
        }

        if ($answerBoolean === null) {
            throw new InvalidArgumentException('A yes/no answer is required.');
        }

        $documentRequest->update([
            'answer_boolean' => $answerBoolean,
            'answer_text' => null,
            'answered_at' => now(),
            'status' => DocumentRequestStatus::Submitted,
        ]);

        return $documentRequest->fresh() ?? $documentRequest;
    }
}
