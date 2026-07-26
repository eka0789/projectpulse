<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('client_brief')->nullable();
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable()->index();
            $table->enum('status', ['draft', 'active', 'on_hold', 'completed', 'cancelled'])->default('draft')->index();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
