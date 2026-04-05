<?php

use App\Http\Controllers\Api\ChineseNameDetectionController;
use App\Http\Controllers\Api\EmailScanController;
use Illuminate\Support\Facades\Route;

Route::post('/pii/email-scan', EmailScanController::class)
    ->name('api.pii.email-scan');

Route::post('/pii/chinese-names/detect', [ChineseNameDetectionController::class, 'store'])
    ->name('api.pii.chinese-names.detect');
