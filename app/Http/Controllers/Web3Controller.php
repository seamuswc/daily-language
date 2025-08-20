<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\SolanaPayService;
use App\Services\AptosPayService;
use Illuminate\Support\Facades\Log;

class Web3Controller extends Controller
{
    public function init(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'plan' => 'required|in:monthly,yearly',
            'chain' => 'required|in:solana,aptos',
            'token' => 'required|in:usdc',
        ]);

        Log::info('Checkout init request', [
            'email' => $data['email'],
            'plan' => $data['plan'],
            'chain' => $data['chain'],
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        $pricing = config('crypto.pricing');
        $amountUsd = $data['plan'] === 'yearly' ? (float) $pricing['yearly_usd'] : (float) $pricing['monthly_usd'];

        // USDC: 1 token unit == 1 USD (display amount; on-chain decimals are 6)
        $amountToken = $amountUsd;

        // Generate a Solana Pay compliant reference (public key) for Solana; use UUID for Aptos
        $reference = $data['chain'] === 'solana'
            ? $this->generateSolanaReferencePubkey()
            : Str::uuid()->toString();

        $recipient = $data['chain'] === 'solana'
            ? config('crypto.solana.merchant_address')
            : config('crypto.aptos.merchant_address');

        $invoice = Invoice::create([
            'email' => $data['email'],
            'plan' => $data['plan'],
            'chain' => $data['chain'],
            'token' => $data['token'],
            'reference' => $reference,
            'recipient' => $recipient,
            'amount_usd' => $amountUsd,
            'amount_token' => (string) $amountToken,
            'status' => 'pending',
        ]);

        Log::info('Checkout invoice created', [
            'reference' => $reference,
            'recipient' => $recipient,
            'amount_token' => (string) $amountToken,
            'amount_usd' => (string) $amountUsd,
            'chain' => $data['chain'],
        ]);

        $payload = [
            'reference' => $reference,
            'recipient' => $recipient,
            'amountToken' => (string) $amountToken,
            'amountUsd' => (string) $amountUsd,
            'chain' => $data['chain'],
            'token' => $data['token'],
            'solana' => [
                'network' => config('crypto.solana.network'),
                'rpcUrl' => config('crypto.solana.rpc_url'),
                'usdcMint' => config('crypto.solana.usdc_mint'),
            ],
            'aptos' => [
                'network' => config('crypto.aptos.network'),
                'fullnodeUrl' => config('crypto.aptos.fullnode_url'),
                'usdcCoinType' => config('crypto.aptos.usdc_coin_type'),
            ],
        ];

        return response()->json($payload);
    }

    public function status(string $reference)
    {
        $invoice = Invoice::where('reference', $reference)->firstOrFail();

        // If already confirmed, return as-is
        if ($invoice->status === 'confirmed') {
            return response()->json(['status' => 'confirmed', 'txId' => $invoice->tx_id]);
        }

        if ($invoice->chain === 'solana') {
            $svc = app(SolanaPayService::class);
            $sig = $svc->findVerifiedPayment(
                reference: $invoice->reference,
                recipient: $invoice->recipient,
                usdcMint: config('crypto.solana.usdc_mint'),
                expectedAmountTokens: (string) $invoice->amount_token,
            );

            if ($sig) {
                Log::info('Solana payment verified', [
                    'reference' => $invoice->reference,
                    'tx' => $sig,
                ]);
                $invoice->update([
                    'status' => 'confirmed',
                    'tx_id' => $sig,
                ]);

                // Activate subscription on success
                $this->activateSubscription($invoice);

                return response()->json(['status' => 'confirmed', 'txId' => $sig]);
            }
        }

        if ($invoice->chain === 'aptos') {
            // No polling implementation yet; status remains pending until user submits tx hash
        }

        Log::info('Checkout status polled', [
            'reference' => $reference,
            'status' => $invoice->status,
        ]);
        return response()->json(['status' => $invoice->status, 'txId' => $invoice->tx_id]);
    }

    protected function generateSolanaReferencePubkey(): string
    {
        // Simple random 32-byte key, base58 encode (compatible with solana-web3.js PublicKey)
        $random = random_bytes(32);
        return $this->base58Encode($random);
    }

    protected function base58Encode(string $binary): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $num = gmp_init(bin2hex($binary), 16);
        $encoded = '';
        while (gmp_cmp($num, 0) > 0) {
            $rem = gmp_intval(gmp_mod($num, 58));
            $encoded = $alphabet[$rem] . $encoded;
            $num = gmp_div_q($num, 58);
        }
        // Preserve leading zeros as '1's
        foreach (str_split($binary) as $byte) {
            if (ord($byte) === 0) {
                $encoded = '1' . $encoded;
            } else {
                break;
            }
        }
        return $encoded ?: '1';
    }

    public function submitTx(Request $request)
    {
        $data = $request->validate([
            'reference' => 'required|string',
            'tx' => 'required|string',
        ]);
        $invoice = Invoice::where('reference', $data['reference'])->firstOrFail();
        Log::info('Submit tx received', [
            'reference' => $data['reference'],
            'tx' => $data['tx'],
            'chain' => $invoice->chain,
        ]);
        if ($invoice->status === 'confirmed') {
            return response()->json(['status' => 'confirmed', 'txId' => $invoice->tx_id]);
        }
        $ok = false;
        if ($invoice->chain === 'solana') {
            $svc = app(SolanaPayService::class);
            $ok = $svc->verifyTransactionSignature(
                signature: $data['tx'],
                recipient: $invoice->recipient,
                usdcMint: config('crypto.solana.usdc_mint'),
                expectedAmountTokens: (string) $invoice->amount_token,
            );
        } elseif ($invoice->chain === 'aptos') {
            $svc = app(AptosPayService::class);
            $ok = $svc->verifyTransactionHash(
                hash: $data['tx'],
                recipient: $invoice->recipient,
                usdcCoinType: config('crypto.aptos.usdc_coin_type'),
                expectedAmountTokens: (string) $invoice->amount_token,
            );
        }
        if ($ok) {
            Log::info('Payment verified via submitTx', [
                'reference' => $data['reference'],
                'tx' => $data['tx'],
            ]);
            $invoice->update([
                'status' => 'confirmed',
                'tx_id' => $data['tx'],
            ]);
            $this->activateSubscription($invoice);
            return response()->json(['status' => 'confirmed', 'txId' => $data['tx']]);
        }
        return response()->json(['status' => 'pending']);
    }

    public function clientLog(Request $request)
    {
        $event = (string) $request->input('event', 'client');
        $data = $request->input('data', []);
        Log::info('ClientEvent', [
            'event' => $event,
            'data' => $data,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);
        return response()->json(['ok' => true]);
    }

    protected function activateSubscription(Invoice $invoice): void
    {
        $targetLanguage = env('TARGET_LANGUAGE');
        $user = User::firstOrCreate(
            ['email' => $invoice->email, 'language' => $targetLanguage],
            ['language' => $targetLanguage]
        );

        $isYearly = $invoice->plan === 'yearly';
        $newExpiration = $isYearly ? now()->addYear() : now()->addDays(30);

        if ($user->is_subscribed && $user->subscription_expires_at && $user->subscription_expires_at > now()) {
            $user->update([
                'subscription_expires_at' => $isYearly
                    ? $user->subscription_expires_at->copy()->addYear()
                    : $user->subscription_expires_at->copy()->addDays(30),
            ]);
        } else {
            $user->update(['subscription_expires_at' => $newExpiration]);
        }

        $user->update([
            'is_subscribed' => true,
            'plan_type' => $invoice->plan,
        ]);
    }
}


