<?php

use App\Models\Legacy\Warga;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Tabel uji per-tenant: tb_warga_{$account} di connection legacy (file sqlite sama dengan default saat testing).
 */
function createLegacyWargaTableForAccount(string $account, array $rows = []): void
{
    $table = 'tb_warga_'.$account;
    Schema::connection('legacy')->dropIfExists($table);

    Schema::connection('legacy')->create($table, function (Blueprint $table) {
        $table->increments('id_warga');
        $table->string('username')->default('');
        $table->string('password')->default('');
        $table->string('level')->default('');
        $table->string('status')->default('1');
        $table->string('account')->nullable();
        $table->string('id_pelanggan')->nullable();
        $table->string('nik')->nullable();
        $table->string('foto_ktp')->nullable();
        $table->string('foto_rumah')->nullable();
        $table->string('nama_warga')->nullable();
        $table->date('tgl_registrasi')->nullable();
        $existing = [
            'id_warga', 'username', 'password', 'level', 'status', 'account', 'id_pelanggan', 'nik',
            'foto_ktp', 'foto_rumah', 'nama_warga', 'tgl_registrasi',
        ];
        foreach (Warga::columnsForListResponse() as $col) {
            if (in_array($col, $existing, true)) {
                continue;
            }
            $table->string($col)->nullable();
        }
    });

    foreach ($rows as $row) {
        DB::connection('legacy')->table($table)->insert($row);
    }
}
