
<?php

// Usage script
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TencentSesService;
use App\Services\JapaneseSentenceService;
use Illuminate\Support\Facades\Log;

$sentenceService = app(JapaneseSentenceService::class);
$sesService = app(TencentSesService::class);

$sentence = [
    'kanji' => '天気',
    'hiragana' => 'てんき',
    'romaji' => 'tenki',
    'breakdown' => '天気 (tenki) - weather',
    'grammar' => 'です (desu) - copula'
];

$recipients = [
    'seamuswconnolly@gmail.com',
    //'second@example.com'
];

$templateId = 65685;
$subject = '今日の日本語 ' . date('m-d-Y');

foreach ($recipients as $email) {
    $success = $sesService->sendEmailWithTemplate(
        $email,
        $templateId,
        $sentence,
        $subject
    );

    echo $email . ': ' . ($success ? '✅ Sent' : '❌ Failed') . PHP_EOL;
}