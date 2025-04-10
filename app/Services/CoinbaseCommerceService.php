<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CoinbaseCommerceService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('COINBASE_COMMERCE_API_KEY');
        $this->client = new Client([
            'base_uri' => 'https://api.commerce.coinbase.com/',
            'headers' => [
                'X-CC-Api-Key' => $this->apiKey,
                
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    public function createCharge(array $data)
    {
        try {
            $response = $this->client->post('charges', [
                'json' => $data
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Coinbase Commerce API Error: ' . $e->getMessage());
            return null;
        }
    }
}