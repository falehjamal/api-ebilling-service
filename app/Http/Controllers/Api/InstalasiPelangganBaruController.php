<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexInstalasiPelangganBaruRequest;
use App\Http\Resources\InstalasiPelangganBaruResource;
use App\Models\Legacy\LaporanPelanggan;
use App\Models\WargaAccount;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InstalasiPelangganBaruController extends Controller
{
    /**
     * Order, instalasi, atau registrasi baru yang relevan untuk tenant Anda; filter jenis dan status proses. Tenant dari token.
     *
     * **Rate limit:** 60 permintaan per menit per IP.
     *
     * @response \Illuminate\Http\Resources\Json\AnonymousResourceCollection<int, \App\Http\Resources\InstalasiPelangganBaruResource>
     */
    #[Response(401, description: 'Tanpa token atau token tidak valid.', type: 'array{message: string}')]
    #[Response(403, description: 'Token tidak memiliki scope tenant.', type: 'array{message: string}')]
    #[Response(422, description: 'Validasi query (page, per_page).', type: 'array{message: string, errors?: array<string, array<int, string>>}')]
    public function index(IndexInstalasiPelangganBaruRequest $request): AnonymousResourceCollection|JsonResponse
    {
        /** @var WargaAccount $user */
        $user = $request->user();
        $account = $user->account;

        if (! $user->tokenCan('account:'.$account)) {
            return response()->json([
                'message' => __('Akses ditolak.'),
            ], SymfonyResponse::HTTP_FORBIDDEN);
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
