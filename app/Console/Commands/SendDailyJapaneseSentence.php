<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\JapaneseSentenceService;
use App\Services\TencentSesService;
use Illuminate\Support\Facades\View;

class SendDailyJapaneseSentence extends Command
{
    protected $signature = 'send:daily-japanese';
    protected $description = 'Send daily Japanese sentences to subscribed users';

    protected $sentenceService;
    protected $ses;

    public function __construct(JapaneseSentenceService $sentenceService, TencentSesService $ses)
    {
        parent::__construct();
        $this->sentenceService = $sentenceService;
        $this->ses = $ses;
    }

    public function handle()
    {
        $users = User::where('is_subscribed', true)->cursor();

        if ($users->isEmpty()) {
            $this->info('No subscribed users found.');
            return;
        }

        try {
            $sentence = $this->sentenceService->generateSentence();

            $templateData = [
                'kanji' => $sentence['kanji'],
                'hiragana' => $sentence['hiragana'],
                'romaji' => $sentence['romaji'],
                'breakdown' => $sentence['breakdown'],
                'grammar' => $sentence['grammar']
            ];

            foreach ($users as $user) {
                $success = $this->ses->sendEmailWithTemplate(
                    $user->email,
                    '65669',  // Replace with your actual template ID from Tencent Cloud
                    $templateData
                );

                if ($success) {
                    $this->info("Sent to {$user->email}");
                } else {
                    $this->error("Failed to send to {$user->email}");
                }
            }
        } catch (\Exception $e) {
            $this->error("Failed to send emails: " . $e->getMessage());
        }
    }

}
