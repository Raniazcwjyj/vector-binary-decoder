<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversion_tasks', function (Blueprint $table) {
            $table->uuid('billing_account_id')->nullable()->after('api_key_id')->index();
            $table->string('billing_mode', 20)->nullable()->after('attempts');
            $table->unsignedInteger('billing_credits_cost')->default(0)->after('billing_mode');
            $table->timestamp('billed_at')->nullable()->after('queued_at');
            $table->timestamp('refunded_at')->nullable()->after('billed_at');

            $table->foreign('billing_account_id')->references('id')->on('billing_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversion_tasks', function (Blueprint $table) {
            $table->dropForeign(['billing_account_id']);
            $table->dropColumn([
                'billing_account_id',
                'billing_mode',
                'billing_credits_cost',
                'billed_at',
                'refunded_at',
            ]);
        });
    }
};

