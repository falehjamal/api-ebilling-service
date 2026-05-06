# Dokumentasi API e-Billing (Warga)

Ringkasan endpoint, header, body, dan contoh `curl`. Semua path di bawah prefix **`/api`**.

**Base URL contoh lokal:** `https://api-ebilling-service.test`  
Ganti sesuai environment Anda.

**Dokumentasi interaktif (OpenAPI):** `GET /docs/api` (UI), `GET /docs/api.json` (spesifikasi JSON). Panduan ini (`docs/API.md`) berisi penjelasan lengkap dan contoh permintaan.

---

## Ringkas multi-tenant

- **`account`**: kode wilayah/tenant (hanya huruf, angka, `_`, `-`, maks. 64 karakter). Menentukan lingkup data yang dipakai bersama token Anda.
- **Login** hanya untuk **akun pelanggan** yang diizinkan layanan: kombinasi `username` dan `password` harus valid; akun harus memenuhi syarat status pelanggan aktif sesuai kebijakan backend (bukan penyedia atau jenis user lain).
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
| `GET` | `/api/me` | Bearer | Profil pengguna (pelanggan) yang sedang login |
| `GET` | `/api/pelanggan` | Bearer | Daftar pelanggan tenant Anda, terpaginasi |
| `GET` | `/api/lokasi` | Bearer | Daftar lokasi tenant Anda (`tb_lokasi`), terpaginasi |
| `GET` | `/api/instalasi-pelanggan-baru` | Bearer | Daftar order/instalasi/registrasi baru yang relevan, terpaginasi |
| `GET` | `/api/pembayaran-pelanggan` | Bearer | Daftar pembayaran pelanggan tenant Anda, terpaginasi |
| `GET` | `/api/status-pelanggan` | Bearer | Status ringkas satu pelanggan (`ACTIVE` / `SUSPENDED` / `DISMANTLE` / `UNKNOWN`) |
| `GET` | `/api/hello-world` | Bearer | Contoh endpoint terproteksi |

**Rate limit:** `POST /api/login` = **5** request per menit per IP. `GET /api/pelanggan`, `GET /api/lokasi`, `GET /api/instalasi-pelanggan-baru`, `GET /api/pembayaran-pelanggan`, dan `GET /api/status-pelanggan` = **60** request per menit per IP (throttle Laravel; nilai dapat disesuaikan di rute).

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
| `username` | string | Ya | Nama pengguna untuk tenant `account` |
| `password` | string | Ya | Kata sandi (teks biasa, sesuai yang terdaftar) |

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

Objek `warga` hanya berisi field yang diizinkan untuk dikirim ke klien (bukan seluruh atribut akun).

Masa berlaku token mengikuti kebijakan **sliding**; default sekitar **24 jam** tanpa aktivitas. Variabel `.env`: `SANCTUM_TOKEN_INACTIVITY_TTL_MINUTES` (menit).

**Respons gagal (contoh)**

| HTTP | Kondisi |
|------|---------|
| `422` | Validasi gagal (format `account`, field kosong, dll.) |
| `422` | Tenant tidak dikenal / tidak tersedia (lihat respons, biasanya melalui `errors.account`) |
| `401` | Username atau password salah, atau `status` akun tidak aktif (`status` ≠ `1`) |
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

Contoh tenant tidak ditemukan (`422`):

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
Body berupa objek profil (biasanya dibungkus kunci `data`):

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

**Respons `404`** — profil tidak lagi tersedia untuk pengguna ini (pesan API mis.: *Data warga tidak ditemukan di sistem lama.*).

**cURL**

```bash
TOKEN="1|ganti_dengan_token_dari_login"

curl -sS -X GET "https://api-ebilling-service.test/api/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}"
```

---

## 4. Daftar pelanggan (`/api/pelanggan`)

Mengembalikan pelanggan untuk **tenant yang sama dengan token Anda**. Parameter `account` di URL **diabaikan** — tenant selalu dari token (keamanan multi-tenant).

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

