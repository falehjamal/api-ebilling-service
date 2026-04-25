<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Hidden(['password', 'nik', 'foto_ktp', 'foto_rumah'])]
class Warga extends Model
{
    protected $connection = 'legacy';

    protected $primaryKey = 'id_warga';

    public $incrementing = true;

    public $timestamps = false;

    protected $guarded = [];

    public static function forAccount(string $account): self
    {
        $instance = new self;

        $table = 'tb_warga_'.$account;
        $instance->setTable($table);

        return $instance;
    }

    /**
     * Kolom DB yang dipetakan ke \App\Http\Resources\WargaResource (tanpa field sensitif).
     *
     * @return list<string>
     */
    public static function columnsForListResponse(): array
    {
        return [
            'id_warga', 'id_pelanggan', 'tgl_registrasi', 'account', 'id_lokasi', 'nama_warga',
            'id_tipe_pembayaran', 'nama_tipe', 'keterangan', 'harga', 'ppn', 'nama_lain_lain', 'nominal_lain_lain',
            'jns_tipe_pembayaran', 'username', 'alamat', 'titik_lokasi', 'blok', 'no_rumah', 'rt', 'rw', 'dusun',
            'kelurahan', 'kecamatan', 'kabupaten', 'propinsi', 'kode_pos', 'tlp', 'tlp2', 'email', 'level',
            'status', 'status_langganan', 'catatan', 'id_sales', 'nama_sales', 'id_router', 'jns_koneksi',
            'tgl_jatuh_tempo', 'prorate', 'jns_blokir', 'latitude', 'longitude', 'modul_olt', 'olt_port',
            'index_onu', 'sn_onu', 'insentif_sales', 'metode_insentif', 'nominal_insentif', 'id_olt', 'id_odc',
            'id_odp', 'nama_olt', 'nama_odc', 'nama_odp', 'status_koneksi', 'waktu_pengecekan', 'jns_tagihan',
            'tgl_tagihan', 'tgl_isolir',
        ];
    }
}
