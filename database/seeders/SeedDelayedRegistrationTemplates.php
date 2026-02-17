<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmsTemplate;

class SeedDelayedRegistrationTemplates extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SmsTemplate::where('service_type', 'Delayed Registration')
            ->where('event_key', 'posting_notice')
            ->delete();
        $templates = [
            [
                'service_type' => 'Delayed Registration',
                'event_key' => 'verification_started',
                'template_body' => 'We received your Delayed Registration requirements for {{citizen_name}} (Ref: {{reference_no}}). Your application is now subject to verification. This process typically takes up to 5 working days.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Delayed Registration',
                'event_key' => 'requirements_incomplete',
                'template_body' => 'Your Delayed Registration requirements for {{citizen_name}} (Ref: {{reference_no}}) are incomplete. Please complete the required documents at the MCRO to proceed.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Delayed Registration',
                'event_key' => 'verification_inconsistent',
                'template_body' => 'Verification for {{citizen_name}} (Ref: {{reference_no}}) found inconsistencies. Please visit the MCRO office to provide additional documents or clarifications.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Delayed Registration',
                'event_key' => 'verification_consistent',
                'template_body' => 'Verification complete for {{citizen_name}} (Ref: {{reference_no}}). Your application is consistent. We will proceed to posting notice, which undergoes a 10-day posting period.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Delayed Registration',
                'event_key' => 'ready_for_release',
                'template_body' => 'Good day! Your Delayed Registration (Ref: {{reference_no}}) for {{citizen_name}} has completed the 10-day posting period and is now ready for release. Please visit the MCRO office.',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            SmsTemplate::updateOrCreate(
                [
                    'service_type' => $template['service_type'],
                    'event_key' => $template['event_key']
                ],
                [
                    'template_body' => $template['template_body'],
                    'is_active' => $template['is_active']
                ]
            );
        }
    }
}
