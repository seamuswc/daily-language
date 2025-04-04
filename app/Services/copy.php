<?php

namespace App\Services;

use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Ses\V20201002\SesClient;
use TencentCloud\Ses\V20201002\Models\SendEmailRequest;
use TencentCloud\Ses\V20201002\Models\Template;
use Illuminate\Support\Facades\Log;

class TencentSesService
{
    protected $client;

    public function __construct()
    {
        $cred = new Credential(
            env('TENCENT_SECRET_ID'),
            env('TENCENT_SECRET_KEY')
        );

        $this->client = new SesClient($cred, env('TENCENT_SES_REGION'));
    }

    /**
     * Send email using Tencent SES template
     */
    public function sendEmailWithTemplate(string $to, string $templateId, array $templateData, string $subject = null): bool
    {
        try {
            $req = new SendEmailRequest();
            
            // Convert template ID to integer
            $templateId = (int)$templateId;

            // Required parameters
            $req->FromEmailAddress = env('TENCENT_SES_SENDER');
            $req->Destination = [$to];
            
            // Template configuration
            $template = new Template();
            $template->TemplateID = $templateId;

            // Process template data
            $processedData = [
                'kanji' => $templateData['kanji'] ?? '',
                'hiragana' => $templateData['hiragana'] ?? '',
                'romaji' => $templateData['romaji'] ?? '',
                'breakdown' => str_replace("\n", "<br>", $templateData['breakdown'] ?? ''),
                'grammar' => str_replace("\n", "<br>", $templateData['grammar'] ?? '')
            ];
            
            $template->TemplateData = json_encode($processedData, JSON_UNESCAPED_UNICODE);
            $req->Template = $template;

            // Generate a unique subject each time to avoid threading issues
            $uniqueSubject = $subject . ' ' . date('m-d-Y'); // Format as Month-Day-Year (e.g., 03-31-2025)

            // Optional subject with unique identifier
            $req->Subject = $uniqueSubject;

            // Send the email with the template
            $response = $this->client->SendEmail($req);

            Log::info('Email sent successfully', [
                'to' => $to,
                'template_id' => $templateId,
                'response' => $response->toJsonString()
            ]);

            return true;

        } catch (TencentCloudSDKException $e) {
            Log::error('Tencent SES failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'request' => [
                    'to' => $to,
                    'template_id' => $templateId,
                    'template_data' => $templateData
                ]
            ]);
            return false;
        }
    }


    /**
     * Alternative method to send raw HTML email
     */
    public function sendHtmlEmail(string $to, string $subject, string $htmlContent): bool
    {
        try {
            $req = new SendEmailRequest();
            $req->FromEmailAddress = env('TENCENT_SES_SENDER');
            $req->Destination = [$to];
            
            $simple = new \TencentCloud\Ses\V20201002\Models\Simple();
            $simple->Subject = $subject;
            $simple->Html = $htmlContent;
            
            $req->Simple = $simple;

            $response = $this->client->SendEmail($req);
            return true;

        } catch (TencentCloudSDKException $e) {
            Log::error('HTML email failed', [
                'error' => $e->getMessage(),
                'to' => $to,
                'subject' => $subject
            ]);
            return false;
        }
    }
}


<?php

namespace App\Services;

use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Ses\V20201002\SesClient;
use TencentCloud\Ses\V20201002\Models\SendEmailRequest;
use TencentCloud\Ses\V20201002\Models\Template;
use Illuminate\Support\Facades\Log;

class TencentSesService
{
    protected $client;

    public function __construct()
    {
        $cred = new Credential(
            env('TENCENT_SECRET_ID'),
            env('TENCENT_SECRET_KEY')
        );

        $this->client = new SesClient($cred, env('TENCENT_SES_REGION', 'ap-hongkong'));
    }

    public function sendEmailWithTemplate(
        string $to, 
        int $templateId, 
        array $templateData, 
        string $subject = null
    ): bool {
        try {
            $req = new SendEmailRequest();
            
            // 1. Validate and prepare sender address
            $sender = env('TENCENT_SES_SENDER');
            if (empty($sender)) {
                throw new \RuntimeException('Sender email not configured');
            }
            $req->FromEmailAddress = $sender;
            
            // 2. Validate recipient
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid recipient email: {$to}");
            }
            $req->Destination = [$to];
            
            // 3. Prepare template with validation
            $template = new Template();
            $template->TemplateID = $templateId;
            
            // Process template data
            $processedData = $this->processTemplateData($templateData);
            $template->TemplateData = json_encode($processedData, JSON_UNESCAPED_UNICODE);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Template data JSON encoding failed: ' . json_last_error_msg());
            }
            
            $req->Template = $template;

            // 4. Handle subject with date
            if ($subject) {
                $req->Subject = "{$subject} " . date('m-d-Y');
            }

            // 5. Send with timeout protection
            $response = $this->client->SendEmail($req, ['timeout' => 10]);
            
            Log::info('Email sent successfully', [
                'to' => $to,
                'template_id' => $templateId,
                'message_id' => $response->MessageId ?? 'unknown'
            ]);
            
            return true;

        } catch (TencentCloudSDKException $e) {
            Log::error('Tencent SES API error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function processTemplateData(array $data): array
    {
        return [
            'kanji' => $data['kanji'] ?? '',
            'hiragana' => $data['hiragana'] ?? '',
            'romaji' => $data['romaji'] ?? '',
            'breakdown' => str_replace(["\n", "\\n"], '<br>', $data['breakdown'] ?? ''),
            'grammar' => str_replace(["\n", "\\n"], '<br>', $data['grammar'] ?? '')
        ];
    }
}