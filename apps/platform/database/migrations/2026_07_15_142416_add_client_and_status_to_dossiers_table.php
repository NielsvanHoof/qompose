<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->foreignId('client_id')->after('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft')->after('reference');

            $table->foreign(['tenant_id', 'client_id'])
                ->references(['tenant_id', 'id'])
                ->on('clients')
                ->cascadeOnDelete();

            $table->index(['tenant_id', 'client_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropForeign(['tenant_id', 'client_id']);
            $table->dropForeign(['client_id']);
            $table->dropIndex(['tenant_id', 'client_id']);
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropColumn(['client_id', 'status']);
        });
    }
};
