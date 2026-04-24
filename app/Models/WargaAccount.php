<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

#[Fillable(['account', 'id_warga_legacy', 'username', 'last_login_at'])]
class WargaAccount extends Authenticatable
{
    /** @use HasApiTokens<PersonalAccessToken> */
    use HasApiTokens;

    /**
     * @return array<int, string>
     */
    protected function casts(): array
    {
        return [
            'id_warga_legacy' => 'integer',
            'last_login_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return '';
    }
}
