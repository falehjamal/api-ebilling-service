<?php

use App\Models\WargaAccount;
use Laravel\Sanctum\Sanctum;

it('menolak tanpa autentikasi', function () {
    $this->getJson('/api/instalasi-pelanggan-baru')->assertUnauthorized();
});

it('mengembalikan hanya baris tenant user yang memenuhi filter jns_laporan dan status', function () {
    createLegacyLaporanPelangganTable([
        [
            'jns_laporan' => 'Installasi Baru',
            'status' => 'Open',
            'account' => 1114,
            'waktu_pembuatan' => '2025-01-10 10:00:00',
        ],
        [
            'jns_laporan' => 'Lainnya',
            'status' => 'Open',
            'account' => 1114,
            'waktu_pembuatan' => '2025-01-10 11:00:00',
        ],
        [
            'jns_laporan' => 'Installasi Baru',
            'status' => 'Closed',
            'account' => 1114,
            'waktu_pembuatan' => '2025-01-10 12:00:00',
        ],
        [
            'jns_laporan' => 'Installasi Baru',
            'status' => 'Open',
            'account' => 9999,
            'waktu_pembuatan' => '2025-01-10 13:00:00',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $res = $this->getJson('/api/instalasi-pelanggan-baru?per_page=10&page=1')
        ->assertOk();

    $res->assertJsonPath('meta.total', 1);
    expect($res->json('data'))->toHaveCount(1);
    expect($res->json('data.0.jns_laporan'))->toBe('Installasi Baru');
    expect($res->json('data.0.status'))->toBe('Open');
});

it('mengurutkan berdasarkan waktu_pembuatan menurun', function () {
    createLegacyLaporanPelangganTable([
        [
            'jns_laporan' => 'Survey Baru',
            'status' => 'Pending',
            'account' => 1114,
            'waktu_pembuatan' => '2025-01-01 00:00:00',
        ],
        [
            'jns_laporan' => 'Survey Baru',
            'status' => 'Pending',
            'account' => 1114,
            'waktu_pembuatan' => '2025-01-03 00:00:00',
        ],
        [
            'jns_laporan' => 'New Regist',
            'status' => 'Aktif',
            'account' => 1114,
            'waktu_pembuatan' => '2025-01-02 00:00:00',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $res = $this->getJson('/api/instalasi-pelanggan-baru?per_page=10');
    $res->assertOk();

    $ids = array_column($res->json('data'), 'id_laporan_pelanggan');
    expect($ids)->toBe([2, 3, 1]);
});

it('memvalidasi per_page maksimal 100', function () {
    createLegacyLaporanPelangganTable([]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/instalasi-pelanggan-baru?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

it('mengotentikasi berdasarkan account user, bukan query string', function () {
    createLegacyLaporanPelangganTable([
        [
            'jns_laporan' => 'Installasi Baru',
            'status' => 'Open',
            'account' => 1114,
            'waktu_pembuatan' => '2025-01-10 10:00:00',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/instalasi-pelanggan-baru?account=meretas')
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});
