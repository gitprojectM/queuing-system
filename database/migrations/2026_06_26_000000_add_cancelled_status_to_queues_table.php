<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL requires modifying the enum column via raw SQL.
        DB::statement("ALTER TABLE queues MODIFY COLUMN status ENUM('waiting','assigned','completed','cancelled') NOT NULL DEFAULT 'waiting'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE queues MODIFY COLUMN status ENUM('waiting','assigned','completed') NOT NULL DEFAULT 'waiting'");
    }
};
