<?php

return [
    'solana' => [
        'network' => env('SOLANA_NETWORK', 'mainnet-beta'),
        'rpc_url' => env('SOLANA_RPC_URL', 'https://api.mainnet-beta.solana.com'),
        'merchant_address' => env('SOLANA_MERCHANT_ADDRESS', ''),
        // Mainnet USDC mint
        'usdc_mint' => env('SOLANA_USDC_MINT', 'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v'),
    ],
    'aptos' => [
        'network' => env('APTOS_NETWORK', 'mainnet'),
        'fullnode_url' => env('APTOS_FULLNODE_URL', 'https://fullnode.mainnet.aptoslabs.com/v1'),
        'merchant_address' => env('APTOS_MERCHANT_ADDRESS', ''),
        // USDC coin type on Aptos (Circle's USDC). Update if needed.
        'usdc_coin_type' => env('APTOS_USDC_COIN_TYPE', '0x5e156f1207d0ebfa19a9eeff00d62a282278fb8719f4fab3a586a0a2c0fffbea::coin::T'),
    ],
    'pricing' => [
        'monthly_usd' => env('PRICING_MONTHLY_USD', 2.00),
        'yearly_usd' => env('PRICING_YEARLY_USD', 12.00),
    ],
];


