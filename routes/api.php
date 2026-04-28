<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InstalasiPelangganBaruController;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\PembayaranPelangganController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/api/health');

Route::get('/health', /**
 * Health check — layanan hidup.
 *
 * @unauthenticated
 *
 * @response array{ok: bool, message: string}
 */ function () {
    return response()->json([
        'ok' => true,
        'message' => 'API is alive',
    ]);
});

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'refresh.sanctum.token'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::get('pelanggan', [PelangganController::class, 'index'])->middleware('throttle:60,1');
    Route::get('instalasi-pelanggan-baru', [InstalasiPelangganBaruController::class, 'index'])->middleware('throttle:60,1');
    Route::get('pembayaran-pelanggan', [PembayaranPelangganController::class, 'index'])->middleware('throttle:60,1');

    Route::get('hello-world', /**
     * Contoh endpoint terproteksi (uji token).
     *
     * @response array{message: string, account: string|null, id_warga_legacy: int|null}
     */ function (Request $request) {
        $user = $request->user();

        return response()->json([
            'message' => 'Hello World',
            'account' => $user?->account,
            'id_warga_legacy' => $user?->id_warga_legacy,
        ]);
    });
});
