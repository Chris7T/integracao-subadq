<?php

use App\Http\Controllers\PixController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WithdrawController;
use Illuminate\Support\Facades\Route;

Route::post('/pix', [PixController::class, 'store']);
Route::post('/withdraw', [WithdrawController::class, 'store']);

Route::post('/webhook/pix', [WebhookController::class, 'pix']);
Route::post('/webhook/withdraw', [WebhookController::class, 'withdraw']);
