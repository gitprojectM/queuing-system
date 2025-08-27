<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('window_id')->nullable();
            $table->integer('queue_number');
            $table->enum('status', ['waiting', 'assigned', 'completed'])->default('waiting');
            $table->timestamps();

            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->foreign('window_id')->references('id')->on('windows')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
