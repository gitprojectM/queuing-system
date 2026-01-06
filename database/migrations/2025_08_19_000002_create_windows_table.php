<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		// Safety check so this migration is effectively a no-op
		// if the `windows` table was already created by an earlier migration.
		if (! Schema::hasTable('windows')) {
			Schema::create('windows', function (Blueprint $table) {
				$table->id();
				$table->string('name');
				$table->text('description')->nullable();
				$table->timestamps();
			});
		}
	}

	public function down(): void
	{
		if (Schema::hasTable('windows')) {
			Schema::drop('windows');
		}
	}
};

