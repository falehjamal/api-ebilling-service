<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexPembayaranPelangganRequest;
use App\Http\Resources\PembayaranPelangganResource;
use App\Models\Legacy\Iuran;
use App\Models\WargaAccount;
use App\Support\LegacyAccount;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PembayaranPelangganController extends Controller
{
    public function index(IndexPembayaranPelangganRequest $request): AnonymousResourceCollection|JsonResponse
    {
        /** @var WargaAccount $user */
        $user = $request->user();
        $account = $user->account;

        if (! LegacyAccount::iuranTableExists($account)) {
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

        [$awal, $akhir] = $this->periodBounds($validated);

        $paginator = Iuran::forAccount($account)
            ->select(Iuran::columnsForListResponse())
            ->where('account', $account)
            ->whereBetween('wkt_entry', [$awal, $akhir])
            ->orderByDesc('id_ipl')
            ->paginate($perPage)
            ->appends($request->query());

        return PembayaranPelangganResource::collection($paginator);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: string}
     */
    private function periodBounds(array $validated): array
    {
        if (! empty($validated['from']) && ! empty($validated['to'])) {
            $start = Carbon::parse($validated['from'])->startOfDay();
            $end = Carbon::parse($validated['to'])->endOfDay();

            return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
        }

        if (! empty($validated['bulan'])) {
            $month = Carbon::createFromFormat('Y-m', $validated['bulan']);

            return [
                $month->copy()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s'),
                $month->copy()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s'),
            ];
        }

        $now = Carbon::now();

        return [
            $now->copy()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s'),
            $now->copy()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s'),
        ];
    }
}
