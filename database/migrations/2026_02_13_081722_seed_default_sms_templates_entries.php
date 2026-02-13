<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $existsPosting = DB::table('sms_templates')
            ->where('service_type', 'application_for_marriage_license')
            ->where('event_key', 'posting_completed')
            ->exists();
        if (!$existsPosting) {
            DB::table('sms_templates')->insert([
                'service_type' => 'application_for_marriage_license',
                'event_key' => 'posting_completed',
                'template_body' => 'Hello {{citizen_name}}, your marriage license application (Ref: {{reference_no}}) has completed the 10-day posting period. Please visit MCRO for next steps.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $existsReleased = DB::table('sms_templates')
            ->where('service_type', 'application_for_marriage_license')
            ->where('event_key', 'released')
            ->exists();
        if (!$existsReleased) {
            DB::table('sms_templates')->insert([
                'service_type' => 'application_for_marriage_license',
                'event_key' => 'released',
                'template_body' => 'Hello {{citizen_name}}, your marriage license (Ref: {{reference_no}}) has been issued. Please claim at MCRO.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sms_templates')
            ->where('service_type', 'application_for_marriage_license')
            ->whereIn('event_key', ['posting_completed', 'released'])
            ->delete();
    }
};
