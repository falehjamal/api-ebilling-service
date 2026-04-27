<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LaporanPelanggan extends Model
{
    public const JNS_LAPORAN_INSTALASI_BARU = ['Installasi Baru', 'Survey Baru', 'New Regist'];

    public const STATUS_DIKECUALIKAN = ['Closed', 'Pemasangan Berhasil Dilakukan', 'Cancel'];

    protected $connection = 'legacy';

    protected $table = 'tb_laporan_pelanggan';

    protected $primaryKey = 'id_laporan_pelanggan';

    public $incrementing = true;

    public $timestamps = false;

    protected $guarded = [];

    /**
     * Hanya jenis laporan order/instalasi pelanggan baru, dengan status belum ditutup/dibatalkan.
     */
    public function scopeInstallasiPelangganBaru(Builder $query): Builder
    {
        return $query
            ->whereIn('jns_laporan', self::JNS_LAPORAN_INSTALASI_BARU)
            ->whereNotIn('status', self::STATUS_DIKECUALIKAN);
    }
}
