<?php

use App\Models\WargaAccount;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

it('mengeluarkan token saat account+username+password valid', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'warga1',
            'password' => 'rahasia',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
        ],
    ]);

    $this->postJson('/api/login', [
        'account' => '1114',
        'username' => 'warga1',
        'password' => 'rahasia',
    ])
        ->assertOk()
        ->assertJsonPath('warga.nama_warga', null)
        ->assertJsonStructure(['token', 'warga']);

    expect(WargaAccount::query()->count())->toBe(1);
});

it('menyimpan expires_at token sekitar 24 jam (sliding) saat login', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'warga1',
            'password' => 'rahasia',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
        ],
    ]);

    $this->postJson('/api/login', [
        'account' => '1114',
        'username' => 'warga1',
        'password' => 'rahasia',
    ])->assertOk();

    $token = PersonalAccessToken::query()->first();
    expect($token)->not->toBeNull()
        ->and($token->expires_at)->not->toBeNull()
        ->and($token->expires_at->isAfter(now()->addHours(23)))->toBeTrue()
        ->and($token->expires_at->isBefore(now()->addHours(25)))->toBeTrue();
});

it('menolak request dengan token yang expires_at sudah lewat', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'warga1',
            'password' => 'rahasia',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
        ],
    ]);

    $res = $this->postJson('/api/login', [
        'account' => '1114',
        'username' => 'warga1',
        'password' => 'rahasia',
    ])->assertOk();

    $plain = $res->json('token');
    $token = PersonalAccessToken::query()->first();
    $token?->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->getJson('/api/me', [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$plain,
    ])->assertUnauthorized();
});

it('memperbarui expires_at setelah request API sukses', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'warga1',
            'password' => 'rahasia',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
        ],
    ]);

    $res = $this->postJson('/api/login', [
        'account' => '1114',
        'username' => 'warga1',
        'password' => 'rahasia',
    ])->assertOk();

    $plain = $res->json('token');
    $token = PersonalAccessToken::query()->first();
    $token?->forceFill(['expires_at' => now()->addHour()])->save();
    $before = $token->fresh()->expires_at;

    $this->getJson('/api/me', [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$plain,
    ])->assertOk();

    $after = $token->fresh()->expires_at;
    expect($after->gt($before))->toBeTrue();
});

it('menolak account yang tidak ada tabelnya', function () {
    $this->postJson('/api/login', [
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
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
        ],
    ]);

    $this->postJson('/api/login', [
        'account' => '1114',
        'username' => 'warga1',
        'password' => 'salah',
    ])
        ->assertUnauthorized();
});

it('menolak format account yang tidak valid', function () {
    $this->postJson('/api/login', [
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
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
        ],
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/login', [
            'account' => '1114',
            'username' => 'warga1',
            'password' => 'salah',
        ])->assertUnauthorized();
    }

    $this->postJson('/api/login', [
        'account' => '1114',
        'username' => 'warga1',
        'password' => 'salah',
    ])->assertStatus(429);
});

it('mengizinkan login jika level bukan Pelanggan', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'warga1',
            'password' => 'rahasia',
            'level' => 'Admin',
            'status' => '1',
            'account' => '1114',
        ],
    ]);

    $this->postJson('/api/login', [
        'account' => '1114',
        'username' => 'warga1',
        'password' => 'rahasia',
    ])
        ->assertOk()
        ->assertJsonPath('warga.level', 'Admin')
        ->assertJsonStructure(['token', 'warga']);
});

it('menolak login jika status bukan 1', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'warga1',
            'password' => 'rahasia',
            'level' => 'Pelanggan',
            'status' => '0',
            'account' => '1114',
        ],
    ]);

    $this->postJson('/api/login', [
        'account' => '1114',
        'username' => 'warga1',
        'password' => 'rahasia',
    ])->assertUnauthorized();
});

it('tidak mengekspos password, nik, atau foto_ktp di response login', function () {
    createLegacyWargaTableForAccount('1114', [
        [
            'username' => 'warga1',
            'password' => 'rahasia',
            'level' => 'Pelanggan',
            'status' => '1',
            'account' => '1114',
            'nik' => '123456',
            'foto_ktp' => 'foto1.jpg',
            'foto_rumah' => 'foto2.jpg',
        ],
    ]);

    $res = $this->postJson('/api/login', [
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
