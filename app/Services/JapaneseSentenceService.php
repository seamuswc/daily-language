<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class JapaneseSentenceService
{
    public function generateSentence(): array
    {
        return Cache::remember('deepseek_n3_japanese_sentence', now()->addHours(12), function () {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('DEEPSEEK_API_KEY'),
                    'Content-Type' => 'application/json',
                ])->timeout(15)->retry(3, 100)->post('https://api.deepseek.com/v1/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $this->getPrompt()
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    return $this->parseResponse($response->json());
                }

                Log::error('DeepSeek API Error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

            } catch (\Exception $e) {
                Log::error('DeepSeek API Exception: ' . $e->getMessage());
            }

            return $this->getFallbackSentence();
        });
    }


    protected function getPrompt(): string
    {
        return "Generate a new intermediate-level (N3) Japanese sentence with this exact structure:\n\n" .
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
               "Breakdown: 先生 (teacher) + が (subject) + 説明 (explanation) + を (object) + 簡潔に (concisely) + まとめた (summarized)\n" .
               "Grammar: ～にまとめた = 'summarized into...'\n" .
               "Meaning: The teacher summarized the explanation concisely\n" .
               "new line for each word\n" .
               "new line for each grammar.\n" . 
               "Breakdown and Grammar formatting should be the same. Each grammar doesnt need a new 'Grammar:' in front. 'Grammar:' is only allowed one time!\n" . 
               "Also please dont add any questions or unnecesarry commentary\n" .
                "if, give, recieve, should, be sure to use these and other useful grammtical constructs"               
               ;
    }

    protected function parseResponse(array $data): array
    {
        $content = $data['choices'][0]['message']['content'] ?? '';
        Log::info('DeepSeek Raw Response', ['content' => $content]);
    
        // Strip markdown bold if present
        $content = preg_replace('/\*\*(.*?)\*\*/', '$1', $content);
    
        if (preg_match('/漢字:\s*(.*?)\nひらがな:\s*(.*?)\nRomaji:\s*(.*?)\nBreakdown:\s*(.*?)\nGrammar:\s*(.*?)\nMeaning:\s*(.*)/s', $content, $matches)) {
            return array_map('trim', [
                'kanji' => $matches[1],
                'hiragana' => $matches[2],
                'romaji' => $matches[3],
                'breakdown' => $matches[4],
                'grammar' => $matches[5],
                'meaning' => $matches[6]
            ]);
        }
    
        Log::error('Failed to parse API response (regex mismatch)', ['content' => $content]);
        return $this->getFallbackSentence();
    }
    

    protected function getFallbackSentence(): array
    {
        return [
            'kanji' => '今日は雨が降っています',
            'hiragana' => 'きょうは あめが ふっています',
            'romaji' => 'Kyō wa ame ga futte imasu',
            'breakdown' => '今日 (today) + は (topic) + 雨 (rain) + が (subject) + 降っています (is falling)',
            'grammar' => '〜ています = ongoing action',
            'meaning' => 'It is raining today'
        ];
    }
}