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
            if (!Schema::hasColumn('services', 'sms_ready_sent')) {
                $table->boolean('sms_ready_sent')->default(false)->after('sms_posting_sent');
            }
            if (!Schema::hasColumn('services', 'registration_number')) {
                $table->string('registration_number')->nullable()->after('reference_no');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'sms_ready_sent')) {
                $table->dropColumn('sms_ready_sent');
            }
            if (Schema::hasColumn('services', 'registration_number')) {
                $table->dropColumn('registration_number');
            }
        });
    }
};
