<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexLokasiRequest;
use App\Http\Resources\LokasiResource;
use App\Models\Legacy\Lokasi;
use App\Models\WargaAccount;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class LokasiController extends Controller
{
    /**
     * Daftar lokasi untuk tenant token Anda (filter kolom `account` di `tb_lokasi`). Parameter `account` di URL tidak dipakai.
     *
     * **Rate limit:** 60 permintaan per menit per IP.
     *
     * @response \Illuminate\Http\Resources\Json\AnonymousResourceCollection<int, \App\Http\Resources\LokasiResource>
     */
    #[Response(401, description: 'Tanpa token atau token tidak valid.', type: 'array{message: string}')]
    #[Response(403, description: 'Token tidak memiliki scope tenant.', type: 'array{message: string}')]
    #[Response(422, description: 'Validasi query (page, per_page).', type: 'array{message: string, errors?: array<string, array<int, string>>}')]
    public function index(IndexLokasiRequest $request): AnonymousResourceCollection|JsonResponse
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

        $paginator = Lokasi::query()
            ->where('account', $account)
            ->orderByDesc('id_lokasi')
            ->paginate($perPage)
            ->appends($request->query());

        return LokasiResource::collection($paginator);
    }
}
