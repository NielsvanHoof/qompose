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
            $table->foreignId('responsible_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('reminder_interval_days')->nullable();
            $table->timestamp('next_reminder_at')->nullable();
            $table->timestamp('last_client_message_sent_at')->nullable();
            $table->timestamp('last_client_opened_at')->nullable();

            $table->index(
                ['tenant_id', 'due_date'],
                'dossiers_tenant_due_date_index',
            );
            $table->index(
                ['tenant_id', 'next_reminder_at'],
                'dossiers_tenant_next_reminder_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('dossiers', function (Blueprint $table) {
            $table->dropIndex('dossiers_tenant_due_date_index');
            $table->dropIndex('dossiers_tenant_next_reminder_index');
            $table->dropConstrainedForeignId('responsible_user_id');
            $table->dropColumn([
                'due_date',
                'reminder_interval_days',
                'next_reminder_at',
                'last_client_message_sent_at',
                'last_client_opened_at',
            ]);
        });
    }
};
