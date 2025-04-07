<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\DailySentenceService;
use App\Services\TencentSesService;

class SendDailySentence extends Command
{
    protected $signature = 'send:daily-sentence';
    protected $description = 'Send daily sentence based on .env SOURCE_LANGUAGE and TARGET_LANGUAGE';

    public function handle()
    {
        $sourceLanguage = env('SOURCE_LANGUAGE');
        $targetLanguage = env('TARGET_LANGUAGE');

        $users = User::where('is_subscribed', true)
            ->where('language', $targetLanguage)
            ->cursor();

        $sentenceService = app(DailySentenceService::class);
        $sesService = app(TencentSesService::class);

        if ($users->isEmpty()) {
            $this->info('No subscribed users found.');
            return;
        }

        try {
            $sentence = $sentenceService->generateSentence($sourceLanguage, $targetLanguage);

            $templateData = $sentence;
            $templateId = (int) env('TENCENT_SES_TEMPLATE_ID');
            $subject = __('ui.sentence_today') . ' ' . date('m-d-Y');

            foreach ($users as $user) {
                $success = $sesService->sendEmailWithTemplate(
                    $user->email,
                    $templateId,
                    $templateData,
                    $subject
                );

                $this->{$success ? 'info' : 'error'}("{$user->email} => " . ($success ? 'sent' : 'failed'));
            }

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            logger()->error('Daily sentence send failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
