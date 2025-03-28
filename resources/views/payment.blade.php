<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Japanese Learning Service</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .japanese-font {
            font-family: 'Noto Sans JP', sans-serif;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-12">
        <!-- Payment Form -->
        <!-- Replace the payment form section with this -->
            <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
                <div class="p-8">
                    <div class="text-center mb-8">
                        <h1 class="text-2xl font-bold text-gray-800">Japanese Sentence Subscription</h1>
                        <p class="text-gray-600 mt-2">Choose your subscription plan</p>
                    </div>
                    
                    <form action="{{ route('payment.process') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                            <input type="email" id="email" name="email" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                placeholder="your@email.com">
                        </div>
                        
                        <!-- Subscription Options -->
                        <div class="mb-6 space-y-4">
                            <div class="flex items-center">
                                <input id="monthly" name="plan" type="radio" value="monthly" checked 
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                                <label for="monthly" class="ml-3 block text-gray-700">
                                    <span class="font-medium">Monthly Plan</span>
                                    <span class="text-gray-600"> - $2.00 (30 days)</span>
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input id="yearly" name="plan" type="radio" value="yearly"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                                <label for="yearly" class="ml-3 block text-gray-700">
                                    <span class="font-medium">Yearly Plan</span>
                                    <span class="text-gray-600"> - $12.00 (1 year, save 50%)</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" 
                                class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline">
                                Continue to Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        <!-- Japanese Sentence Display -->
        <div class="mt-8 max-w-2xl mx-auto bg-white p-8 rounded-xl shadow-lg">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <span class="mr-2">今日の日本語</span>
                <span class="text-lg text-gray-500">(Today's Japanese)</span>
            </h2>
            
            <div class="space-y-6">
                <!-- Kanji -->
                <div class="border-b border-gray-200 pb-4">
                    <p class="font-semibold text-gray-700 mb-2">漢字 (Kanji):</p>
                    <p class="text-2xl japanese-font">{{ $sentence['kanji'] ?? '今日は良い天気ですね。' }}</p>
                </div>
                
                <!-- Hiragana -->
                <div class="border-b border-gray-200 pb-4">
                    <p class="font-semibold text-gray-700 mb-2">ひらがな (Hiragana):</p>
                    <p class="text-xl japanese-font">{{ $sentence['hiragana'] ?? 'きょうは いい てんき ですね。' }}</p>
                </div>
                
                <!-- Romaji -->
                <div class="border-b border-gray-200 pb-4">
                    <p class="font-semibold text-gray-700 mb-2">Romaji:</p>
                    <p class="text-lg italic text-gray-600">{{ $sentence['romaji'] ?? 'Kyō wa ii tenki desu ne.' }}</p>
                </div>
                
                <!-- Meaning -->
                <div class="border-b border-gray-200 pb-4">
                    <p class="font-semibold text-gray-700 mb-2">Meaning:</p>
                    <p class="text-gray-700">{{ $sentence['meaning'] ?? 'The weather is nice today, isn\'t it?' }}</p>
                </div>
                
                <!-- Breakdown -->
                <div class="border-b border-gray-200 pb-4">
                    <p class="font-semibold text-gray-700 mb-2">Word Breakdown:</p>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="whitespace-pre-line">{{ $sentence['breakdown'] ?? "今日 (kyō) - today\nは (wa) - topic marker\n良い (ii) - good\n天気 (tenki) - weather\nです (desu) - copula\nね (ne) - particle for agreement" }}</p>
                    </div>
                </div>
                
                <!-- Grammar -->
                <div class="border-b border-gray-200 pb-4">
                    <p class="font-semibold text-gray-700 mb-2">Grammar Explanation:</p>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="whitespace-pre-line">{{ $sentence['grammar'] ?? '〜ですね is a common sentence-ending pattern used to seek agreement. です is the polite copula, and ね adds a sense of shared understanding.' }}</p>
                    </div>
                </div>
                
            </div>
            
            <!-- Refresh Button -->
            <div class="mt-8 text-center">
                <button onclick="window.location.reload()" class="bg-blue-100 text-blue-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-200 transition">
                    ↻ Generate New Sentence
                </button>
            </div>
        </div>
    </div>

    <!-- Auto-refresh after 24 hours -->
    <script>
        setTimeout(() => {
            window.location.reload();
        }, 24 * 60 * 60 * 1000);
    </script>
</body>
</html>