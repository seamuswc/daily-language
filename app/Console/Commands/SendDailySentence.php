<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\DailySentenceService;
use App\Services\TencentSesService;
use Illuminate\Support\Facades\Log;

class SendDailySentence extends Command
{
    protected $signature = 'send:daily-sentence';
    protected $description = 'Send daily sentence based on .env SOURCE_LANGUAGE and TARGET_LANGUAGE';

    public function handle()
    {
        $sourceLanguage = env('SOURCE_LANGUAGE');
        $targetLanguage = env('TARGET_LANGUAGE');

        Log::info('SendDailySentence started', [
            'source' => $sourceLanguage,
            'target' => $targetLanguage,
        ]);

        $users = User::where('is_subscribed', true)
            ->where('language', $targetLanguage)
            ->cursor();

        $sentenceService = app(DailySentenceService::class);
        $sesService = app(TencentSesService::class);

        if ($users->isEmpty()) {
            $this->info('No subscribed users found.');
            Log::info('SendDailySentence: no subscribed users');
            return;
        }

        try {
            $sentence = $sentenceService->generateSentence($sourceLanguage, $targetLanguage);
            Log::info('SendDailySentence: sentence generated');

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
                Log::info('SendDailySentence: user processed', [
                    'email' => $user->email,
                    'status' => $success ? 'sent' : 'failed'
                ]);
            }

            Log::info('SendDailySentence completed');
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            logger()->error('Daily sentence send failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Log::error('SendDailySentence exception', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
