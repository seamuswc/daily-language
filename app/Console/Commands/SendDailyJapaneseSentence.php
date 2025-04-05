<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\JapaneseSentenceService;
use App\Services\TencentSesService;

class SendDailyJapaneseSentence extends Command
{
    protected $signature = 'send:daily-japanese';
    protected $description = 'Send daily Japanese sentences to subscribed users';

    public function handle()
    {
        $language = env('SITE_LANGUAGE');
        $users = User::where('is_subscribed', true)
        ->where('language', $language)
        ->cursor();
        $sentenceService = app(JapaneseSentenceService::class);
        $sesService = app(TencentSesService::class);

        if ($users->isEmpty()) {
            $this->info('No subscribed users found.');
            return;
        }

        try {
            $sentence = $sentenceService->generateSentence();

            $templateData = [
                'kanji' => $sentence['kanji'],
                'hiragana' => $sentence['hiragana'],
                'romaji' => $sentence['romaji'],
                'breakdown' => $sentence['breakdown'],
                'grammar' => $sentence['grammar']
            ];

            $templateId = (int) env('TENCENT_SES_TEMPLATE_ID');
            $subject = '今日の日本語 ' . date('m-d-Y');

            foreach ($users as $user) {
                $success = $sesService->sendEmailWithTemplate(
                    $user->email,
                    $templateId,
                    $templateData,
                    $subject
                );

                if ($success) {
                    $this->info("Sent to {$user->email}");
                } else {
                    $this->error("Failed to send to {$user->email}");
                }
            }

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            logger()->error('Daily Japanese send failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
