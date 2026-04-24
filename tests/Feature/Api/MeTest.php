<?php

use App\Models\WargaAccount;
use Laravel\Sanctum\Sanctum;

it('mengembalikan data warga saat token valid', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'warga1',
            'password' => 'x',
            'status' => 'aktif',
            'account' => '1114',
            'nama_warga' => 'Budi',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'warga1',
    ]);

    Sanctum::actingAs($warga);

    $this->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.nama_warga', 'Budi');
});

it('menolak tanpa autentikasi', function () {
    $this->getJson('/api/me')->assertUnauthorized();
});
