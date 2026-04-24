<?php

use App\Models\WargaAccount;
use Illuminate\Support\Facades\Cache;

it('mengeluarkan token saat account+username+password valid', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'warga1',
            'password' => 'rahasia',
            'status' => 'aktif',
            'account' => '1114',
        ],
    ]);

    $this->postJson('/api/auth/login', [
        'account' => '1114',
        'username' => 'warga1',
        'password' => 'rahasia',
    ])
        ->assertOk()
        ->assertJsonPath('warga.nama_warga', null)
        ->assertJsonStructure(['token', 'warga']);

    expect(WargaAccount::query()->count())->toBe(1);
});

it('menolak account yang tidak ada tabelnya', function () {
    $this->postJson('/api/auth/login', [
        'account' => '9999',
        'username' => 'x',
        'password' => 'y',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('account');
});

it('menolak kredensial salah', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'warga1',
            'password' => 'rahasia',
            'status' => 'aktif',
            'account' => '1114',
        ],
    ]);

    $this->postJson('/api/auth/login', [
        'account' => '1114',
        'username' => 'warga1',
        'password' => 'salah',
    ])
        ->assertUnauthorized();
});

it('menolak format account yang tidak valid', function () {
    $this->postJson('/api/auth/login', [
        'account' => "1114'; DROP TABLE users; --",
        'username' => 'warga1',
        'password' => 'x',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('account');
});

it('rate limits setelah 5 percobaan login', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'warga1',
            'password' => 'rahasia',
            'status' => 'aktif',
            'account' => '1114',
        ],
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/auth/login', [
            'account' => '1114',
            'username' => 'warga1',
            'password' => 'salah',
        ])->assertUnauthorized();
    }

    $this->postJson('/api/auth/login', [
        'account' => '1114',
        'username' => 'warga1',
        'password' => 'salah',
    ])->assertStatus(429);
});

it('tidak mengekspos password, nik, atau foto_ktp di response login', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'warga1',
            'password' => 'rahasia',
            'status' => 'aktif',
            'account' => '1114',
            'nik' => '123456',
            'foto_ktp' => 'foto1.jpg',
            'foto_rumah' => 'foto2.jpg',
        ],
    ]);

    $res = $this->postJson('/api/auth/login', [
        'account' => '1114',
        'username' => 'warga1',
        'password' => 'rahasia',
    ])->assertOk();

    $json = $res->json('warga');
    expect($json)->not->toHaveKey('password')
        ->and($json)->not->toHaveKey('nik')
        ->and($json)->not->toHaveKey('foto_ktp')
        ->and($json)->not->toHaveKey('foto_rumah');
});

afterEach(function () {
    Cache::flush();
});
