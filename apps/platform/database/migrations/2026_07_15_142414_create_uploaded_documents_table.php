<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploaded_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_request_id')->constrained()->cascadeOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->timestamp('uploaded_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'document_request_id']);
            $table->foreign(['tenant_id', 'document_request_id'])
                ->references(['tenant_id', 'id'])
                ->on('document_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_documents');
    }
};
