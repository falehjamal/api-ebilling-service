<?php

use App\Models\WargaAccount;
use Laravel\Sanctum\Sanctum;

it('menolak akses tanpa token', function () {
    $this->getJson('/api/hello-world')->assertUnauthorized();
});

it('menolak akses tanpa token meski bukan permintaan JSON (tanpa route login web)', function () {
    $this->get('/api/hello-world')
        ->assertUnauthorized()
        ->assertHeader('content-type', 'application/json');
});

it('menolak token tidak valid', function () {
    $this->getJson('/api/hello-world', [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer token-tidak-valid',
    ])->assertUnauthorized();
});

it('memberi hello world saat token valid', function () {
    $warga = WargaAccount::query()->create([
        'account' => '1114',
        'id_warga_legacy' => 1,
        'username' => 'warga1',
    ]);

    Sanctum::actingAs($warga);

    $this->getJson('/api/hello-world')
        ->assertOk()
        ->assertJsonPath('message', 'Hello World')
        ->assertJsonPath('account', '1114')
        ->assertJsonPath('id_warga_legacy', 1);
});
