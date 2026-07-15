<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dossier_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->foreign(['tenant_id', 'dossier_id'])
                ->references(['tenant_id', 'id'])
                ->on('dossiers')
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'dossier_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
