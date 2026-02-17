<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Remove the 'released' template
        DB::table('sms_templates')
            ->where('event_key', 'released')
            ->delete();

        // 2. Rename 'ready_for_pickup' to 'releasing'
        DB::table('sms_templates')
            ->where('event_key', 'ready_for_pickup')
            ->update(['event_key' => 'releasing']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Rename 'releasing' back to 'ready_for_pickup'
        DB::table('sms_templates')
            ->where('event_key', 'releasing')
            ->update(['event_key' => 'ready_for_pickup']);

        // 2. Restore the 'released' template (optional, cannot fully restore deleted data)
        // We will just leave it deleted as we can't restore user customizations.
    }
};
