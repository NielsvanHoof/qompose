<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_template_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('type')->default('file');
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['questionnaire_template_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_template_items');
    }
};
