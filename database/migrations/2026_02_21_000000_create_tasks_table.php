<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();

            $table->string('title', 255);
            $table->text('description')->nullable();

            // Backed by TaskStatus enum values: pending | in_progress | completed
            $table->string('status', 20)->default('pending');

            // Soft-delete flag — preserves history without cascading deletes
            $table->boolean('is_deleted')->default(false);

            $table->timestamps(); // created_at, updated_at

            // Query optimisation: most lookups filter by is_deleted and status
            $table->index('is_deleted');
            $table->index('status');
            $table->index(['is_deleted', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
