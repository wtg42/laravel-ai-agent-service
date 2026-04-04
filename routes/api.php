<?php

use App\Http\Controllers\Api\ChineseNameDetectionController;
use Illuminate\Support\Facades\Route;

Route::post('/pii/chinese-names/detect', [ChineseNameDetectionController::class, 'store'])
    ->name('api.pii.chinese-names.detect');
