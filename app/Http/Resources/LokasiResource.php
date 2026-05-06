<?php

namespace App\Http\Resources;

use App\Models\Legacy\Lokasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lokasi
 */
class LokasiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_lokasi' => $this->id_lokasi,
            'account' => $this->account,
            'nama_lokasi' => $this->nama_lokasi,
            'alamat_lokasi' => $this->alamat_lokasi,
            'tlp_lokasi' => $this->tlp_lokasi,
            'group_wa' => $this->group_wa,
            'id_pic' => $this->id_pic,
            'nama_pic' => $this->nama_pic,
            'kode_lokasi' => $this->kode_lokasi,
            'account_wagw' => $this->account_wagw,
            'insentif_sales' => $this->insentif_sales,
            'metode_insentif' => $this->metode_insentif,
            'nominal_insentif' => $this->nominal_insentif,
            'filter_lokasi' => $this->filter_lokasi,
            'jns_lokasi' => $this->jns_lokasi,
            'id_cabang' => $this->id_cabang,
            'nama_cabang' => $this->nama_cabang,
            'id_referensi_corcab' => $this->id_referensi_corcab,
            'nama_referensi_corcab' => $this->nama_referensi_corcab,
            'provinsi' => $this->provinsi,
            'kabupaten' => $this->kabupaten,
            'kecamatan' => $this->kecamatan,
            'kelurahan' => $this->kelurahan,
            'rt' => $this->rt,
            'rw' => $this->rw,
        ];
    }
}
