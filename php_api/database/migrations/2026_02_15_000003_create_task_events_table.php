<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('task_id');
            $table->string('level', 20)->index();
            $table->string('message');
            $table->json('context_json')->nullable();
            $table->timestamps();

            $table
                ->foreign('task_id')
                ->references('id')
                ->on('conversion_tasks')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_events');
    }
};
