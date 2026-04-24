<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'ok' => true,
    'message' => 'API is alive',
]));

/*
| Alias singkat (banyak client mengharapkan POST /api/login).
| Rute kanonik tetap di bawah prefix auth/ sesuai struktur API.
*/
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);

    Route::get('hello-world', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'message' => 'Hello World',
            'account' => $user?->account,
            'id_warga_legacy' => $user?->id_warga_legacy,
        ]);
    });
});
