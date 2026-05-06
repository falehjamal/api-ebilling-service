<?php

it('menyajikan UI dokumentasi Scramble di /docs/api', function () {
    $this->get('/docs/api')
        ->assertSuccessful()
        ->assertSee('API', false);
});

it('menyajikan spesifikasi OpenAPI JSON di /docs/api.json', function () {
    $response = $this->getJson('/docs/api.json');

    $response->assertSuccessful();

    $data = $response->json();
    expect($data)->toBeArray()
        ->and($data['openapi'] ?? null)->toBe('3.1.0')
        ->and($data['paths'] ?? null)->toBeArray()
        ->and($data['servers'][0]['url'] ?? '')->toEndWith('/api')
        ->and($data['paths']['/login'] ?? null)->toBeArray()
        ->and($data['paths']['/me'] ?? null)->toBeArray()
        ->and($data['paths']['/lokasi'] ?? null)->toBeArray()
        ->and($data['paths']['/lokasi']['get']['responses']['401'] ?? null)->toBeArray()
        ->and($data['security'][0]['bearer'] ?? null)->toBeArray()
        ->and($data['components']['securitySchemes']['bearer'] ?? null)->toBeArray()
        ->and($data['components']['securitySchemes']['bearer']['type'] ?? null)->toBe('http')
        ->and($data['components']['securitySchemes']['bearer']['scheme'] ?? null)->toBe('bearer');
});

it('mengembalikan 404 untuk root web dan /up', function () {
    $this->get('/')->assertNotFound();
    $this->get('/up')->assertNotFound();
});
