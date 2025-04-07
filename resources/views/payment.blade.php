<!-- resources/views/payment.blade.php -->
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('ui.title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-12">
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
        <div class="p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">{{ __('ui.subscribe_header') }}</h1>
                <p class="text-gray-600 mt-2">{{ __('ui.subscribe_text') }}</p>
            </div>

            @if ($user && $remainingDays > 0)
                <p class="text-green-600 font-medium mb-4">
                    {{ __('ui.already_subscribed', ['days' => $remainingDays]) }}
                </p>
            @elseif ($user && $remainingDays <= 0)
                <p class="text-red-600 font-medium mb-4">
                    {{ __('ui.subscription_expired', ['days' => abs($remainingDays)]) }}
                </p>
            @endif

            <form action="{{ route('payment.process') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">{{ __('ui.email') }}</label>
                    <input type="email" id="email" name="email" required
                           value="{{ request('email') }}"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           placeholder="your@email.com">
                </div>

                <div class="mb-6 space-y-4">
                    <div class="flex items-center">
                        <input id="monthly" name="plan" type="radio" value="monthly" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                        <label for="monthly" class="ml-3 block text-gray-700">
                            <span class="font-medium">{{ __('ui.monthly') }}</span>
                            <span class="text-gray-600"> - $2.00 (30 days)</span>
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input id="yearly" name="plan" type="radio" value="yearly"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                        <label for="yearly" class="ml-3 block text-gray-700">
                            <span class="font-medium">{{ __('ui.yearly') }}</span>
                            <span class="text-gray-600"> - $12.00 (1 year)</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit"
                            class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline">
                        {{ __('ui.continue') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-8 max-w-2xl mx-auto bg-white p-8 rounded-xl shadow-lg">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <span class="mr-2">{{ __('ui.sentence_today') }}</span>
        </h2>

        <div class="space-y-6">
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-700 mb-2">{{ __('ui.kanji') }}</p>
                <p class="text-2xl">{{ $sentence['kanji'] ?? '' }}</p>
            </div>
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-700 mb-2">{{ __('ui.hiragana') }}</p>
                <p class="text-xl">{{ $sentence['hiragana'] ?? '' }}</p>
            </div>
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-700 mb-2">{{ __('ui.romaji') }}</p>
                <p class="text-lg italic text-gray-600">{{ $sentence['romaji'] ?? '' }}</p>
            </div>
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-700 mb-2">{{ __('ui.meaning') }}</p>
                <p class="text-gray-700">{{ $sentence['meaning'] ?? '' }}</p>
            </div>
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-700 mb-2">{{ __('ui.breakdown') }}</p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="whitespace-pre-line">{{ $sentence['breakdown'] ?? '' }}</p>
                </div>
            </div>
            <div class="border-b border-gray-200 pb-4">
                <p class="font-semibold text-gray-700 mb-2">{{ __('ui.grammar') }}</p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="whitespace-pre-line">{{ $sentence['grammar'] ?? '' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
