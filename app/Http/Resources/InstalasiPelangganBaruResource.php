<?php

namespace App\Http\Resources;

use App\Models\Legacy\LaporanPelanggan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LaporanPelanggan
 */
class InstalasiPelangganBaruResource extends JsonResource
{
    /**
     * Mirror SELECT * dari tb_laporan_pelanggan.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
