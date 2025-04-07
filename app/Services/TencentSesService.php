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

            $sender = env('TENCENT_SES_SENDER');
            if (empty($sender)) {
                throw new \RuntimeException('Sender email not configured');
            }
            $req->FromEmailAddress = $sender;

            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid recipient email: {$to}");
            }
            $req->Destination = [$to];

            $template = new Template();
            $template->TemplateID = $templateId;

            $processedData = $this->processTemplateData($templateData);
            $template->TemplateData = json_encode($processedData, JSON_UNESCAPED_UNICODE);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Template data JSON encoding failed: ' . json_last_error_msg());
            }

            $req->Template = $template;

            if ($subject) {
                $req->Subject = $subject;
            }

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
        $formatted = [];

        foreach ($data as $key => $value) {
            if (in_array($key, ['breakdown', 'grammar']) && is_string($value)) {
                $formatted[$key] = str_replace(["\n", "\\n"], '<br>', $value);
            } else {
                $formatted[$key] = $value;
            }
        }

        return $formatted;
    }
}
