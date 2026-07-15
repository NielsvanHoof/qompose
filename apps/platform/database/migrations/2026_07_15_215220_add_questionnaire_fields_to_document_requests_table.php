<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            // Questionnaire item type: file | text | boolean.
            $table->string('type')->default('file')->after('dossier_id');
            $table->text('answer_text')->nullable()->after('instructions');
            $table->boolean('answer_boolean')->nullable()->after('answer_text');
            $table->timestamp('answered_at')->nullable()->after('answer_boolean');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn(['type', 'answer_text', 'answer_boolean', 'answered_at']);
        });
    }
};
