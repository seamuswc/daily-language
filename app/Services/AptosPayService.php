<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AptosPayService
{
    protected string $fullnodeUrl;

    public function __construct()
    {
        $this->fullnodeUrl = rtrim(config('crypto.aptos.fullnode_url'), '/');
    }

    public function verifyTransactionHash(
        string $hash,
        string $recipient,
        string $usdcCoinType,
        string $expectedAmountTokens
    ): bool {
        $url = $this->fullnodeUrl . '/transactions/by_hash/' . $hash;
        $resp = Http::timeout(10)->get($url);
        if (!$resp->ok()) {
            Log::warning('Aptos fullnode tx fetch failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            return false;
        }
        $tx = $resp->json();
        if (!($tx['success'] ?? false)) {
            return false;
        }

        // Find a deposit event into recipient's USDC coin store with expected amount
        $events = $tx['events'] ?? [];
        $expectedBase = (int) round(((float) $expectedAmountTokens) * 1_000_000); // USDC 6 decimals
        foreach ($events as $e) {
            $type = $e['type'] ?? '';
            $guid = $e['guid'] ?? [];
            $account = $guid['account_address'] ?? '';
            $data = $e['data'] ?? [];
            $eventAmount = isset($data['amount']) ? (int) $data['amount'] : null;
            $eventCoinType = $data['coin_type'] ?? ($e['type'] ?? '');

            // Some nodes place coin type on type args; accept either way
            $matchesCoin = str_contains($eventCoinType, $usdcCoinType) || str_contains($type, $usdcCoinType);
            if ($matchesCoin && strtolower($account) === strtolower($recipient) && $eventAmount === $expectedBase) {
                return true;
            }
        }

        return false;
    }
}


