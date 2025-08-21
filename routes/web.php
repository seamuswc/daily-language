<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Web3Controller;

Route::get('/', [PaymentController::class, 'showPaymentForm'])->name('payment.form');
Route::get('/payment/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/cancel', [PaymentController::class, 'paymentCancel'])->name('payment.cancel');

// Web3 checkout (init + status)
Route::post('/api/checkout/init', [Web3Controller::class, 'init'])->name('checkout.init');
Route::get('/api/checkout/status/{reference}', [Web3Controller::class, 'status'])->name('checkout.status');
Route::post('/api/checkout/submit-tx', [Web3Controller::class, 'submitTx'])->name('checkout.submit_tx');
Route::post('/api/client-log', [Web3Controller::class, 'clientLog'])->name('client.log');