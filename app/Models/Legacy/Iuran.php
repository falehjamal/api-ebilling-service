<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Iuran extends Model
{
    protected $connection = 'legacy';

    protected $primaryKey = 'id_ipl';

    public $incrementing = true;

    public $timestamps = false;

    protected $guarded = [];

    public static function forAccount(string $account): self
    {
        $instance = new self;

        $table = 'tb_iuran_'.$account;
        $instance->setTable($table);

        return $instance;
    }

    /**
     * Kolom DB yang dipetakan ke \App\Http\Resources\PembayaranPelangganResource.
     *
     * @return list<string>
     */
    public static function columnsForListResponse(): array
    {
        return [
            'id_ipl',
            'id_pelanggan',
            'nama_warga',
            'nama_sales',
            'nama_tipe',
            'harga',
            'jumlah_bayar',
            'status_transaksi',
            'alamat',
            'tlp',
            'id_lokasi',
            'foto',
            'bayar_bulan',
            'nama_rekening',
            'wkt_entry',
            'keterangan',
            'metode_insentif',
            'insentif_sales',
            'nominal_insentif',
            'account',
        ];
    }
}
