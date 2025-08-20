<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SolanaPayService
{
    protected string $rpcUrl;

    public function __construct()
    {
        $this->rpcUrl = config('crypto.solana.rpc_url');
    }

    public function findVerifiedPayment(
        string $reference,
        string $recipient,
        string $usdcMint,
        string $expectedAmountTokens
    ): ?string {
        $signatures = $this->getSignaturesForAddress($reference);
        foreach ($signatures as $sig) {
            $tx = $this->getTransaction($sig);
            if (!$tx) {
                continue;
            }
            // Must be confirmed/successful
            if (($tx['meta']['err'] ?? null) !== null) {
                continue;
            }

            if ($this->matchesUsdcPayment($tx, $recipient, $usdcMint, $expectedAmountTokens)) {
                return $sig;
            }
        }
        return null;
    }

    public function verifyTransactionSignature(
        string $signature,
        string $recipient,
        string $usdcMint,
        string $expectedAmountTokens
    ): bool {
        $tx = $this->getTransaction($signature);
        if (!$tx) {
            return false;
        }
        if (($tx['meta']['err'] ?? null) !== null) {
            return false;
        }
        return $this->matchesUsdcPayment($tx, $recipient, $usdcMint, $expectedAmountTokens);
    }

    protected function getSignaturesForAddress(string $address, int $limit = 20): array
    {
        $payload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getSignaturesForAddress',
            'params' => [ $address, [ 'limit' => $limit ] ],
        ];

        $resp = Http::timeout(10)->post($this->rpcUrl, $payload);
        if (!$resp->ok()) {
            Log::warning('Solana RPC getSignaturesForAddress failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            return [];
        }
        $result = $resp->json('result') ?? [];
        return array_map(fn($e) => $e['signature'], $result);
    }

    protected function getTransaction(string $signature): ?array
    {
        $payload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getTransaction',
            'params' => [
                $signature,
                [
                    'encoding' => 'jsonParsed',
                    'maxSupportedTransactionVersion' => 0,
                    'commitment' => 'confirmed',
                ],
            ],
        ];
        $resp = Http::timeout(10)->post($this->rpcUrl, $payload);
        if (!$resp->ok()) {
            Log::warning('Solana RPC getTransaction failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            return null;
        }
        return $resp->json('result');
    }

    protected function matchesUsdcPayment(array $tx, string $recipient, string $usdcMint, string $expectedAmountTokens): bool
    {
        $pre = $tx['meta']['preTokenBalances'] ?? [];
        $post = $tx['meta']['postTokenBalances'] ?? [];

        // Build maps by accountIndex for USDC
        $preMap = [];
        foreach ($pre as $b) {
            if (($b['mint'] ?? '') === $usdcMint) {
                $preMap[$b['accountIndex']] = $b;
            }
        }
        foreach ($post as $b) {
            if (($b['mint'] ?? '') !== $usdcMint) {
                continue;
            }
            // We need positive delta for the recipient owner
            $owner = $b['owner'] ?? null;
            if (strcasecmp((string) $owner, (string) $recipient) !== 0) {
                continue;
            }
            $dec = (int) (($b['uiTokenAmount']['decimals'] ?? 6));
            $preAmountBase = $this->getBaseUnits($preMap[$b['accountIndex']]['uiTokenAmount']['amount'] ?? '0');
            $postAmountBase = $this->getBaseUnits($b['uiTokenAmount']['amount'] ?? '0');
            $deltaBase = $postAmountBase - $preAmountBase;
            $expectedBase = (int) round(((float) $expectedAmountTokens) * pow(10, $dec));
            if ($deltaBase === $expectedBase && $expectedBase > 0) {
                return true;
            }
        }
        return false;
    }

    protected function getBaseUnits(string $amount): int
    {
        // amount is already in base units per Solana RPC docs
        // guard large ints by casting via string
        return (int) $amount;
    }
}


