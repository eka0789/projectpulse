<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_task_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->string('provider');
            $table->string('model');
            $table->string('brief_hash', 64);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'timeout'])->default('pending');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('latency_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_task_generations');
    }
};
