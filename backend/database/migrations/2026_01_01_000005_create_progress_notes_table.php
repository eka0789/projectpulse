<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('note');
            $table->string('status_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_notes');
    }
};
