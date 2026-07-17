<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uploaded_documents', function (Blueprint $table) {
            // Parallel OCR lifecycle — independent of document-request review status.
            $table->string('processing_status')->default('pending')->after('rejection_reason');
            $table->longText('extracted_text')->nullable()->after('processing_status');
            $table->string('processing_error')->nullable()->after('extracted_text');
            $table->timestamp('processing_started_at')->nullable()->after('processing_error');
            $table->timestamp('processing_finished_at')->nullable()->after('processing_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('uploaded_documents', function (Blueprint $table) {
            $table->dropColumn([
                'processing_status',
                'extracted_text',
                'processing_error',
                'processing_started_at',
                'processing_finished_at',
            ]);
        });
    }
};
