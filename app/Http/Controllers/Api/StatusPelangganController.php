<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ShowStatusPelangganRequest;
use App\Http\Resources\StatusPelangganResource;
use App\Models\Legacy\Warga;
use App\Models\WargaAccount;
use App\Support\LegacyAccount;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class StatusPelangganController extends Controller
{
    /**
     * Status ringkas satu pelanggan (`ACTIVE`, `SUSPENDED`, `DISMANTLE`, `UNKNOWN`) dari kombinasi field `status` dan `status_langganan` pada respons. Tenant dari token; `id_warga` dari daftar pelanggan.
     *
     * **Rate limit:** 60 permintaan per menit per IP.
     *
     * @response array{data: array{id_warga: int, id_pelanggan: string|null, nama_warga: string|null, account: string|null, status: string|null, status_langganan: string|null, status_pelanggan: string}}
     */
    #[Response(401, description: 'Tanpa token atau token tidak valid.', type: 'array{message: string}')]
    #[Response(403, description: 'Token tidak memiliki scope tenant.', type: 'array{message: string}')]
    #[Response(404, description: 'Data tenant tidak tersedia atau pelanggan tidak ditemukan.', type: 'array{message: string}')]
    #[Response(422, description: 'Validasi query (id_warga).', type: 'array{message: string, errors?: array<string, array<int, string>>}')]
    public function show(ShowStatusPelangganRequest $request): StatusPelangganResource|JsonResponse
    {
        /** @var WargaAccount $user */
        $user = $request->user();
        $account = $user->account;

        if (! LegacyAccount::tableExists($account)) {
            return response()->json([
                'message' => __('Data tenant tidak ditemukan.'),
            ], SymfonyResponse::HTTP_NOT_FOUND);
        }

        if (! $user->tokenCan('account:'.$account)) {
            return response()->json([
                'message' => __('Akses ditolak.'),
            ], SymfonyResponse::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();
        $idWarga = (int) $validated['id_warga'];

        $warga = Warga::forAccount($account)
            ->select(['id_warga', 'id_pelanggan', 'nama_warga', 'account', 'status', 'status_langganan'])
            ->where('id_warga', $idWarga)
            ->where('level', 'Pelanggan')
            ->first();

        if ($warga === null) {
            return response()->json([
                'message' => __('Pelanggan tidak ditemukan.'),
            ], SymfonyResponse::HTTP_NOT_FOUND);
        }

        return new StatusPelangganResource($warga);
    }
}
