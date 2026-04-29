# Dokumentasi API e-Billing (Warga)

Ringkasan endpoint, header, body, dan contoh `curl`. Semua path di bawah prefix **`/api`**.

**Base URL contoh lokal:** `https://api-ebilling-service.test`  
Ganti sesuai environment Anda.

**Dokumentasi interaktif (OpenAPI, [dedoc/scramble](https://github.com/dedoc/scramble)):** `GET /docs/api` (UI), `GET /docs/api.json` (spesifikasi JSON). Isi ringkas juga ada di `config/scramble.php` (`info.description`).

---

## Ringkas multi-tenant

- **`account`**: kode tenant (hanya huruf, angka, `_`, `-`). Data warga dibaca dari tabel legacy **`tb_warga_{account}`** (contoh: `tb_warga_1114`). Data pembayaran/iuran dibaca dari **`tb_iuran_{account}`** (contoh: `tb_iuran_1114`).
- **Login** hanya untuk **pelanggan**: kolom **`level`** harus persis **`Pelanggan`**, kolom **`status`** harus **`1`** (string, sesuai `where` di kode), plus `username` + `password` (plain text) cocok.
- **Token**: Laravel Sanctum. Kirim **`Authorization: Bearer {token}`** untuk endpoint yang membutuhkan auth. Masa berlaku **geser (sliding)**: setiap request API yang valid memperpanjang token; **tanpa request dalam 24 jam** (default, bisa diubah lewat `SANCTUM_TOKEN_INACTIVITY_TTL_MINUTES` / `config/sanctum.php`) token tidak dapat dipakai lagi — **login ulang** diperlukan.

---

## Header umum

| Header | Wajib | Keterangan |
|--------|--------|------------|
| `Accept` | Disarankan | `application/json` agar respons selalu JSON |
| `Content-Type` | Untuk POST JSON | `application/json` |
| `Authorization` | Untuk rute terproteksi | `Bearer {plainTextToken}` |

---

## Daftar endpoint

| Method | Path | Auth | Keterangan |
|--------|------|------|------------|
| `GET` | `/api/health` | Tidak | Cek service hidup |
| `POST` | `/api/login` | Tidak | Login |
| `POST` | `/api/logout` | Bearer | Hapus token saat ini |
| `GET` | `/api/me` | Bearer | Profil warga dari DB legacy |
| `GET` | `/api/pelanggan` | Bearer | Daftar pelanggan (`level = Pelanggan`) tenant user, terpaginasi |
| `GET` | `/api/instalasi-pelanggan-baru` | Bearer | Order/instalasi pelanggan baru (`tb_laporan_pelanggan`), terpaginasi |
| `GET` | `/api/pembayaran-pelanggan` | Bearer | Pembayaran pelanggan (`tb_iuran_{account}`), terpaginasi |
| `GET` | `/api/status-pelanggan` | Bearer | Status turunan pelanggan (`ACTIVE` / `SUSPENDED` / `DISMANTLE` / `UNKNOWN`) dari `tb_warga_{account}` |
| `GET` | `/api/hello-world` | Bearer | Contoh endpoint terproteksi |

**Rate limit:** login `POST /api/login` = **5** request per menit per IP. `GET /api/pelanggan`, `GET /api/instalasi-pelanggan-baru`, `GET /api/pembayaran-pelanggan`, dan `GET /api/status-pelanggan` = **60** request per menit per IP (throttle Laravel; nilai dapat disesuaikan di rute).

---

## 1. Health check

**Request**

```http
GET /api/health HTTP/1.1
Host: api-ebilling-service.test
Accept: application/json
```

**Respons `200`**

```json
{
  "ok": true,
  "message": "API is alive"
}
```

**cURL**

```bash
curl -sS -X GET "https://api-ebilling-service.test/api/health" \
  -H "Accept: application/json"
```

---

## 2. Login

**URL:** `POST /api/login`

**Body JSON**

| Field | Tipe | Wajib | Keterangan |
|-------|------|--------|------------|
| `account` | string | Ya | Regex: `^[A-Za-z0-9_-]+$`, max 64 |
| `username` | string | Ya | Sesuai kolom `username` di `tb_warga_{account}` |
| `password` | string | Ya | Sesuai kolom `password` di legacy (plain) |

**Respons sukses `200`**

```json
{
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "warga": {
    "id_warga": 1,
    "username": "warga1",
    "nama_warga": "Contoh",
    "level": "Pelanggan",
    "status": "1"
  }
}
```

Objek `warga` adalah subset field (whitelist); tidak termasuk `password`, `nik`, `foto_ktp`, dll.

Field `token` dipasang dengan **`expires_at`** di database: default **24 jam** setelah aktifitas terakhir (setiap request API yang sukses memperpanjang masa berlaku). Variabel `.env`: `SANCTUM_TOKEN_INACTIVITY_TTL_MINUTES` (menit).

**Respons gagal (contoh)**

| HTTP | Kondisi |
|------|---------|
| `422` | Validasi gagal (format `account`, field kosong, dll.) |
| `422` | Tabel `tb_warga_{account}` tidak ada di DB legacy (`errors.account`) |
| `401` | Username/password salah, atau `level` bukan `Pelanggan`, atau `status` bukan `1` |
| `429` | Terlalu banyak percobaan login |

**cURL**

```bash
curl -sS -X POST "https://api-ebilling-service.test/api/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"account\":\"1114\",\"username\":\"warga1\",\"password\":\"rahasia\"}"
```

Contoh respons validasi `422`:

```json
{
  "message": "The account field format is invalid.",
  "errors": {
    "account": [
      "The account field format is invalid."
    ]
  }
}
```

Contoh tabel tidak ada (`422`):

```json
{
  "message": "Tabel akun tidak ditemukan.",
  "errors": {
    "account": [
      "Tabel akun tidak ditemukan."
    ]
  }
}
```

Contoh kredensial salah (`401`):

```json
{
  "message": "Kredensial tidak valid."
}
```

---

## 3. Profil saya (`/api/me`)

**Request**

```http
GET /api/me HTTP/1.1
Host: api-ebilling-service.test
Accept: application/json
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Respons sukses `200`**  
Body mengikuti `WargaResource` (biasanya dibungkus kunci `data`):

```json
{
  "data": {
    "id_warga": 1,
    "id_pelanggan": "...",
    "nama_warga": "Contoh",
    "username": "warga1",
    "level": "Pelanggan",
    "status": "1",
    "status_langganan": "...",
    "email": null,
    "tlp": null
  }
}
```

**Respons `401`** — token hilang, kedaluwarsa, atau tidak valid.

**Respons `404`** — baris warga tidak lagi ada di `tb_warga_{account}` di legacy.

**cURL**

```bash
TOKEN="1|ganti_dengan_token_dari_login"

curl -sS -X GET "https://api-ebilling-service.test/api/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}"
```

---

## 4. Daftar pelanggan (`/api/pelanggan`)

Mengembalikan semua baris dengan **`level = Pelanggan`** di tabel legacy `tb_warga_{account}`, di mana **`account` diambil dari user token** (field `account` pada pengguna yang login), **bukan** dari query string — parameter `account` di URL diabaikan untuk keamanan multi-tenant.

**Request**

```http
GET /api/pelanggan?page=1&per_page=15 HTTP/1.1
Host: api-ebilling-service.test
Accept: application/json
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Query (opsional)**

| Param | Tipe | Keterangan |
|-------|------|------------|
| `page` | integer | Halaman, min `1` |
| `per_page` | integer | Default `15`, min `1`, maks `100` |

**Respons sukses `200`** — JSON terpaginasi Laravel (kumpulan `WargaResource` di `data`, plus `links` dan `meta`):

```json
{
  "data": [
    {
      "id_warga": 1,
      "nama_warga": "Contoh",
      "username": "warga1",
      "level": "Pelanggan",
      "id_sales": null,
      "nama_sales": null
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

| HTTP | Kondisi |
|------|---------|
| `401` | Tanpa / token tidak valid |
| `403` | Token tidak punya scope `account:{account}` (jarang, jika token dimanipulasi) |
| `404` | Tabel `tb_warga_{account}` tidak ada di legacy untuk tenant user |
| `422` | `per_page` melebihi 100 atau validasi query gagal |

**cURL**

```bash
curl -sS -G "https://api-ebilling-service.test/api/pelanggan" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  --data-urlencode "per_page=15" \
  --data-urlencode "page=1"
```

---

## 5. Daftar order/instalasi pelanggan baru (`/api/instalasi-pelanggan-baru`)

Membaca baris dari tabel legacy **`tb_laporan_pelanggan`** (shared, satu tabel untuk semua tenant) dengan filter:

- **`account`**: nilai tenant diambil dari **user token** (bukan query string); parameter `account` di URL diabaikan.
- **`jns_laporan`**: salah satu dari `Installasi Baru`, `Survey Baru`, `New Regist`.
- **`status`**: bukan `Closed`, `Pemasangan Berhasil Dilakukan`, atau `Cancel`.

Urutan default: **`waktu_pembuatan` DESC**.

**Request**

```http
GET /api/instalasi-pelanggan-baru?page=1&per_page=15 HTTP/1.1
Host: api-ebilling-service.test
Accept: application/json
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Query (opsional)**

| Param | Tipe | Keterangan |
|-------|------|------------|
| `page` | integer | Halaman, min `1` |
| `per_page` | integer | Default `15`, min `1`, maks `100` |

**Respons sukses `200`** — JSON terpaginasi Laravel; setiap item di `data` memuat **semua kolom** baris `tb_laporan_pelanggan` (mirror `SELECT *`), plus `links` dan `meta`:

```json
{
  "data": [
    {
      "id_laporan_pelanggan": 1,
      "jns_laporan": "Installasi Baru",
      "status": "Open",
      "account": 1114,
      "waktu_pembuatan": "2025-01-15T08:00:00.000000Z"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

| HTTP | Kondisi |
|------|---------|
| `401` | Tanpa / token tidak valid |
| `403` | Token tidak punya scope `account:{account}` |
| `422` | `per_page` melebihi 100 atau validasi query gagal |

**cURL**

```bash
curl -sS -G "https://api-ebilling-service.test/api/instalasi-pelanggan-baru" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  --data-urlencode "per_page=15" \
  --data-urlencode "page=1"
```

---

## 6. Daftar pembayaran pelanggan (`/api/pembayaran-pelanggan`)

Membaca baris dari tabel legacy **`tb_iuran_{account}`**, di mana **`account` diambil dari user token**, bukan dari query string (parameter `account` di URL diabaikan untuk keamanan multi-tenant).

**Default filter periode:** baris dengan `wkt_entry` pada **bulan kalender berjalan** (00:00:00 tanggal 1 sampai 23:59:59 tanggal terakhir bulan itu). Filter dapat di-override (saling eksklusif):

- **`from` + `to`**: rentang tanggal inklusif (format `YYYY-MM-DD`), `wkt_entry` antara `from 00:00:00` dan `to 23:59:59`.
- **`bulan`**: satu bulan penuh (format `YYYY-MM`).

Urutan default: **`id_ipl` DESC**.

**Kolom nomor urut (“No”) di UI:** hitung di client: untuk indeks baris ke-`i` (0-based) pada halaman saat ini, **No = `meta.from + i`** (pakai field `meta.from` dari respons terpaginasi Laravel).

**Request**

```http
GET /api/pembayaran-pelanggan?page=1&per_page=15 HTTP/1.1
Host: api-ebilling-service.test
Accept: application/json
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Query (opsional)**

| Param | Tipe | Keterangan |
|-------|------|------------|
| `page` | integer | Halaman, min `1` |
| `per_page` | integer | Default `15`, min `1`, maks `100` |
| `from` | string `YYYY-MM-DD` | Awal rentang (wajib bersama `to`; tidak boleh bersama `bulan`) |
| `to` | string `YYYY-MM-DD` | Akhir rentang (wajib bersama `from`; harus ≥ `from`; tidak boleh bersama `bulan`) |
| `bulan` | string `YYYY-MM` | Satu bulan penuh (tidak boleh bersama `from`/`to`) |

**Respons sukses `200`** — JSON terpaginasi Laravel; setiap item di `data` memuat field berikut:

| Field JSON | Sumber kolom DB (`tb_iuran_{account}`) |
|------------|----------------------------------------|
| `id_ipl` | `id_ipl` |
| `id_pelanggan` | `id_pelanggan` |
| `nama_pelanggan` | `nama_warga` |
| `nama_sales` | `nama_sales` |
| `nama_pembayaran` | `nama_tipe` |
| `nominal_harus_dibayar` | `harga` |
| `nominal_pembayaran` | `jumlah_bayar` |
| `status_pembayaran` | `status_transaksi` |
| `alamat` | `alamat` |
| `tlp` | `tlp` |
| `lokasi` | `id_lokasi` |
| `bukti_pembayaran` | `foto` |
| `periode_pembayaran` | `bayar_bulan` |
| `metode_pembayaran` | `nama_rekening` |
| `waktu_entry` | `wkt_entry` |
| `keterangan` | `keterangan` |
| `metode_insentif` | `metode_insentif` |
| `insentif` | `insentif_sales` |
| `nominal_insentif` | `nominal_insentif` |

```json
{
  "data": [
    {
      "id_ipl": 1,
      "id_pelanggan": "PLG-1",
      "nama_pelanggan": "Contoh",
      "nama_sales": "Sales",
      "nama_pembayaran": "Internet",
      "nominal_harus_dibayar": 100000,
      "nominal_pembayaran": 100000,
      "status_pembayaran": "Selesai",
      "alamat": null,
      "tlp": null,
      "lokasi": 5,
      "bukti_pembayaran": null,
      "periode_pembayaran": "2025-03-01",
      "metode_pembayaran": "BCA",
      "waktu_entry": "2025-03-10T03:00:00.000000Z",
      "keterangan": "",
      "metode_insentif": null,
      "insentif": null,
      "nominal_insentif": null
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

| HTTP | Kondisi |
|------|---------|
| `401` | Tanpa / token tidak valid |
| `403` | Token tidak punya scope `account:{account}` |
| `404` | Tabel `tb_iuran_{account}` tidak ada di legacy untuk tenant user |
| `422` | `per_page` melebihi 100, `from`/`to`/`bulan` tidak valid, atau kombinasi query tidak diizinkan |

**cURL**

```bash
curl -sS -G "https://api-ebilling-service.test/api/pembayaran-pelanggan" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  --data-urlencode "per_page=15" \
  --data-urlencode "page=1"
```

Contoh filter bulan tertentu:

```bash
curl -sS -G "https://api-ebilling-service.test/api/pembayaran-pelanggan" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  --data-urlencode "bulan=2025-03"
```

---

## 7. Hello World (uji token)

**Request**

```http
GET /api/hello-world HTTP/1.1
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json
```

**Respons sukses `200`**

```json
{
  "message": "Hello World",
  "account": "1114",
  "id_warga_legacy": 1
}
```

**cURL**

```bash
curl -sS -X GET "https://api-ebilling-service.test/api/hello-world" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}"
```

---

## 8. Logout

**URL:** `POST /api/logout`

**Request**

```http
POST /api/logout HTTP/1.1
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json
```

**Respons sukses `204`** — tanpa body.

**cURL**

```bash
curl -sS -i -X POST "https://api-ebilling-service.test/api/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}"
```

Setelah logout, token yang sama tidak boleh dipakai lagi (`401` pada `/api/me`).

---

## 9. Status pelanggan (`/api/status-pelanggan`)

Mengembalikan **status turunan** satu pelanggan (baris `level = Pelanggan`) di `tb_warga_{account}` berdasarkan kolom **`status`** dan **`status_langganan`**. **`account` diambil dari token**, bukan query.

**Pencocokan `status_langganan`** bersifat **case-insensitive** (nilai DB enum `On` / `Off` setara dengan aturan `on` / `off`).

| `status_pelanggan` | Kondisi (setelah normalisasi) |
|--------------------|------------------------------|
| `ACTIVE` | `status = '1'` dan langganan `on` |
| `SUSPENDED` | `status = '1'` dan langganan `off` |
| `DISMANTLE` | `status = '0'` dan langganan `off` |
| `UNKNOWN` | Kombinasi lain (mis. `status = '2'`, atau `status = '0'` dengan langganan `on`) |

**Request**

```http
GET /api/status-pelanggan?id_warga=1 HTTP/1.1
Host: api-ebilling-service.test
Accept: application/json
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Query (wajib)**

| Param | Tipe | Keterangan |
|-------|------|------------|
| `id_warga` | integer | Wajib, primary key baris di `tb_warga_{account}`; min `1` |

**Respons sukses `200`**

```json
{
  "data": {
    "id_warga": 1,
    "id_pelanggan": "PLG-1",
    "nama_warga": "Contoh",
    "account": "1114",
    "status": "1",
    "status_langganan": "On",
    "status_pelanggan": "ACTIVE"
  }
}
```

| HTTP | Kondisi |
|------|---------|
| `401` | Tanpa / token tidak valid |
| `403` | Token tidak punya scope `account:{account}` |
| `404` | Tabel `tb_warga_{account}` tidak ada di legacy, atau tidak ada baris `Pelanggan` dengan `id_warga` tersebut |
| `422` | `id_warga` tidak dikirim, bukan integer, atau bernilai kurang dari `1` |
| `429` | Rate limit |

**cURL**

```bash
curl -sS -G "https://api-ebilling-service.test/api/status-pelanggan" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  --data-urlencode "id_warga=1"
```

---

## Konfigurasi server (.env)

| Variabel | Keterangan |
|----------|------------|
| `APP_URL` | URL publik aplikasi |
| `DB_*` | Database **lokal** (Sanctum, `warga_accounts`, cache, dll.) |
| `DB_LEGACY_*` | Koneksi ke DB billing lama (`tb_warga_{account}`, `tb_iuran_{account}`, `tb_laporan_pelanggan`, dll.) |
| `SANCTUM_TOKEN_INACTIVITY_TTL_MINUTES` | (Opsional) Berapa menit token API boleh tidak dipakai sebelum dianggap kedaluwarsa; default `1440` (24 jam). Setiap request API yang sukses memperpanjang masa berlaku. |

---

## Alur client singkat

1. `POST /api/login` → simpan `token`.
2. Panggil `GET /api/me`, `GET /api/pelanggan`, `GET /api/status-pelanggan`, `GET /api/instalasi-pelanggan-baru`, `GET /api/pembayaran-pelanggan`, dst. dengan header `Authorization: Bearer {token}`.
3. `POST /api/logout` saat selesai (opsional).

---

*Dokumen ini diselaraskan dengan kode di `routes/api.php` dan controller terkait (termasuk `StatusPelangganController`).*
