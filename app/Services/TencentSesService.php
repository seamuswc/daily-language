<?php

namespace App\Services;

use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Ses\V20201002\SesClient;
use TencentCloud\Ses\V20201002\Models\SendEmailRequest;
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
    
        $region = env('TENCENT_SES_REGION', 'ap-hongkong');
    
        $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
        $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
        $httpProfile->setEndpoint("ses.tencentcloudapi.com");
        $clientProfile->setHttpProfile($httpProfile);
    
        $this->client = new SesClient($cred, $region, $clientProfile);
    }
    

    public function sendEmail(string $to, string $subject, string $htmlBody, string $textBody = '')
    {
        try {
            $req = new SendEmailRequest();

            $req->FromEmailAddress = env('TENCENT_SES_SENDER');
            $req->Destination = [$to];
            $req->Subject = $subject;
            $req->ReplyToAddresses = [env('TENCENT_SES_SENDER')];

            $req->Simple = [
                "Html" => ["Data" => $htmlBody],
                "Text" => ["Data" => $textBody ?: strip_tags($htmlBody)],
            ];

            $response = $this->client->SendEmail($req);
            Log::info('Tencent SES email sent successfully.', ['response' => $response->toJsonString()]);
            return true;

        } catch (TencentCloudSDKException $e) {
            Log::error('Tencent SES error: ' . $e->getMessage());
            return false;
        }
    }
}
