<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_ledgers', function (Blueprint $table) {
            $table->id();
            $table->uuid('billing_account_id');
            $table->uuid('task_id')->nullable();
            $table->string('type', 20)->index();
            $table->integer('credits_delta');
            $table->unsignedInteger('balance_after');
            $table->string('note', 255)->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['billing_account_id', 'created_at']);
            $table->foreign('billing_account_id')->references('id')->on('billing_accounts')->cascadeOnDelete();
            $table->foreign('task_id')->references('id')->on('conversion_tasks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_ledgers');
    }
};

