<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
