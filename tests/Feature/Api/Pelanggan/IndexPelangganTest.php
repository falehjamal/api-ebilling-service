<?php

use App\Models\WargaAccount;
use Laravel\Sanctum\Sanctum;

it('menolak tanpa autentikasi', function () {
    $this->getJson('/api/pelanggan')->assertUnauthorized();
});

it('mengembalikan daftar pelanggan terpaginasi hanya level Pelanggan', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'a',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
            'nama_warga' => 'Satu',
        ],
        [
            'username' => 'b',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
            'nama_warga' => 'Dua',
        ],
        [
            'username' => 'c',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
            'nama_warga' => 'Tiga',
        ],
        [
            'username' => 'admin1',
            'password' => 'x',
            'level' => 'Admin',
            'status' => '1',
            'account' => '1114',
            'nama_warga' => 'Bukan pelanggan',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $res = $this->getJson('/api/pelanggan?per_page=10&page=1')
        ->assertOk();

    $res->assertJsonPath('meta.total', 3);
    expect($res->json('data'))->toHaveCount(3);
});

it('memvalidasi per_page maksimal 100', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'a',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/pelanggan?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

it('mengembalikan 404 jika tabel legacy tenant tidak ada', function () {
    $warga = WargaAccount::query()->create([
        'account' => '9998',
        'id_warga_legacy' => 1,
        'username' => 'x',
    ]);

    Sanctum::actingAs($warga, ['account:9998']);

    $this->getJson('/api/pelanggan')
        ->assertNotFound();
});

it('mengotentikasi berdasarkan account user, bukan query string', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'a',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/pelanggan?account=meretas')
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});
