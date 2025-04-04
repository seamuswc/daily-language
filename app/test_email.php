<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\JapaneseSentenceService;
use App\Services\TencentSesService;
use Illuminate\Support\Facades\Log;

$sentenceService = app(JapaneseSentenceService::class);
$sesService = app(TencentSesService::class);

$sentence = [
    'kanji' => '今日は良い天気ですね。',
    'hiragana' => 'きょうは いい てんき ですね。',
    'romaji' => 'Kyō wa ii tenki desu ne.',
    'breakdown' => '今日 (kyō) - today\nは (wa) - topic marker\n良い (ii) - good\n天気 (tenki) - weather\nです (desu) - copula\nね (ne) - particle for agreement',
    'grammar' => '〜ですね is a common sentence-ending pattern used to seek agreement. です is the polite copula, and ね adds a sense of shared understanding.'
];

// Prepare the template data for the email
$templateData = [
    'kanji' => $sentence['kanji'],
    'hiragana' => $sentence['hiragana'],
    'romaji' => $sentence['romaji'],
    'breakdown' => str_replace("\\n", "\n", $sentence['breakdown']), // Fix escaped newlines
    'grammar' => str_replace("\\n", "\n", $sentence['grammar'])
];



$userEmail = 'seamuswconnolly@gmail.com';

// Your SES template ID
$templateId = 65685; // Replace with your actual template ID

$subject = '今日の日本語 ' . date('m-d-Y'); 

try {
    // Send the email using Tencent SES with the template
    $success = $sesService->sendEmailWithTemplate(
        $userEmail,
        $templateId,
        $templateData,
        $subject
    );

    if ($success) {
        echo "Email sent successfully!";
    } else {
        echo "Failed to send email.";
    }

} catch (\Exception $e) {
    // Log any errors that occur
    Log::error('Failed to send email: ' . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
