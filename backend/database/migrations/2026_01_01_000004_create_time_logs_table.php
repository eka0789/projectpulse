<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->date('work_date');
            $table->integer('duration_minutes');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_logs');
    }
};
