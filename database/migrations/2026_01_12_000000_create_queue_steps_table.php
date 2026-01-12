<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained('queues')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('window_id')->nullable()->constrained('windows')->nullOnDelete();
            $table->unsignedInteger('step_order')->default(1);
            $table->enum('status', ['waiting', 'assigned', 'completed'])->default('waiting');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_steps');
    }
};
