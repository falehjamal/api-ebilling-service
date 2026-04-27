<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexInstalasiPelangganBaruRequest;
use App\Http\Resources\InstalasiPelangganBaruResource;
use App\Models\Legacy\LaporanPelanggan;
use App\Models\WargaAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class InstalasiPelangganBaruController extends Controller
{
    public function index(IndexInstalasiPelangganBaruRequest $request): AnonymousResourceCollection|JsonResponse
    {
        /** @var WargaAccount $user */
        $user = $request->user();
        $account = $user->account;

        if (! $user->tokenCan('account:'.$account)) {
            return response()->json([
                'message' => __('Akses ditolak.'),
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);

        $paginator = LaporanPelanggan::query()
            ->where('account', $account)
            ->installasiPelangganBaru()
            ->orderByDesc('waktu_pembuatan')
            ->paginate($perPage)
            ->appends($request->query());

        return InstalasiPelangganBaruResource::collection($paginator);
    }
}
