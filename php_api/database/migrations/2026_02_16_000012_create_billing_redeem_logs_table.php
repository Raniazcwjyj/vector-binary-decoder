<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_redeem_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('billing_account_id');
            $table->foreignId('billing_redeem_code_id')->nullable()->constrained('billing_redeem_codes')->nullOnDelete();
            $table->string('code', 64);
            $table->unsignedSmallInteger('days')->default(0);
            $table->unsignedInteger('credits')->default(0);
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['billing_account_id', 'created_at']);
            $table->foreign('billing_account_id')->references('id')->on('billing_accounts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_redeem_logs');
    }
};

