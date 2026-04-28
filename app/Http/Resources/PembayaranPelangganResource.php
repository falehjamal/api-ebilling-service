<?php

namespace App\Http\Resources;

use App\Models\Legacy\Iuran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Iuran
 */
class PembayaranPelangganResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_ipl' => $this->id_ipl,
            'id_pelanggan' => $this->id_pelanggan,
            'nama_pelanggan' => $this->nama_warga,
            'nama_sales' => $this->nama_sales,
            'nama_pembayaran' => $this->nama_tipe,
            'nominal_harus_dibayar' => $this->harga,
            'nominal_pembayaran' => $this->jumlah_bayar,
            'status_pembayaran' => $this->status_transaksi,
            'alamat' => $this->alamat,
            'tlp' => $this->tlp,
            'lokasi' => $this->id_lokasi,
            'bukti_pembayaran' => $this->foto,
            'periode_pembayaran' => $this->bayar_bulan,
            'metode_pembayaran' => $this->nama_rekening,
            'waktu_entry' => $this->wkt_entry,
            'keterangan' => $this->keterangan,
            'metode_insentif' => $this->metode_insentif,
            'insentif' => $this->insentif_sales,
            'nominal_insentif' => $this->nominal_insentif,
        ];
    }
}
