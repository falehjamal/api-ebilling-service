<?php

use App\Models\WargaAccount;
use Laravel\Sanctum\Sanctum;

it('menolak tanpa autentikasi', function () {
    $this->getJson('/api/status-pelanggan?id_warga=1')->assertUnauthorized();
});

it('memvalidasi id_warga wajib', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'a',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
            'id_pelanggan' => 'P-1',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/status-pelanggan')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_warga');
});

it('memvalidasi id_warga harus bilangan bulat positif', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'a',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
            'id_pelanggan' => 'P-1',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/status-pelanggan?id_warga=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_warga');
});

it('mengembalikan 404 jika tabel legacy tenant tidak ada', function () {
    $warga = WargaAccount::query()->create([
        'account' => '9998',
        'id_warga_legacy' => 1,
        'username' => 'x',
    ]);

    Sanctum::actingAs($warga, ['account:9998']);

    $this->getJson('/api/status-pelanggan?id_warga=1')
        ->assertNotFound()
        ->assertJsonPath('message', __('Data tenant tidak ditemukan.'));
});

it('403 ketika token tidak punya scope account tenant', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'a',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
            'id_pelanggan' => 'P-1',
            'status_langganan' => 'On',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:lain']);

    $this->getJson('/api/status-pelanggan?id_warga=1')
        ->assertForbidden()
        ->assertJsonPath('message', __('Akses ditolak.'));
});

it('mengembalikan 404 jika id_warga tidak ada', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'a',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
            'id_pelanggan' => 'P-1',
            'status_langganan' => 'On',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/status-pelanggan?id_warga=99999')
        ->assertNotFound()
        ->assertJsonPath('message', __('Pelanggan tidak ditemukan.'));
});

it('mengembalikan ACTIVE saat status 1 dan langganan on', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'a',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
            'nama_warga' => 'Budi',
            'id_pelanggan' => 'PLG-1',
            'status_langganan' => 'On',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/status-pelanggan?id_warga=1')
        ->assertOk()
        ->assertJsonPath('data.id_warga', 1)
        ->assertJsonPath('data.status_pelanggan', 'ACTIVE')
        ->assertJsonPath('data.status', '1')
        ->assertJsonPath('data.status_langganan', 'On')
        ->assertJsonPath('data.nama_warga', 'Budi');
});

it('mengembalikan SUSPENDED saat status 1 dan langganan off', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'a',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
            'id_pelanggan' => 'PLG-1',
            'status_langganan' => 'Off',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/status-pelanggan?id_warga=1')
        ->assertOk()
        ->assertJsonPath('data.status_pelanggan', 'SUSPENDED');
});

it('mengembalikan DISMANTLE saat status 0 dan langganan off', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'a',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '0',
            'account' => '1114',
            'id_pelanggan' => 'PLG-1',
            'status_langganan' => 'Off',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/status-pelanggan?id_warga=1')
        ->assertOk()
        ->assertJsonPath('data.status_pelanggan', 'DISMANTLE');
});

it('mengembalikan UNKNOWN untuk kombinasi di luar mapping', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'a',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '2',
            'account' => '1114',
            'id_pelanggan' => 'PLG-1',
            'status_langganan' => 'Off',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/status-pelanggan?id_warga=1')
        ->assertOk()
        ->assertJsonPath('data.status_pelanggan', 'UNKNOWN');
});

it('case-insensitive untuk status_langganan', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'a',
            'password' => 'x',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
            'id_pelanggan' => 'PLG-1',
            'status_langganan' => 'ON',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'a',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/status-pelanggan?id_warga=1')
        ->assertOk()
        ->assertJsonPath('data.status_pelanggan', 'ACTIVE');
});

it('tidak mengembalikan baris bukan Pelanggan walau id_warga cocok', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'admin1',
            'password' => 'x',
            'level' => 'Admin',
            'status' => '1',
            'account' => '1114',
            'id_pelanggan' => 'X-1',
            'status_langganan' => 'On',
        ],
    ]);

    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'admin1',
    ]);

    Sanctum::actingAs($warga, ['account:1114']);

    $this->getJson('/api/status-pelanggan?id_warga=1')
        ->assertNotFound();
});
