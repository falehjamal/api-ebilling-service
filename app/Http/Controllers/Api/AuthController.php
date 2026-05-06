<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\AuthenticateWarga;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\WargaResource;
use App\Models\Legacy\Warga;
use App\Models\WargaAccount;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthController extends Controller
{
    /**
     * Login warga (multi-tenant). Token Sanctum ber-scope `account:{tenant}`.
     *
     * **Rate limit:** 5 permintaan per menit per IP.
     *
     * @unauthenticated
     *
     * @response array{token: string, warga: array<string, mixed>}
     */
    #[Response(401, description: 'Kredensial tidak valid.', type: 'array{message: string}')]
    #[Response(422, description: 'Validasi gagal atau akun tenant tidak ditemukan.', type: 'array{message: string, errors?: array<string, array<int, string>>}')]
    #[Response(429, description: 'Terlalu banyak percobaan login.', type: 'array{message: string}')]
    public function login(LoginRequest $request, AuthenticateWarga $authenticateWarga): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $authenticateWarga(
                $validated['account'],
                $validated['username'],
                $validated['password'],
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'token' => $result['token']->plainTextToken,
            'warga' => (new WargaResource($result['warga']))->resolve(),
        ]);
    }

    /**
     * Profil pengguna yang sedang login (multi-tenant).
     *
     * @response \App\Http\Resources\WargaResource
     */
    #[Response(401, description: 'Token tidak valid atau kedaluwarsa.', type: 'array{message: string}')]
    #[Response(404, description: 'Profil tidak ditemukan.', type: 'array{message: string}')]
    public function me(Request $request): JsonResponse|WargaResource
    {
        /** @var WargaAccount $user */
        $user = $request->user();
        $account = $user->account;

        $warga = Warga::forAccount($account)
            ->where('id_warga', $user->id_warga_legacy)
            ->first();

        if ($warga === null) {
            return response()->json([
                'message' => __('Data warga tidak ditemukan di sistem lama.'),
            ], SymfonyResponse::HTTP_NOT_FOUND);
        }

        return new WargaResource($warga);
    }

    /**
     * Logout: hapus token akses saat ini (Sanctum).
     *
     * @status 204
     */
    #[Response(401, description: 'Token tidak valid atau kedaluwarsa.', type: 'array{message: string}')]
    public function logout(Request $request): SymfonyResponse
    {
        $user = $request->user();
        if ($user !== null) {
            $user->currentAccessToken()?->delete();
        }

        return response()->noContent();
    }
}
