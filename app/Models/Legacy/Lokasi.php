<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    protected $connection = 'legacy';

    protected $table = 'tb_lokasi';

    protected $primaryKey = 'id_lokasi';

    public $incrementing = true;

    public $timestamps = false;

    protected $guarded = [];
}
