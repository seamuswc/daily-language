<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupProductionEnv extends Command
{
    protected $signature = 'env:setup-production';
    protected $description = 'Interactively create a .env.production file';

    public function handle()
    {
        $this->info('Let\'s configure your production .env file');

        $env = [
            'APP_NAME'                    => $this->ask('App name', 'Language'),
            'APP_ENV'                     => 'production',
            'APP_KEY'                     => 'base64:' . base64_encode(random_bytes(32)),
            'APP_DEBUG'                   => 'false',
            'APP_URL'                     => $this->ask('App URL (https://...)'),

            'APP_LOCALE'                  => $this->ask('Default app locale (e.g. en_ja)', 'en_ja'),
            'APP_FALLBACK_LOCALE'         => $this->ask('Fallback locale (same as above if unsure)', 'en_ja'),

            'SOURCE_LANGUAGE'             => $this->ask('Source language (e.g. english)', 'english'),
            'TARGET_LANGUAGE'             => $this->ask('Target language (e.g. japanese)', 'japanese'),

            'COINBASE_COMMERCE_API_KEY'   => $this->secret('Coinbase Commerce API key'),
            'COINBASE_COMMERCE_WEBHOOK_SECRET' => $this->secret('Coinbase Webhook Secret'),

            'DEEPSEEK_API_KEY'            => $this->secret('DeepSeek API Key'),

            'TENCENT_SECRET_ID'           => $this->secret('Tencent Cloud Secret ID'),
            'TENCENT_SECRET_KEY'          => $this->secret('Tencent Cloud Secret Key'),
            'TENCENT_SES_REGION'          => $this->ask('Tencent SES region', 'ap-singapore'),
            'TENCENT_SES_SENDER'          => $this->ask('Tencent SES verified sender'),
            'TENCENT_SES_TEMPLATE_ID'     => $this->ask('Tencent SES template ID'),

           
        ];

        $envPath = base_path('.env.production');
        $lines = collect($env)->map(fn($v, $k) => "{$k}={$v}")->implode("\n");

        File::put($envPath, $lines);

        $this->info('.env.production created at: ' . $envPath);
    }
}
