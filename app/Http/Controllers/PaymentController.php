<?php

namespace App\Http\Controllers;

use App\Services\CoinbaseCommerceService;
use App\Services\JapaneseSentenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class PaymentController extends Controller
{
    public function __construct(
        protected CoinbaseCommerceService $coinbaseService,
        protected JapaneseSentenceService $sentenceService
    ) {}

    public function showPaymentForm()
    {
        return view('payment', [
            'sentence' => $this->sentenceService->generateSentence()
        ]);
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'plan' => 'required|in:monthly,yearly'
        ]);

        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['is_subscribed' => false]
        );

        // Determine pricing based on plan
        $plan = $request->plan;
        $amount = ($plan === 'yearly') ? '12.00' : '2.00';
        $description = ($plan === 'yearly') 
            ? 'Yearly Japanese learning subscription' 
            : 'Monthly Japanese learning subscription';

        $chargeData = [
            'name' => 'Japanese Daily Sentences Subscription',
            'description' => $description,
            'pricing_type' => 'fixed_price',
            'local_price' => [
                'amount' => $amount,
                'currency' => 'USDC'
            ],
            'metadata' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'plan_type' => $plan,
                'expires_at' => ($plan === 'yearly') 
                    ? now()->addYear()->toDateTimeString() 
                    : now()->addDays(30)->toDateTimeString()
            ],
            'redirect_url' => route('payment.success'),
            'cancel_url' => route('payment.cancel'),
        ];

        $charge = $this->coinbaseService->createCharge($chargeData);

        if ($charge && isset($charge['data']['hosted_url'])) {
            return redirect($charge['data']['hosted_url']);
        }

        return back()->with('error', 'Failed to create payment. Please try again.');
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
                $user = User::find($metadata['user_id']);
                
                if ($user) {
                    $user->update([
                        'is_subscribed' => true,
                        'subscription_expires_at' => $metadata['expires_at'],
                        'plan_type' => $metadata['plan_type']
                    ]);
                    
                    // Send welcome email
                    //Mail::to($user->email)->send(new WelcomeMail($metadata['plan_type']));
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
        return view('payment-success');
    }

    public function paymentCancel()
    {
        return view('payment-cancel');
    }
}