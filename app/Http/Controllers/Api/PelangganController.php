<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexPelangganRequest;
use App\Http\Resources\WargaResource;
use App\Models\Legacy\Warga;
use App\Models\WargaAccount;
use App\Support\LegacyAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PelangganController extends Controller
{
    public function index(IndexPelangganRequest $request): AnonymousResourceCollection|JsonResponse
    {
        /** @var WargaAccount $user */
        $user = $request->user();
        $account = $user->account;

        if (! LegacyAccount::tableExists($account)) {
            return response()->json([
                'message' => __('Data tenant tidak ditemukan.'),
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $user->tokenCan('account:'.$account)) {
            return response()->json([
                'message' => __('Akses ditolak.'),
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = Warga::forAccount($account)
            ->select(Warga::columnsForListResponse())
            ->where('level', 'Pelanggan')
            ->orderByDesc('id_warga');

        $paginator = $query->paginate($perPage)->appends($request->query());

        return WargaResource::collection($paginator);
    }
}