**Respons sukses `200`** — JSON terpaginasi Laravel (`data`, `links`, `meta`):

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
| `404` | Data tenant tidak tersedia untuk pengguna ini (pesan API mis.: *Data tenant tidak ditemukan.*) |
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

## 5. Daftar lokasi (`/api/lokasi`)

Data lokasi (`tb_lokasi`) untuk **tenant yang sama dengan token Anda**. Filter baris memakai kolom `account`. Parameter `account` di URL **diabaikan** — tenant selalu dari token.

Urutan: **`id_lokasi` menurun** (ID lebih besar lebih baru).

**Request**

```http
GET /api/lokasi?page=1&per_page=15 HTTP/1.1
Host: api-ebilling-service.test
Accept: application/json
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Query (opsional)**

| Param | Tipe | Keterangan |
|-------|------|------------|
| `page` | integer | Halaman, min `1` |
| `per_page` | integer | Default `15`, min `1`, maks `100` |

**Respons sukses `200`** — JSON terpaginasi Laravel; setiap elemen `data` memuat minimal field berikut:

| Field | Keterangan |
|-------|------------|
| `id_lokasi` | Identitas lokasi |
| `account` | Tenant (angka, selaras dengan token) |
| `nama_lokasi` | Nama lokasi |
| `alamat_lokasi` | Alamat teks |
| `tlp_lokasi` | Telepon lokasi |
| `group_wa` | Grup WA (jika ada) |
| `id_pic`, `nama_pic` | PIC lokasi |
| `kode_lokasi` | Kode lokasi |
| `account_wagw` | Akun terkait WA |
| `insentif_sales`, `metode_insentif`, `nominal_insentif` | Insentif (jika ada) |
| `filter_lokasi`, `jns_lokasi` | Filter / jenis lokasi |
| `id_cabang`, `nama_cabang` | Cabang |
| `id_referensi_corcab`, `nama_referensi_corcab` | Referensi Corcab |
| `provinsi`, `kabupaten`, `kecamatan`, `kelurahan`, `rt`, `rw` | Wilayah |

```json
{
  "data": [
    {
      "id_lokasi": 19968,
      "account": 6720,
      "nama_lokasi": "Renaldo Gunawan",
      "alamat_lokasi": "Bukit Pamulang Indah …",
      "tlp_lokasi": "087888911505",
      "jns_lokasi": "POP"
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
curl -sS -G "https://api-ebilling-service.test/api/lokasi" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${TOKEN}" \
  --data-urlencode "per_page=15" \
  --data-urlencode "page=1"
```

---

## 6. Daftar order/instalasi pelanggan baru (`/api/instalasi-pelanggan-baru`)

Data order dan progres terkait **instalasi baru**, **survey baru**, atau **registrasi baru** untuk tenant Anda, dengan status yang belum dianggap selesai atau dibatalkan. **Tenant dari token**, bukan query.

Urutan: pembuatan terbaru lebih dulu.

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

**Respons sukses `200`** — JSON terpaginasi; setiap elemen `data` memuat properti sesuai struktur data laporan (banyak field), plus `links` dan `meta`:

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

## 7. Daftar pembayaran pelanggan (`/api/pembayaran-pelanggan`)

Daftar pembayaran untuk **tenant token Anda**; parameter `account` di URL **diabaikan**.

**Filter waktu (default):** entri pada **bulan kalender berjalan**. Bisa diganti (saling eksklusif):

- **`from` + `to`**: rentang tanggal inklusif (`YYYY-MM-DD`), berdasarkan waktu pencatatan entri.
- **`bulan`**: satu bulan penuh (`YYYY-MM`).

Urutan: **`id_ipl` menurun** (transaksi dengan identitas lebih besar lebih baru).

**Nomor urut di UI:** pada halaman saat ini, untuk indeks `i` (mulai 0): **No = `meta.from + i`**.

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

**Respons sukses `200`** — setiap item di `data` memuat field berikut:

| Field | Keterangan (bisnis) |
|-------|---------------------|
| `id_ipl` | Identitas unik baris pembayaran |
| `id_pelanggan` | Kode/referensi pelanggan |
| `nama_pelanggan` | Nama pelanggan |
| `nama_sales` | Nama sales (jika ada) |
| `nama_pembayaran` | Jenis/nama paket atau pembayaran |
| `nominal_harus_dibayar` | Nominal yang seharusnya dibayar |
| `nominal_pembayaran` | Nominal yang dibayarkan |
| `status_pembayaran` | Status penyelesaian transaksi |
| `alamat` | Alamat (jika tersedia) |
| `tlp` | Telepon |
| `lokasi` | Referensi lokasi |
| `bukti_pembayaran` | Referensi bukti (jika ada) |
| `periode_pembayaran` | Periode tagihan yang dibayar |
| `metode_pembayaran` | Metode atau rekening pembayaran |
| `waktu_entry` | Waktu entri transaksi |
| `keterangan` | Catatan tambahan |
| `metode_insentif` | Metode insentif (jika ada) |
| `insentif` | Insentif terkait |
| `nominal_insentif` | Nominal insentif |

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
| `404` | Data pembayaran tenant tidak tersedia (pesan API mis.: *Data tenant tidak ditemukan.*) |
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

## 8. Hello World (uji token)

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

## 9. Logout

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

## 10. Status pelanggan (`/api/status-pelanggan`)

Mengembalikan **satu pelanggan** (menggunakan `id_warga` dari daftar pelanggan, mis. `GET /api/pelanggan`) beserta **status ringkas** `status_pelanggan`. Nilai dihitung dari kombinasi **`status`** dan **`status_langganan`** pada respons (bukan detail implementasi server).

**Nilai `status_langganan`** dipadankan **tanpa membedakan huruf besar/kecil** (mis. `On` dan `on` setara).

**Makna `status_pelanggan` (ringkas):**

| Nilai | Uraian untuk klien |
|-------|-------------------|
| `ACTIVE` | Pelanggan dalam kondisi aktif berlangganan |
| `SUSPENDED` | Pelanggan dianggap aktif namun langganan nonaktif / ditangguhkan |
| `DISMANTLE` | Pelanggan tidak aktif dan langganan berhenti |
| `UNKNOWN` | Kombinasi status tidak mengikuti pola di atas atau tidak dikenali |

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
| `id_warga` | integer | Wajib; identitas pelanggan (sesuai `id_warga` dari daftar pelanggan); min `1` |

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
| `404` | Tenant tidak tersedia (*Data tenant tidak ditemukan.*) atau pelanggan tidak ada (*Pelanggan tidak ditemukan.*) |
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
| `DB_*` | Database aplikasi (sesi, penyimpanan token, akun sinkron, cache, dll.) |
| `DB_LEGACY_*` | Koneksi ke sistem data tagihan/organisasi yang menjadi sumber transaksi dan master pelanggan |
| `SANCTUM_TOKEN_INACTIVITY_TTL_MINUTES` | (Opsional) Berapa menit token API boleh tidak dipakai sebelum dianggap kedaluwarsa; default `1440` (24 jam). Setiap request API yang sukses memperpanjang masa berlaku. |

---

## Alur client singkat

1. `POST /api/login` → simpan `token`.
2. Panggil `GET /api/me`, `GET /api/pelanggan`, `GET /api/lokasi`, `GET /api/status-pelanggan`, `GET /api/instalasi-pelanggan-baru`, `GET /api/pembayaran-pelanggan`, dst. dengan header `Authorization: Bearer {token}`.
3. `POST /api/logout` saat selesai (opsional).

---

Dokumen ini menggambarkan endpoint publik di bawah prefix `/api`.
