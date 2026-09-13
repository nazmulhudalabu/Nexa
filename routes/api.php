<?php

use App\Http\Controllers\Api\PostApiController;
use App\Http\Controllers\Api\AuthApiController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiAuthenticate;

Route::post('/auth/register', [AuthApiController::class, 'register'])->middleware('throttle:6,1');
Route::post('/auth/login', [AuthApiController::class, 'login'])->middleware('throttle:6,1');
Route::post('/auth/refresh', [AuthApiController::class, 'refresh'])->middleware('throttle:6,1');
Route::middleware(ApiAuthenticate::class)->group(function (): void {
	Route::get('/auth/me', [AuthApiController::class, 'me']);
	Route::post('/auth/logout', [AuthApiController::class, 'logout']);
});

Route::get('/posts', [PostApiController::class, 'index']);
Route::get('/posts/{post}', [PostApiController::class, 'show']);