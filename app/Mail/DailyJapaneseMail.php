<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyJapaneseMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sentence;

    public function __construct(array $sentence)
    {
        $this->sentence = $sentence;
    }

    public function build()
    {
        return $this->subject('今日の日本語 (Today\'s Japanese)')
            ->view('emails.daily_japanese');
    }
}