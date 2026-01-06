<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('windows') && ! Schema::hasColumn('windows', 'current_client_id')) {
			Schema::table('windows', function (Blueprint $table) {
				$table->unsignedBigInteger('current_client_id')->nullable()->after('description');
			});
		}
	}

	public function down(): void
	{
		if (Schema::hasTable('windows') && Schema::hasColumn('windows', 'current_client_id')) {
			Schema::table('windows', function (Blueprint $table) {
				$table->dropColumn('current_client_id');
			});
		}
	}
};

