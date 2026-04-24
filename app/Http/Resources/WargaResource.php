<?php

namespace App\Http\Resources;

use App\Models\Legacy\Warga;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Warga
 */
class WargaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_warga' => $this->id_warga,
            'id_pelanggan' => $this->id_pelanggan,
            'tgl_registrasi' => $this->tgl_registrasi,
            'account' => $this->account,
            'id_lokasi' => $this->id_lokasi,
            'nama_warga' => $this->nama_warga,
            'id_tipe_pembayaran' => $this->id_tipe_pembayaran,
            'nama_tipe' => $this->nama_tipe,
            'keterangan' => $this->keterangan,
            'harga' => $this->harga,
            'ppn' => $this->ppn,
            'nama_lain_lain' => $this->nama_lain_lain,
            'nominal_lain_lain' => $this->nominal_lain_lain,
            'jns_tipe_pembayaran' => $this->jns_tipe_pembayaran,
            'username' => $this->username,
            'alamat' => $this->alamat,
            'titik_lokasi' => $this->titik_lokasi,
            'blok' => $this->blok,
            'no_rumah' => $this->no_rumah,
            'rt' => $this->rt,
            'rw' => $this->rw,
            'dusun' => $this->dusun,
            'kelurahan' => $this->kelurahan,
            'kecamatan' => $this->kecamatan,
            'kabupaten' => $this->kabupaten,
            'propinsi' => $this->propinsi,
            'kode_pos' => $this->kode_pos,
            'tlp' => $this->tlp,
            'tlp2' => $this->tlp2,
            'email' => $this->email,
            'level' => $this->level,
            'status' => $this->status,
            'status_langganan' => $this->status_langganan,
            'catatan' => $this->catatan,
            'id_sales' => $this->id_sales,
            'nama_sales' => $this->nama_sales,
            'id_router' => $this->id_router,
            'jns_koneksi' => $this->jns_koneksi,
            'tgl_jatuh_tempo' => $this->tgl_jatuh_tempo,
            'prorate' => $this->prorate,
            'jns_blokir' => $this->jns_blokir,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'modul_olt' => $this->modul_olt,
            'olt_port' => $this->olt_port,
            'index_onu' => $this->index_onu,
            'sn_onu' => $this->sn_onu,
            'insentif_sales' => $this->insentif_sales,
            'metode_insentif' => $this->metode_insentif,
            'nominal_insentif' => $this->nominal_insentif,
            'id_olt' => $this->id_olt,
            'id_odc' => $this->id_odc,
            'id_odp' => $this->id_odp,
            'nama_olt' => $this->nama_olt,
            'nama_odc' => $this->nama_odc,
            'nama_odp' => $this->nama_odp,
            'status_koneksi' => $this->status_koneksi,
            'waktu_pengecekan' => $this->waktu_pengecekan,
            'jns_tagihan' => $this->jns_tagihan,
            'tgl_tagihan' => $this->tgl_tagihan,
            'tgl_isolir' => $this->tgl_isolir,
        ];
    }
}
