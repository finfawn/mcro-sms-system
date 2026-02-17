<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SmsTemplate;

class EnsureSmsTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'service_type' => 'Application for Marriage License',
                'event_key' => 'posting_notice',
                'template_body' => 'Your Application for Marriage License has been posted today. Your Marriage License shall be issued after the 10 days posting of the notice.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Application for Marriage License',
                'event_key' => 'releasing',
                'template_body' => 'Good day! Your Marriage License is now available for release. Please claim it at the MCRO office during office hours.',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            $t = SmsTemplate::firstOrCreate(
                [
                    'service_type' => $template['service_type'],
                    'event_key' => $template['event_key']
                ],
                [
                    'template_body' => $template['template_body'],
                    'is_active' => $template['is_active']
                ]
            );
            echo "Processed: {$t->id} - {$t->service_type} - {$t->event_key}\n";
        }
    }
}
