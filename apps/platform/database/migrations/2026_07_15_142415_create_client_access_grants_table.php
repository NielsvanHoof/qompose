<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dossier_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->foreign(['tenant_id', 'dossier_id'])
                ->references(['tenant_id', 'id'])
                ->on('dossiers')
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'dossier_id']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_access_grants');
    }
};
