<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexPelangganRequest;
use App\Http\Resources\WargaResource;
use App\Models\Legacy\Warga;
use App\Models\WargaAccount;
use App\Support\LegacyAccount;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PelangganController extends Controller
{
    /**
     * Daftar pelanggan (`level = Pelanggan`) untuk tenant user, terpaginasi. `account` dari token, bukan query.
     *
     * **Rate limit:** 60 permintaan per menit per IP.
     *
     * @response \Illuminate\Http\Resources\Json\AnonymousResourceCollection<int, \App\Http\Resources\WargaResource>
     */
    #[Response(401, description: 'Tanpa token atau token tidak valid.', type: 'array{message: string}')]
    #[Response(403, description: 'Token tidak memiliki scope tenant.', type: 'array{message: string}')]
    #[Response(404, description: 'Tabel tb_warga_{account} tidak ada di legacy.', type: 'array{message: string}')]
    #[Response(422, description: 'Validasi query (page, per_page).', type: 'array{message: string, errors?: array<string, array<int, string>>}')]
    public function index(IndexPelangganRequest $request): AnonymousResourceCollection|JsonResponse
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
        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = Warga::forAccount($account)
            ->select(Warga::columnsForListResponse())
            ->where('level', 'Pelanggan')
            ->orderByDesc('id_warga');

        $paginator = $query->paginate($perPage)->appends($request->query());

        return WargaResource::collection($paginator);
    }
}
