<?php

namespace App\Services;

use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Ses\V20201002\SesClient;
use TencentCloud\Ses\V20201002\Models\SendEmailRequest;
use TencentCloud\Ses\V20201002\Models\Simple;
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

        $region = env('TENCENT_SES_REGION');

        $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
        $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
        $httpProfile->setEndpoint("ses.tencentcloudapi.com");
        $clientProfile->setHttpProfile($httpProfile);

        $this->client = new SesClient($cred, $region, $clientProfile);
    }


    public function sendEmailWithTemplate(string $to, string $templateId, array $templateData)
    {
        try {
            $req = new SendEmailRequest();

            $req->FromEmailAddress = env('TENCENT_SES_SENDER');
            $req->Destination = [$to];
            $req->TemplateId = $templateId; // Use the template ID created in Tencent Cloud SES

            // Pass dynamic template data as JSON (this will replace placeholders in the template)
            $req->TemplateData = json_encode($templateData);

            $response = $this->client->SendEmail($req);
            Log::info('Tencent SES email sent successfully using template.', ['response' => $response->toJsonString()]);
            return true;

        } catch (TencentCloudSDKException $e) {
            Log::error('Tencent SES error: ' . $e->getMessage());
            return false;
        }
    }


}
