<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Unify file uploads and text/boolean answers under "submitted".
        DB::table('document_requests')
            ->where('status', 'uploaded')
            ->update(['status' => 'submitted']);
    }

    public function down(): void
    {
        DB::table('document_requests')
            ->where('status', 'submitted')
            ->update(['status' => 'uploaded']);
    }
};
