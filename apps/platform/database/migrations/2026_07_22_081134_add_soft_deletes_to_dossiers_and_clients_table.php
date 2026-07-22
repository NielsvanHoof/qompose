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
            $table->dropForeign(['client_id']);
            $table->dropForeign(['tenant_id', 'client_id']);

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->restrictOnDelete();

            $table->foreign(['tenant_id', 'client_id'])
                ->references(['tenant_id', 'id'])
                ->on('clients')
                ->restrictOnDelete();

            $table->softDeletes();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropSoftDeletes();

            $table->dropForeign(['client_id']);
            $table->dropForeign(['tenant_id', 'client_id']);

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->cascadeOnDelete();

            $table->foreign(['tenant_id', 'client_id'])
                ->references(['tenant_id', 'id'])
                ->on('clients')
                ->cascadeOnDelete();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
