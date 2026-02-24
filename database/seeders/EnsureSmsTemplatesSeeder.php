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
            [
                'service_type' => 'Request for PSA documents through BREQS',
                'event_key' => 'ready_for_pickup',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your document is ready for pick up. Please bring your claim stub to the MCRO office during office hours.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Endorsement for Negative PSA - Positive LCRO',
                'event_key' => 'psa_sent',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been sent to PSA-RSSO CAR for uploading into the PSA database. We will notify again once approved.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Endorsement for Negative PSA - Positive LCRO',
                'event_key' => 'psa_no_feedback_uploaded',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been successfully uploaded into the PSA database. You may now request for your PSA document in our office.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Endorsement for Negative PSA - Positive LCRO',
                'event_key' => 'psa_feedback_received',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). PSA sent back your documents with feedback for further processing. The document is not yet ready for release. We will process and send to PSA again.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Endorsement for Negative PSA - Positive LCRO',
                'event_key' => 'psa_resent_for_processing',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been reworked and resent to PSA for processing.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Endorsement for Blurred PSA - Clear LCRO File',
                'event_key' => 'psa_sent',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been sent to PSA-RSSO CAR for replacement of your file in the PSA database.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Endorsement for Blurred PSA - Clear LCRO File',
                'event_key' => 'psa_no_feedback_uploaded',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been approved for uploading into the PSA database. You may now request your new PSA document from our office.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Endorsement for Blurred PSA - Clear LCRO File',
                'event_key' => 'psa_feedback_received',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). PSA sent back your documents for further processing. The document is not yet ready for release. We will process and send to PSA again.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Endorsement for Blurred PSA - Clear LCRO File',
                'event_key' => 'psa_resent_for_processing',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been reworked and resent to PSA for uploading into the PSA database.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Endorsement of Legal Instrument & MC 2010-04 & Court Order',
                'event_key' => 'psa_sent',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been forwarded to PSA Central office for uploading to the PSA database.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Endorsement of Legal Instrument & MC 2010-04 & Court Order',
                'event_key' => 'psa_no_feedback_uploaded',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been approved for uploading into the PSA database. You may now request your new PSA document from our office.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Endorsement of Legal Instrument & MC 2010-04 & Court Order',
                'event_key' => 'psa_feedback_received',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). PSA sent back your documents for further processing. The document is not yet ready for release. We will process and send to PSA again.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Endorsement of Legal Instrument & MC 2010-04 & Court Order',
                'event_key' => 'psa_resent_for_processing',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been reworked and resent to PSA for uploading into the PSA database.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 - Clerical Error',
                'event_key' => 'petition_ready_for_filing',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition is ready for filing. Please come to the MCRO office to sign and pay at the Treasury Office for filing and processing fees.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 - Clerical Error',
                'event_key' => 'petition_ready_for_posting',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been filed and is now ready for 10-day posting. Posting starts one business day after filing.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 - Clerical Error',
                'event_key' => 'sent_to_psa_legal',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been sent to PSA Legal Services for review.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 - Clerical Error',
                'event_key' => 'psa_affirmed',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been affirmed by PSA. Please come to the MCRO office within 15 days from receipt of this notification.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 - Clerical Error',
                'event_key' => 'psa_impugned',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been impugned by PSA. Please come to the MCRO office for further processing.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 - Clerical Error',
                'event_key' => 'psa_resent_for_review',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been reworked and resent to PSA Legal Services for review.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 & RA 10172',
                'event_key' => 'petition_ready_for_filing',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition is ready for filing. Please come to the MCRO office to sign and pay at the Treasury Office for filing and processing fees.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 & RA 10172',
                'event_key' => 'petition_ready_for_posting',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been filed and is now ready for 10-day posting. Posting starts one business day after filing.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 & RA 10172',
                'event_key' => 'petition_published',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been published for two consecutive weeks. Decision shall be rendered 7 business days after the last publication date.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 & RA 10172',
                'event_key' => 'sent_to_psa_legal',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition decision has been sent to PSA Legal Services for review.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 & RA 10172',
                'event_key' => 'psa_affirmed',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been affirmed by PSA. Please come to the MCRO office within 15 days from receipt of this notification.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 & RA 10172',
                'event_key' => 'psa_impugned',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been impugned by PSA. Please come to the MCRO office for further processing.',
                'is_active' => true,
            ],
            [
                'service_type' => 'Petitions filed under RA 9048 & RA 10172',
                'event_key' => 'psa_resent_for_review',
                'template_body' => 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been reworked and resent to PSA Legal Services for review.',
                'is_active' => true,
            ],
        ];

        // Ensure no Frontline Service templates exist
        SmsTemplate::where('service_type', 'Frontline Service')->delete();
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
