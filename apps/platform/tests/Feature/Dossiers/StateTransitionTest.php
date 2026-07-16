<?php

declare(strict_types=1);

use App\Enums\DocumentRequestStatus;
use App\Enums\DossierStatus;
use App\Enums\QuestionnaireItemType;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
    $this->client = Client::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->dossier = Dossier::factory()->create([
        'tenant_id' => $this->tenant->id,
        'client_id' => $this->client->id,
    ]);
});

test('a dossier advances without regressing from later open stages', function () {
    $this->dossier->markAwaitingClient();

    expect($this->dossier->status)->toBe(DossierStatus::AwaitingClient);

    $this->dossier->markInReview();
    $this->dossier->markAwaitingClient();

    expect($this->dossier->status)->toBe(DossierStatus::InReview);

    $this->dossier->complete();

    expect($this->dossier->status)->toBe(DossierStatus::Completed);

    expect(fn () => $this->dossier->markAwaitingClient())
        ->toThrow(
            ValidationException::class,
            'A completed dossier cannot receive a new portal invitation.',
        );

    expect(fn () => $this->dossier->markInReview())
        ->toThrow(ValidationException::class, 'A completed dossier cannot return to review.');

    expect(fn () => $this->dossier->complete())
        ->toThrow(ValidationException::class, 'This dossier is already completed.');
});

test('a dossier must be in review before it can be completed', function () {
    expect(fn () => $this->dossier->complete())
        ->toThrow(ValidationException::class, 'Only a dossier in review can be completed.');

    $this->dossier->markAwaitingClient();

    expect(fn () => $this->dossier->complete())
        ->toThrow(ValidationException::class, 'Only a dossier in review can be completed.');
});

test('an answer can be reviewed and a rejected answer can be corrected', function () {
    $reviewer = User::factory()->create();
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'dossier_id' => $this->dossier->id,
        'type' => QuestionnaireItemType::Text,
    ]);

    $documentRequest->submitAnswer(' First answer ');
    $documentRequest->reject($reviewer, ' Add the registered address. ');

    expect($documentRequest->status)->toBe(DocumentRequestStatus::Rejected)
        ->and($documentRequest->answer_text)->toBe('First answer')
        ->and($documentRequest->rejection_reason)->toBe('Add the registered address.');

    $documentRequest->submitAnswer('Corrected answer');

    expect($documentRequest->status)->toBe(DocumentRequestStatus::Submitted)
        ->and($documentRequest->answer_text)->toBe('Corrected answer')
        ->and($documentRequest->reviewed_by)->toBeNull()
        ->and($documentRequest->reviewed_at)->toBeNull()
        ->and($documentRequest->rejection_reason)->toBeNull();

    $documentRequest->accept($reviewer);

    expect($documentRequest->status)->toBe(DocumentRequestStatus::Accepted)
        ->and($documentRequest->reviewed_by)->toBe($reviewer->id)
        ->and(fn () => $documentRequest->submitAnswer('Another answer'))
        ->toThrow(ValidationException::class, 'An approved item cannot be submitted again.');
});

test('only submitted items can be accepted or rejected', function () {
    $reviewer = User::factory()->create();
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'dossier_id' => $this->dossier->id,
        'type' => QuestionnaireItemType::Text,
    ]);

    expect(fn () => $documentRequest->accept($reviewer))
        ->toThrow(ValidationException::class, 'Only submitted items can be reviewed.');

    $documentRequest->submitAnswer('Answer');

    expect(fn () => $documentRequest->reject($reviewer, '  '))
        ->toThrow(ValidationException::class, 'Explain what the client needs to correct.');
});

test('a submitted upload can be replaced but an accepted upload cannot', function () {
    $reviewer = User::factory()->create();
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $this->tenant->id,
        'dossier_id' => $this->dossier->id,
        'type' => QuestionnaireItemType::File,
    ]);

    $documentRequest->submitUpload();
    $documentRequest->submitUpload();

    expect($documentRequest->status)->toBe(DocumentRequestStatus::Submitted)
        ->and($documentRequest->answered_at)->toBeInstanceOf(Carbon::class);

    $documentRequest->accept($reviewer);

    expect(fn () => $documentRequest->submitUpload())
        ->toThrow(ValidationException::class, 'An approved item cannot be submitted again.');
});
