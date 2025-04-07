<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\DailySentenceService;
use App\Services\TencentSesService;
use Illuminate\Support\Facades\Log;

$sentenceService = app(DailySentenceService::class);
$sesService = app(TencentSesService::class);

// Prompt user
$input = readline("Choose language (j = Japanese, e = English): ");
$input = strtolower(trim($input));

// Set template and sentence based on language
switch ($input) {
    case 'j':
        $templateId = 65685;
        $sentence = [
            'kanji' => '今日は良い天気ですね。',
            'hiragana' => 'きょうは いい てんき ですね。',
            'romaji' => 'Kyō wa ii tenki desu ne.',
            'breakdown' => '今日 (kyō) - today\nは (wa) - topic marker\n良い (ii) - good\n天気 (tenki) - weather\nです (desu) - copula\nね (ne) - particle for agreement',
            'grammar' => '〜ですね is a common sentence-ending pattern used to seek agreement. です is the polite copula, and ね adds a sense of shared understanding.'
        ];
        $templateData = [
            'kanji' => $sentence['kanji'],
            'hiragana' => $sentence['hiragana'],
            'romaji' => $sentence['romaji'],
            'breakdown' => str_replace("\\n", "\n", $sentence['breakdown']),
            'grammar' => str_replace("\\n", "\n", $sentence['grammar'])
        ];
        break;

    case 'e':
        $templateId = 65687;
        $sentence = [
            'english' => 'It’s a beautiful day today.',
            'katakana' => 'イッツ・ア・ビューティフル・デイ・トゥデイ',
            'pronunciation' => 'Itsu a byūtifuru dei tsudei',
            'breakdown' => 'It’s - it is\nbeautiful - 美しい (utsukushii)\nday - 日 (hi)\ntoday - 今日 (kyou)',
            'grammar' => '“It’s a …” is a common structure for describing something. The verb “is” is contracted to “’s”.'
        ];
        $templateData = [
            'english' => $sentence['english'],
            'katakana' => $sentence['katakana'],
            'pronunciation' => $sentence['pronunciation'],
            'breakdown' => str_replace("\\n", "\n", $sentence['breakdown']),
            'grammar' => str_replace("\\n", "\n", $sentence['grammar'])
        ];
        break;

    default:
        echo "Invalid input. Use 'j' or 'e'.\n";
        exit(1);
}

// Send email
$userEmail = 'seamuswconnolly@gmail.com';
$subject = ($input === 'j' ? '今日の日本語 ' : '今日の英語 ') . date('m-d-Y');

try {
    $success = $sesService->sendEmailWithTemplate(
        $userEmail,
        $templateId,
        $templateData,
        $subject
    );

    echo $success ? "✅ Email sent successfully!\n" : "❌ Failed to send email.\n";

} catch (\Exception $e) {
    Log::error('Failed to send email: ' . $e->getMessage());
    echo "❌ Error: " . $e->getMessage() . "\n";
}
