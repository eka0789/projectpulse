<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['frontend', 'backend', 'design', 'qa', 'devops', 'management', 'other'])->default('other')->index();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->onDelete('set null')->index();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['todo', 'in_progress', 'review', 'done'])->default('todo')->index();
            $table->decimal('estimated_hours', 5, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('source', ['manual', 'ai'])->default('manual');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
