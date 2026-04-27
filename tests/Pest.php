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

/**
 * Tabel uji: tb_laporan_pelanggan di connection legacy (sama connection dengan default saat testing).
 */
function createLegacyLaporanPelangganTable(array $rows = []): void
{
    $tableName = 'tb_laporan_pelanggan';
    Schema::connection('legacy')->dropIfExists($tableName);

    Schema::connection('legacy')->create($tableName, function (Blueprint $table) {
        $table->increments('id_laporan_pelanggan');
        $table->string('id_pelanggan', 64)->nullable();
        $table->string('id_warga', 64)->nullable();
        $table->string('nama_warga', 255)->nullable();
        $table->string('tlp', 64)->nullable();
        $table->text('alamat')->nullable();
        $table->string('blok', 64)->nullable();
        $table->string('no_rumah', 64)->nullable();
        $table->string('jns_laporan', 64)->nullable();
        $table->string('nama_laporan', 255)->nullable();
        $table->dateTime('waktu_masuk')->nullable();
        $table->dateTime('waktu_keluar')->nullable();
        $table->string('foto', 255)->nullable();
        $table->text('keterangan')->nullable();
        $table->string('status', 128)->nullable();
        $table->integer('account')->nullable();
        $table->integer('id_lokasi')->nullable();
        $table->integer('id_sales')->nullable();
        $table->string('nama_sales', 255)->nullable();
        $table->string('id_pelapor', 64)->nullable();
        $table->string('nama_pelapor', 255)->nullable();
        $table->dateTime('waktu_pembuatan')->nullable();
        $table->string('id_teknisi1', 64)->nullable();
        $table->string('nama_teknisi1', 255)->nullable();
        $table->string('tlp_teknisi1', 64)->nullable();
        $table->integer('biaya')->nullable();
        $table->string('nama_tipe', 255)->nullable();
        $table->string('keterangan_tipe', 255)->nullable();
        $table->string('harga_tipe', 64)->nullable();
        $table->string('kendala', 255)->nullable();
        $table->string('aksi_perbaikan', 255)->nullable();
        $table->string('koordinat', 255)->nullable();
        $table->integer('id_approval')->nullable();
        $table->string('nama_approval', 255)->nullable();
        $table->dateTime('wkt_approval')->nullable();
        $table->string('status_approval', 64)->nullable();
        $table->string('nama_perwakilan_perusahan', 255)->nullable();
        $table->string('nama_jabatan', 128)->nullable();
        $table->string('tgl_mulai', 32)->nullable();
        $table->string('tgl_akhir', 32)->nullable();
        $table->string('id_tipe_pembayaran', 64)->nullable();
        $table->string('ttd_perwakilan', 255)->nullable();
        $table->string('ttd_teknisi', 255)->nullable();
        $table->string('ttd_pelanggan', 255)->nullable();
        $table->string('ip_ttd_perwakilan', 64)->nullable();
        $table->string('ip_ttd_teknisi', 64)->nullable();
        $table->string('ip_ttd_pelanggan', 64)->nullable();
        $table->dateTime('waktu_ttd_perwakilan')->nullable();
        $table->dateTime('waktu_ttd_teknisi')->nullable();
        $table->dateTime('waktu_ttd_pelanggan')->nullable();
        $table->string('device_ttd_perwakilan', 128)->nullable();
        $table->string('device_ttd_teknisi', 128)->nullable();
        $table->string('device_ttd_pelanggan', 128)->nullable();
        $table->string('kota', 128)->nullable();
        $table->string('id_teknisi2', 64)->nullable();
        $table->string('nama_teknisi2', 255)->nullable();
        $table->string('tlp_teknisi2', 64)->nullable();
        $table->string('jenis_kategori', 128)->nullable();
        $table->string('nama_kategori', 128)->nullable();
        $table->string('rating', 32)->nullable();
        $table->string('ulasan', 255)->nullable();
        $table->string('id_cabang', 64)->nullable();
        $table->string('keterangan_followup', 255)->nullable();
        $table->dateTime('waktu_followup')->nullable();
    });

    foreach ($rows as $row) {
        DB::connection('legacy')->table($tableName)->insert($row);
    }
}
