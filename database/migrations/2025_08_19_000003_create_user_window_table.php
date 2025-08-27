<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_window', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('window_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('window_id')->references('id')->on('windows')->cascadeOnDelete();
            $table->unique(['user_id', 'window_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_window');
    }
};
