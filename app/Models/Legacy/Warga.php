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
}
