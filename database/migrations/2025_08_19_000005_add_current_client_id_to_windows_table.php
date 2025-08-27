<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('windows', 'current_client_id')) {
            Schema::table('windows', function (Blueprint $table) {
                $table->unsignedBigInteger('current_client_id')->nullable()->after('description');
                // If you have a clients table, you can add a foreign key:
                // $table->foreign('current_client_id')->references('id')->on('clients')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('windows', function (Blueprint $table) {
            $table->dropColumn('current_client_id');
        });
    }
};
