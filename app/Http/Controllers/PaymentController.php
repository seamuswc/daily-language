<?php

namespace App\Http\Controllers;

use App\Services\CoinbaseCommerceService;
use App\Services\DailySentenceService;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\View;

class PaymentController extends Controller
{
    public function __construct(
        protected CoinbaseCommerceService $coinbaseService,
        protected DailySentenceService $sentenceService
    ) {}

    public function showPaymentForm(Request $request)
    {
        $sourceLanguage = env('SOURCE_LANGUAGE');
        $targetLanguage = env('TARGET_LANGUAGE');

        $sentence = $this->sentenceService->generateSentence($sourceLanguage, $targetLanguage);

        $user = null;
        $remainingDays = null;

        if ($request->has('email')) {
            $user = User::where('email', $request->email)
                        ->where('language', $targetLanguage)
                        ->first();

            if ($user && $user->is_subscribed && $user->subscription_expires_at) {
                $remainingDays = now()->diffInDays($user->subscription_expires_at, false);
            }
        }

        return view('payment', compact('sentence', 'user', 'remainingDays'));
    }

    public function processPayment(Request $request)
    {
        return back()->with('error', 'Card/crypto checkout is disabled. Use Aptos or Solana buttons.');
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-CC-Webhook-Signature');
        $webhookSecret = env('COINBASE_COMMERCE_WEBHOOK_SECRET');

        if ($this->verifySignature($payload, $signature, $webhookSecret)) {
            $event = json_decode($payload, true);

            if ($event['event']['type'] === 'charge:confirmed') {
                $metadata = $event['event']['data']['metadata'];
                $targetLanguage = env('TARGET_LANGUAGE');

                $user = User::where('id', $metadata['user_id'])
                            ->where('language', $targetLanguage)
                            ->first();

                if ($user) {
                    $plan = $metadata['plan_type'];
                    $newExpiration = $plan === 'yearly'
                        ? now()->addYear()
                        : now()->addDays(30);

                    if ($user->is_subscribed && $user->subscription_expires_at && $user->subscription_expires_at > now()) {
                        $user->update([
                            'subscription_expires_at' => $plan === 'yearly'
                                ? $user->subscription_expires_at->copy()->addYear()
                                : $user->subscription_expires_at->copy()->addDays(30),
                        ]);
                    } else {
                        $user->update([
                            'subscription_expires_at' => $newExpiration,
                        ]);
                    }

                    $user->update([
                        'is_subscribed' => true,
                        'plan_type' => $plan,
                    ]);
                }
            }

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid signature'], 400);
    }

    protected function verifySignature($payload, $signature, $secret)
    {
        $computedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($signature, $computedSignature);
    }

    public function paymentSuccess()
    {
        return view('payment_success');
    }

    public function paymentCancel()
    {
        return view('payment-cancel');
    }
}
