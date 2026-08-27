<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => '💳 Refund Processing Confirmation',
                'category' => 'refunds',
                'subject' => 'Re: Refund Processing Update',
                'body_text' => "Hello,\n\nWe have received your refund request for ticket #{ticket_number}.\nOur finance department has processed the refund to your original payment method. Please allow 3 to 5 business days for the funds to reflect on your account statement.\n\nIf you have any further questions, please feel free to reply to this email.\n\nBest regards,\n{company_name} Support Team",
            ],
            [
                'name' => '📑 KYB / KYC Verification Documents Request',
                'category' => 'verification',
                'subject' => 'Re: Required Documents for Account Verification',
                'body_text' => "Hello,\n\nIn order to complete your account verification for {company_name}, please provide the following documents:\n\n1. Recent Utility Bill or Bank Statement (issued within last 3 months)\n2. Valid Passport / Government ID of the UBO\n3. Corporate Registration Certificate\n\nPlease attach the requested files directly in reply to this email.\n\nBest regards,\n{company_name} Compliance Team",
            ],
            [
                'name' => '⚠️ Chargeback / Dispute Notice Inquiry',
                'category' => 'compliance',
                'subject' => 'Re: Inquiry Regarding Transaction Dispute',
                'body_text' => "Dear Customer,\n\nWe have received a dispute notification regarding a recent transaction on {website_url}. We would like to resolve this matter directly with you.\n\nPlease contact us or reply to this email with your order details so we can assist you promptly.\n\nKind regards,\n{company_name} Support Team",
            ],
            [
                'name' => '⚙️ Technical Support Investigation Update',
                'category' => 'technical',
                'subject' => 'Re: Technical Support Ticket Update',
                'body_text' => "Hello,\n\nThank you for reaching out regarding your technical issue. Our engineering team is currently investigating ticket #{ticket_number}.\n\nWe will update you as soon as the issue is resolved.\n\nThank you for your patience,\n{company_name} Technical Team",
            ],
        ];

        foreach ($templates as $t) {
            EmailTemplate::firstOrCreate(['name' => $t['name']], $t);
        }
    }
}
