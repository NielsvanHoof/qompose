<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Correlate async Textract SNS/SQS completion messages to uploaded rows.
     */
    public function up(): void
    {
        Schema::table('uploaded_documents', function (Blueprint $table) {
            $table->string('textract_job_id')->nullable()->after('processing_finished_at');
            $table->index('textract_job_id');
        });
    }

    public function down(): void
    {
        Schema::table('uploaded_documents', function (Blueprint $table) {
            $table->dropIndex(['textract_job_id']);
            $table->dropColumn('textract_job_id');
        });
    }
};
