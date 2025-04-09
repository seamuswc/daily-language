<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class DailySentenceService
{

    protected function fetchFromDeepSeek(string $sourceLanguage, string $targetLanguage): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('DEEPSEEK_API_KEY'),
                'Content-Type'  => 'application/json',
            ])->timeout(15)->retry(3, 100)->post('https://api.deepseek.com/v1/chat/completions', [
                'model'    => 'deepseek-chat',
                'messages' => [
                    [
                        'role'    => 'user',
                        'content' => $this->getPrompt($sourceLanguage, $targetLanguage)
                    ]
                ]
            ]);

            if ($response->successful()) {
                return $this->parseResponse($response->json());
            }

            Log::error('DeepSeek API Error', [
                'status'   => $response->status(),
                'response' => $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error('DeepSeek API Exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
        }

        return null;
    }

    public function generateSentence(string $sourceLanguage, string $targetLanguage): array
    {
        $cacheKey = "daily_sentence_{$sourceLanguage}_to_{$targetLanguage}";

        // First, check if cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Try getting from API
        $sentence = $this->fetchFromDeepSeek($sourceLanguage, $targetLanguage);

        if ($sentence !== null) {
            // Cache only valid API result
            Cache::put($cacheKey, $sentence, now()->addHours(12));
            return $sentence;
        }

        // Otherwise, return fallback without caching
        return $this->getFallbackSentence($sourceLanguage, $targetLanguage);
    }


    protected function getPrompt(string $source, string $target): string
    {
        $path = resource_path("prompts/{$source}_{$target}.txt");

        if (File::exists($path)) {
            Log::info('Using Prompt.', ['source' => $source, 'target' => $target]);
            return File::get($path);
        }

        Log::warning('Prompt file not found. Using default prompt.', ['source' => $source, 'target' => $target]);

        return "Generate a new intermediate-level Japanese sentence, use grammatical concepts from N1-N3, with this exact structure:\n\n" .
               "漢字: [sentence]\n" .
               "ひらがな: [reading]\n" .
               "Romaji: [romaji]\n" .
               "Breakdown: [word-by-word]\n" .
               "Grammar: [explanation]\n" .
               "Meaning: [english]\n\n" .
               "Example:\n" .
               "漢字: 先生が説明を簡潔にまとめた。\n" .
               "ひらがな: せんせいが せつめいを かんけつに まとめた。\n" .
               "Romaji: Sensei ga setsumei o kanketsu ni matometa.\n" .
               "Breakdown: 先生（せんせい）（teacher） + が（が）（subject） + 説明（せつめい）（explanation） + を（を）（object） + 簡潔に（かんけつに）（concisely） + まとめた（まとめた）（summarized）\n" .
               "Grammar: ～にまとめた = 'summarized into...'\n" .
               "Meaning: The teacher summarized the explanation concisely\n" .
               "new line for each word\n" .
               "new line for each grammar\n" .
               "Breakdown and Grammar formatting should be the same. Each grammar doesn't need a new 'Grammar:' in front. 'Grammar:' is only allowed one time!\n" .
               "Also please don't add any questions or unnecessary commentary\n" .
               "Remember, use grammatical structures that an adult would need, not too simple";
    }

    protected function parseResponse(array $data): array
    {
        $content = $data['choices'][0]['message']['content'] ?? '';
        Log::info('DeepSeek Raw Response', ['content' => $content]);
    
        $content = preg_replace('/\*\*(.*?)\*\*/', '$1', $content);
    
        $sourceTarget = strtolower(env('SOURCE_LANGUAGE') . '_' . env('TARGET_LANGUAGE'));
    
        switch ($sourceTarget) {
            case 'japanese_english':
                if (preg_match('/^English:\s*(.*?)\n読み方:\s*(.*?)\nWord Breakdown:\s*(.*?)\nGrammar:\s*(.*?)\nMeaning:\s*(.*)/s', $content, $matches)) {
                    return [
                        'kanji'     => $matches[1], // English sentence
                        'hiragana'  => $matches[2], // Katakana reading
                        'romaji'    => '',          // Optional
                        'breakdown' => trim($matches[3]),
                        'grammar'   => trim($matches[4]),
                        'meaning'   => trim($matches[5]),
                    ];
                }
                break;
    
            case 'english_japanese':
            default:
                if (preg_match('/漢字:\s*(.*?)\nひらがな:\s*(.*?)\nRomaji:\s*(.*?)\nBreakdown:\s*(.*?)\nGrammar:\s*(.*?)\nMeaning:\s*(.*)/s', $content, $matches)) {
                    return array_map('trim', [
                        'kanji'     => $matches[1],
                        'hiragana'  => $matches[2],
                        'romaji'    => $matches[3],
                        'breakdown' => $matches[4],
                        'grammar'   => $matches[5],
                        'meaning'   => $matches[6]
                    ]);
                }
                break;
        }
    
        Log::error('Failed to parse API response (regex mismatch)', ['content' => $content]);
        return $this->getFallbackSentence(
            env('SOURCE_LANGUAGE'),
            env('TARGET_LANGUAGE')
        );
    }
    

    protected function getFallbackSentence(string $source, string $target): array
    {
        Log::info('Using hardcoded fallback sentence.', ['source' => $source, 'target' => $target]);

        return [
            'kanji'     => '今日は雨が降っています',
            'hiragana'  => 'きょうは あめが ふっています',
            'romaji'    => 'Kyō wa ame ga futte imasu',
            'breakdown' => '今日 (today) + は (topic) + 雨 (rain) + が (subject) + 降っています (is falling)',
            'grammar'   => '〜ています = ongoing action',
            'meaning'   => 'It is raining today'
        ];
    }
}
