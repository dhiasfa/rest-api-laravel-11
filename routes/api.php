<?php

use App\Models\Article;
// use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\v2\ArticleController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::post('register', [App\Http\Controllers\API\Auth\AuthController::class, 'register']);
Route::post('login', [App\Http\Controllers\API\Auth\AuthController::class, 'login']);

Route::prefix('v1')->group(function () {
    Route::get('list-articles', [App\Http\Controllers\API\v1\ArticleController::class, 'index']);
    Route::post('store-articles', [App\Http\Controllers\API\v1\ArticleController::class, 'store']);
    Route::get('read-article/{id}', [App\Http\Controllers\API\v1\ArticleController::class, 'show']);
    Route::put('update-article/{id}', [App\Http\Controllers\API\v1\ArticleController::class, 'update']);
    Route::delete('delete-article/{id}', [App\Http\Controllers\API\v1\ArticleController::class, 'delete']);
    Route::get('article/search', [App\Http\Controllers\API\v1\ArticleController::class, 'index']);
});



Route::prefix('v2')->group(function () {
    Route::get('list-articles', [App\Http\Controllers\API\v2\ArticleController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::resource('article', ArticleController::class);
        Route::get('read-article/{id}', [App\Http\Controllers\API\v2\ArticleController::class, 'show']);
        Route::delete('delete-article/{id}', [App\Http\Controllers\API\v2\ArticleController::class, 'delete']);
    });
    Route::put('update-article/{id}', [App\Http\Controllers\API\v2\ArticleController::class, 'update']);
    Route::post('store-articles', [App\Http\Controllers\API\v2\ArticleController::class, 'store']);
});
