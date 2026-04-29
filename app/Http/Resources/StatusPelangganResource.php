<?php

namespace App\Http\Resources;

use App\Models\Legacy\Warga;
use App\Support\StatusPelangganDeriver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Warga
 */
class StatusPelangganResource extends JsonResource
{
    /**
     * @return array<string, int|string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_warga' => $this->id_warga,
            'id_pelanggan' => $this->id_pelanggan,
            'nama_warga' => $this->nama_warga,
            'account' => $this->account,
            'status' => $this->status,
            'status_langganan' => $this->status_langganan,
            'status_pelanggan' => StatusPelangganDeriver::derive($this->status, $this->status_langganan),
        ];
    }
}
