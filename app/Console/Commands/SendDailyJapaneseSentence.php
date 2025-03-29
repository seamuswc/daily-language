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
            $html = View::make('emails.daily_japanese', ['sentence' => $sentence])->render();

            foreach ($users as $user) {
                $success = $this->ses->send(
                    $user->email,
                    '今日の日本語 (Today\'s Japanese)',
                    $html
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
