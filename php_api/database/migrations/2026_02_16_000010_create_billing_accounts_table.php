<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('account_code', 32)->unique();
            $table->unsignedInteger('balance_credits')->default(0);
            $table->unsignedInteger('total_spent_credits')->default(0);
            $table->timestamp('vip_expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_accounts');
    }
};

