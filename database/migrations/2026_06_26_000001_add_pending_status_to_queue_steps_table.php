<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE queue_steps MODIFY COLUMN status ENUM('pending','waiting','assigned','completed') NOT NULL DEFAULT 'waiting'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE queue_steps MODIFY COLUMN status ENUM('waiting','assigned','completed') NOT NULL DEFAULT 'waiting'");
    }
};
