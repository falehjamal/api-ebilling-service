<?php

use App\Models\WargaAccount;
use Laravel\Sanctum\Sanctum;

it('menolak tanpa autentikasi', function () {
    $this->getJson('/api/lokasi')->assertUnauthorized();
});

it('mengembalikan hanya lokasi milik tenant token', function () {
    createLegacyLokasiTable([
        [
            'nama_lokasi' => 'A',
            'alamat_lokasi' => 'Jl. Satu',
            'account' => 1114,
        ],
        [
            'nama_lokasi' => 'B',
            'alamat_lokasi' => 'Jl. Dua',
            'account' => 1114,
        ],
        [
            'nama_lokasi' => 'C',
            'alamat_lokasi' => 'Jl. Lain',
            'account' => 9999,
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $res = $this->getJson('/api/lokasi?per_page=10&page=1')
        ->assertOk();

    $res->assertJsonPath('meta.total', 2);
    expect($res->json('data'))->toHaveCount(2);
});

it('mengurutkan berdasarkan id_lokasi menurun', function () {
    createLegacyLokasiTable([
        [
            'nama_lokasi' => 'Pertama',
            'alamat_lokasi' => 'x',
            'account' => 1114,
        ],
        [
            'nama_lokasi' => 'Kedua',
            'alamat_lokasi' => 'y',
            'account' => 1114,
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $res = $this->getJson('/api/lokasi?per_page=10')->assertOk();

    expect($res->json('data.0.nama_lokasi'))->toBe('Kedua')
        ->and($res->json('data.1.nama_lokasi'))->toBe('Pertama');
});

it('memvalidasi per_page maksimal 100', function () {
    createLegacyLokasiTable([]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/lokasi?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

it('mengotentikasi berdasarkan account user, bukan query string', function () {
    createLegacyLokasiTable([
        [
            'nama_lokasi' => 'Satu',
            'alamat_lokasi' => 'Jl.',
            'account' => 1114,
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/lokasi?account=meretas')
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});
