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

// CLI args: --lang=j|e, --to=email
$lang = 'j';
$to = getenv('MAIL_TEST_TO') ?: '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--lang=')) {
        $lang = strtolower(substr($arg, 7));
    } elseif (str_starts_with($arg, '--to=')) {
        $to = substr($arg, 5);
    }
}

if (!in_array($lang, ['j', 'e'], true)) {
    fwrite(STDERR, "Usage: php app/test_email.php --lang=j|e [--to=email]\n");
    exit(1);
}

if (!$to) {
    fwrite(STDOUT, "No --to provided; set MAIL_TEST_TO in env or pass --to=you@example.com.\n");
    exit(1);
}

// Compose template data
if ($lang === 'j') {
    $templateId = (int) (getenv('TENCENT_SES_TEMPLATE_ID') ?: 65685);
    $sentence = [
        'kanji' => '今日は良い天気ですね。',
        'hiragana' => 'きょうは いい てんき ですね。',
        'romaji' => 'Kyō wa ii tenki desu ne.',
        'breakdown' => "今日 (kyō) - today\nは (wa) - topic marker\n良い (ii) - good\n天気 (tenki) - weather\nです (desu) - copula\nね (ne) - particle for agreement",
        'grammar' => '〜ですね is a common sentence-ending pattern used to seek agreement. です is the polite copula, and ね adds a sense of shared understanding.'
    ];
    $templateData = [
        'kanji' => $sentence['kanji'],
        'hiragana' => $sentence['hiragana'],
        'romaji' => $sentence['romaji'],
        'breakdown' => $sentence['breakdown'],
        'grammar' => $sentence['grammar']
    ];
    $subject = '今日の日本語 ' . date('m-d-Y');
} else {
    $templateId = (int) (getenv('TENCENT_SES_TEMPLATE_ID_EN') ?: 65687);
    $sentence = [
        'english' => 'It’s a beautiful day today.',
        'katakana' => 'イッツ・ア・ビューティフル・デイ・トゥデイ',
        'pronunciation' => 'Itsu a byūtifuru dei tsudei',
        'breakdown' => "It’s - it is\nbeautiful - 美しい (utsukushii)\nday - 日 (hi)\ntoday - 今日 (kyou)",
        'grammar' => '“It’s a …” is a common structure for describing something. The verb “is” is contracted to “’s”.'
    ];
    $templateData = [
        'english' => $sentence['english'],
        'katakana' => $sentence['katakana'],
        'pronunciation' => $sentence['pronunciation'],
        'breakdown' => $sentence['breakdown'],
        'grammar' => $sentence['grammar']
    ];
    $subject = '今日の英語 ' . date('m-d-Y');
}

try {
    $success = $sesService->sendEmailWithTemplate(
        $to,
        $templateId,
        $templateData,
        $subject
    );
    echo $success ? "✅ Email sent successfully to {$to}!\n" : "❌ Failed to send email.\n";
} catch (\Throwable $e) {
    Log::error('Failed to send email', ['error' => $e->getMessage()]);
    fwrite(STDERR, "❌ Error: " . $e->getMessage() . "\n");
    exit(1);
}
