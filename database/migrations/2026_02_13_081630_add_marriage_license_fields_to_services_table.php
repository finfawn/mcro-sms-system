<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->date('payment_date')->nullable();
            $table->date('posting_start_date')->nullable();
            $table->date('release_date')->nullable();
            $table->boolean('sms_posting_sent')->default(false);
            $table->boolean('sms_release_sent')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'payment_date',
                'posting_start_date',
                'release_date',
                'sms_posting_sent',
                'sms_release_sent',
            ]);
        });
    }
};
