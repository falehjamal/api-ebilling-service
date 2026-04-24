<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\AuthenticateWarga;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\WargaResource;
use App\Models\Legacy\Warga;
use App\Models\WargaAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
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
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'token' => $result['token']->plainTextToken,
            'warga' => (new WargaResource($result['warga']))->resolve(),
        ]);
    }

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
            ], Response::HTTP_NOT_FOUND);
        }

        return new WargaResource($warga);
    }

    public function logout(Request $request): Response
    {
        $user = $request->user();
        if ($user !== null) {
            $user->currentAccessToken()?->delete();
        }

        return response()->noContent();
    }
}
