<?php

namespace App\Http\Controllers;

use App\Services\DailySentenceService;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\View;

class PaymentController extends Controller
{
    public function __construct(
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

    // Legacy Coinbase flow removed

    public function paymentSuccess()
    {
        return view('payment_success');
    }

    public function paymentCancel()
    {
        return view('payment-cancel');
    }
}
