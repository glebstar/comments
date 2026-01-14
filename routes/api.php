<?php

use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\VideoPostController;
use Illuminate\Support\Facades\Route;

Route::prefix('news')->group(function () {
    Route::get('/', [NewsController::class, 'index']);
    Route::post('/', [NewsController::class, 'store']);
    Route::get('{news}', [NewsController::class, 'show']);
    Route::post('{news}/comments', [CommentController::class, 'storeForNews']);
});

Route::prefix('video-posts')->group(function () {
    Route::get('/', [VideoPostController::class, 'index']);
    Route::post('/', [VideoPostController::class, 'store']);
    Route::get('{videoPost}', [VideoPostController::class, 'show']);
    Route::post('{videoPost}/comments', [CommentController::class, 'storeForVideoPost']);
});

Route::get('comments/{comment}', [CommentController::class, 'show']);
Route::post('comments/{comment}/replies', [CommentController::class, 'storeForComment']);
Route::patch('comments/{comment}', [CommentController::class, 'update']);
Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
