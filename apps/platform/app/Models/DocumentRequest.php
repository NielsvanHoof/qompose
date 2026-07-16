<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Enums\DocumentRequestStatus;
use App\Enums\QuestionnaireItemType;
use Carbon\CarbonInterface;
use Database\Factories\DocumentRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use function in_array;

/**
 * Questionnaire item on a dossier (file upload, text, or yes/no).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $dossier_id
 * @property QuestionnaireItemType $type
 * @property string $title
 * @property string|null $instructions
 * @property string|null $answer_text
 * @property bool|null $answer_boolean
 * @property CarbonInterface|null $answered_at
 * @property int|null $reviewed_by
 * @property CarbonInterface|null $reviewed_at
 * @property string|null $rejection_reason
 * @property DocumentRequestStatus $status
 * @property int $sort_order
 */
#[Fillable([
    'tenant_id',
    'dossier_id',
    'type',
    'title',
    'instructions',
    'answer_text',
    'answer_boolean',
    'answered_at',
    'reviewed_by',
    'reviewed_at',
    'rejection_reason',
    'status',
    'sort_order',
])]
final class DocumentRequest extends Model
{
    /** @use HasFactory<DocumentRequestFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'file',
        'status' => 'pending',
        'sort_order' => 0,
    ];

    /**
     * @return BelongsTo<Dossier, $this>
     */
    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    /**
     * @return HasOne<UploadedDocument, $this>
     */
    public function uploadedDocument(): HasOne
    {
        return $this->hasOne(UploadedDocument::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submitAnswer(
        ?string $answerText = null,
        ?bool $answerBoolean = null,
    ): void {
        if ($this->type === QuestionnaireItemType::File) {
            throw new InvalidArgumentException('File items must be answered via upload.');
        }

        $this->ensureCanBeSubmitted();

        if ($this->type === QuestionnaireItemType::Text) {
            if ($answerText === null || mb_trim($answerText) === '') {
                throw new InvalidArgumentException('A text answer is required.');
            }

            $this->update([
                'answer_text' => mb_trim($answerText),
                'answer_boolean' => null,
                ...$this->submittedState(),
            ]);

            return;
        }

        if ($answerBoolean === null) {
            throw new InvalidArgumentException('A yes/no answer is required.');
        }

        $this->update([
            'answer_boolean' => $answerBoolean,
            'answer_text' => null,
            ...$this->submittedState(),
        ]);
    }

    public function submitUpload(): void
    {
        if ($this->type !== QuestionnaireItemType::File) {
            throw new InvalidArgumentException('Only file items accept uploads.');
        }

        $this->ensureCanBeSubmitted();
        $this->update($this->submittedState());
    }

    public function accept(User $reviewer): void
    {
        $this->ensureCanBeReviewed();

        $this->update([
            'status' => DocumentRequestStatus::Accepted,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public function reject(User $reviewer, ?string $rejectionReason): void
    {
        $this->ensureCanBeReviewed();

        $normalizedRejectionReason = $rejectionReason === null
            ? null
            : mb_trim($rejectionReason);

        if ($normalizedRejectionReason === null || $normalizedRejectionReason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Explain what the client needs to correct.',
            ]);
        }

        $this->update([
            'status' => DocumentRequestStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $normalizedRejectionReason,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionnaireItemType::class,
            'status' => DocumentRequestStatus::class,
            'answer_boolean' => 'boolean',
            'answered_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    private function ensureCanBeSubmitted(): void
    {
        if (in_array($this->status, [
            DocumentRequestStatus::Pending,
            DocumentRequestStatus::Rejected,
            DocumentRequestStatus::Submitted,
        ], true)) {
            return;
        }

        throw ValidationException::withMessages([
            'document_request' => 'An approved item cannot be submitted again.',
        ]);
    }

    private function ensureCanBeReviewed(): void
    {
        if ($this->status === DocumentRequestStatus::Submitted) {
            return;
        }

        throw ValidationException::withMessages([
            'decision' => 'Only submitted items can be reviewed.',
        ]);
    }

    /**
     * @return array{
     *     status: DocumentRequestStatus,
     *     answered_at: CarbonInterface,
     *     reviewed_by: null,
     *     reviewed_at: null,
     *     rejection_reason: null
     * }
     */
    private function submittedState(): array
    {
        return [
            'status' => DocumentRequestStatus::Submitted,
            'answered_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ];
    }
}
