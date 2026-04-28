<?php

use App\Models\WargaAccount;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

it('menolak tanpa autentikasi', function () {
    $this->getJson('/api/pembayaran-pelanggan')->assertUnauthorized();
});

it('memfilter default bulan berjalan berdasarkan wkt_entry', function () {
    Carbon::setTestNow(Carbon::parse('2025-03-15 12:00:00'));

    createLegacyIuranTableForAccount('1114', [
        [
            'id_ipl' => 1,
            'account' => 1114,
            'wkt_entry' => '2025-03-10 10:00:00',
            'keterangan' => '',
        ],
        [
            'id_ipl' => 2,
            'account' => 1114,
            'wkt_entry' => '2025-02-28 23:59:59',
            'keterangan' => '',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $res = $this->getJson('/api/pembayaran-pelanggan?per_page=10&page=1')
        ->assertOk();

    $res->assertJsonPath('meta.total', 1);
    expect($res->json('data'))->toHaveCount(1);
    expect($res->json('data.0.id_ipl'))->toBe(1);

    Carbon::setTestNow();
});

it('override periode dengan from dan to', function () {
    createLegacyIuranTableForAccount('1114', [
        [
            'id_ipl' => 1,
            'account' => 1114,
            'wkt_entry' => '2025-01-05 08:00:00',
            'keterangan' => '',
        ],
        [
            'id_ipl' => 2,
            'account' => 1114,
            'wkt_entry' => '2025-02-01 10:00:00',
            'keterangan' => '',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $res = $this->getJson('/api/pembayaran-pelanggan?from=2025-01-01&to=2025-01-31')
        ->assertOk();

    expect($res->json('meta.total'))->toBe(1);
    expect($res->json('data.0.id_ipl'))->toBe(1);
});

it('override periode dengan bulan YYYY-MM', function () {
    createLegacyIuranTableForAccount('1114', [
        [
            'id_ipl' => 1,
            'account' => 1114,
            'wkt_entry' => '2025-03-01 00:00:00',
            'keterangan' => '',
        ],
        [
            'id_ipl' => 2,
            'account' => 1114,
            'wkt_entry' => '2025-04-01 00:00:00',
            'keterangan' => '',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $res = $this->getJson('/api/pembayaran-pelanggan?bulan=2025-03')
        ->assertOk();

    expect($res->json('meta.total'))->toBe(1);
    expect($res->json('data.0.id_ipl'))->toBe(1);
});

it('membutuhkan from dan to bersama', function () {
    createLegacyIuranTableForAccount('1114', []);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/pembayaran-pelanggan?from=2025-01-01')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['to']);
});

it('bulan tidak boleh digabung dengan from atau to', function () {
    createLegacyIuranTableForAccount('1114', []);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/pembayaran-pelanggan?bulan=2025-03&from=2025-03-01&to=2025-03-31')
        ->assertUnprocessable();
});

it('memvalidasi per_page maksimal 100', function () {
    createLegacyIuranTableForAccount('1114', []);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/pembayaran-pelanggan?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

it('mengurutkan berdasarkan id_ipl menurun', function () {
    createLegacyIuranTableForAccount('1114', [
        [
            'id_ipl' => 1,
            'account' => 1114,
            'wkt_entry' => '2025-03-15 10:00:00',
            'keterangan' => '',
        ],
        [
            'id_ipl' => 3,
            'account' => 1114,
            'wkt_entry' => '2025-03-15 10:00:00',
            'keterangan' => '',
        ],
        [
            'id_ipl' => 2,
            'account' => 1114,
            'wkt_entry' => '2025-03-15 10:00:00',
            'keterangan' => '',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $res = $this->getJson('/api/pembayaran-pelanggan?bulan=2025-03');

    $res->assertOk();
    expect(array_column($res->json('data'), 'id_ipl'))->toBe([3, 2, 1]);
});

it('mengotentikasi berdasarkan account user, bukan query string', function () {
    createLegacyIuranTableForAccount('1114', [
        [
            'id_ipl' => 1,
            'account' => 1114,
            'wkt_entry' => '2025-03-10 10:00:00',
            'keterangan' => '',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    Carbon::setTestNow(Carbon::parse('2025-03-15 12:00:00'));

    $this->getJson('/api/pembayaran-pelanggan?account=meretas')
        ->assertOk()
        ->assertJsonPath('meta.total', 1);

    Carbon::setTestNow();
});

it('404 ketika tabel tb_iuran tenant tidak ada di legacy', function () {
    $warga = WargaAccount::query()->create([
        'account' => '9999',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:9999']);

    $this->getJson('/api/pembayaran-pelanggan')
        ->assertNotFound()
        ->assertJsonPath('message', __('Data tenant tidak ditemukan.'));
});

it('403 ketika token tidak punya scope account tenant', function () {
    createLegacyIuranTableForAccount('1114', [
        [
            'id_ipl' => 1,
            'account' => 1114,
            'wkt_entry' => '2025-03-10 10:00:00',
            'keterangan' => '',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:lain']);

    Carbon::setTestNow(Carbon::parse('2025-03-15 12:00:00'));

    $this->getJson('/api/pembayaran-pelanggan')
        ->assertForbidden()
        ->assertJsonPath('message', __('Akses ditolak.'));

    Carbon::setTestNow();
});

it('memetakan kolom resource ke nama field yang diharapkan', function () {
    createLegacyIuranTableForAccount('1114', [
        [
            'id_ipl' => 1,
            'id_pelanggan' => 'P-1',
            'nama_warga' => 'Budi',
            'nama_sales' => 'Sales A',
            'nama_tipe' => 'Internet',
            'harga' => 100000,
            'jumlah_bayar' => 100000,
            'status_transaksi' => 'Selesai',
            'alamat' => 'Jl. Contoh',
            'tlp' => '081',
            'id_lokasi' => 5,
            'foto' => 'bukti.jpg',
            'bayar_bulan' => '2025-03-01',
            'nama_rekening' => 'BCA',
            'wkt_entry' => '2025-03-10 10:00:00',
            'keterangan' => 'OK',
            'metode_insentif' => 'Persen',
            'insentif_sales' => 'Ya',
            'nominal_insentif' => '5000',
            'account' => 1114,
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    Carbon::setTestNow(Carbon::parse('2025-03-15 12:00:00'));

    $this->getJson('/api/pembayaran-pelanggan')
        ->assertOk()
        ->assertJsonPath('data.0.id_ipl', 1)
        ->assertJsonPath('data.0.id_pelanggan', 'P-1')
        ->assertJsonPath('data.0.nama_pelanggan', 'Budi')
        ->assertJsonPath('data.0.nama_sales', 'Sales A')
        ->assertJsonPath('data.0.nama_pembayaran', 'Internet')
        ->assertJsonPath('data.0.nominal_harus_dibayar', 100000)
        ->assertJsonPath('data.0.nominal_pembayaran', 100000)
        ->assertJsonPath('data.0.status_pembayaran', 'Selesai')
        ->assertJsonPath('data.0.alamat', 'Jl. Contoh')
        ->assertJsonPath('data.0.tlp', '081')
        ->assertJsonPath('data.0.lokasi', 5)
        ->assertJsonPath('data.0.bukti_pembayaran', 'bukti.jpg')
        ->assertJsonPath('data.0.metode_pembayaran', 'BCA')
        ->assertJsonPath('data.0.keterangan', 'OK')
        ->assertJsonPath('data.0.metode_insentif', 'Persen')
        ->assertJsonPath('data.0.insentif', 'Ya')
        ->assertJsonPath('data.0.nominal_insentif', '5000');

    Carbon::setTestNow();
});
