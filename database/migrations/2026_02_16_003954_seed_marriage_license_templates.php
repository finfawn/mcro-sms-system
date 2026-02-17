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
        $postingExists = DB::table('sms_templates')
            ->where('service_type', 'Application for Marriage License')
            ->where('event_key', 'posting_notice')
            ->exists();
        if (!$postingExists) {
            DB::table('sms_templates')->insert([
                'service_type' => 'Application for Marriage License',
                'event_key' => 'posting_notice',
                'template_body' => 'Your Application for Marriage License has been posted today. Your Marriage License shall be issued after the 10 days posting of the notice.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $readyExists = DB::table('sms_templates')
            ->where('service_type', 'Application for Marriage License')
            ->where('event_key', 'ready_for_pickup')
            ->exists();
        if (!$readyExists) {
            DB::table('sms_templates')->insert([
                'service_type' => 'Application for Marriage License',
                'event_key' => 'ready_for_pickup',
                'template_body' => 'Your Marriage License with Reg # {{registration_number}} is now ready for pick-up. Please bring valid ID card and prepare P102.00 as payment for form fee and license fee to be paid at the Municipal Treasury.',
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
            ->where('service_type', 'Application for Marriage License')
            ->whereIn('event_key', ['posting_notice','ready_for_pickup'])
            ->delete();
    }
};
