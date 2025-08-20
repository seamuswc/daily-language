<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('plan'); // monthly|yearly
            $table->string('chain'); // solana|aptos
            $table->string('token'); // usdc|native
            $table->string('reference')->unique(); // solana ref or aptos invoice id
            $table->string('recipient'); // merchant address used
            $table->decimal('amount_usd', 12, 2);
            $table->string('amount_token'); // string to avoid float issues
            $table->string('status')->default('pending'); // pending|confirmed|failed
            $table->string('tx_id')->nullable(); // signature/hash
            $table->timestamps();
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};


