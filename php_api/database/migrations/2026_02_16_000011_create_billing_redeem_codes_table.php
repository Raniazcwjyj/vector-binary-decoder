<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_redeem_codes', function (Blueprint $table) {
            $table->id();
            $table->string('batch_name', 64)->nullable()->index();
            $table->string('code', 64)->unique();
            $table->string('status', 20)->default('active')->index();
            $table->unsignedSmallInteger('days')->default(0);
            $table->unsignedInteger('credits')->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_redeemed_at')->nullable();
            $table->string('created_by', 64)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_redeem_codes');
    }
};

