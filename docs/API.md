# Dokumentasi API e-Billing (Warga)

Ringkasan endpoint, header, body, dan contoh `curl`. Semua path di bawah prefix **`/api`**.

**Base URL contoh lokal:** `https://api-ebilling-service.test`  
Ganti sesuai environment Anda.

---

## Ringkas multi-tenant

- **`account`**: kode tenant (hanya huruf, angka, `_`, `-`). Data warga dibaca dari tabel legacy **`tb_warga_{account}`** (contoh: `tb_warga_1114`).
- **Login** hanya untuk **pelanggan**: kolom **`level`** harus persis **`Pelanggan`**, kolom **`status`** harus **`1`** (string, sesuai `where` di kode), plus `username` + `password` (plain text) cocok.
- **Token**: Laravel Sanctum. Kirim **`Authorization: Bearer {token}`** untuk endpoint yang membutuhkan auth.

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
| `POST` | `/api/login` | Tidak | Login (alias) |
| `POST` | `/api/auth/login` | Tidak | Login (kanonik) |
| `POST` | `/api/logout` | Bearer | Hapus token saat ini |
| `POST` | `/api/auth/logout` | Bearer | Sama seperti di atas |
| `GET` | `/api/me` | Bearer | Profil warga dari DB legacy |
| `GET` | `/api/hello-world` | Bearer | Contoh endpoint terproteksi |

**Rate limit login:** maksimal **5** percobaan per menit per IP (path login).

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

**URL (pilih salah satu)**

- `POST /api/login`
- `POST /api/auth/login`

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

## 4. Hello World (uji token)

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

## 5. Logout

**URL (pilih salah satu)**

- `POST /api/logout`
- `POST /api/auth/logout`

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

## Konfigurasi server (.env)

| Variabel | Keterangan |
|----------|------------|
| `APP_URL` | URL publik aplikasi |
| `DB_*` | Database **lokal** (Sanctum, `warga_accounts`, cache, dll.) |
| `DB_LEGACY_*` | Koneksi ke DB billing lama (`tb_warga_{account}`) |

---

## Alur client singkat

1. `POST /api/login` → simpan `token`.
2. Panggil `GET /api/me` (atau endpoint lain) dengan header `Authorization: Bearer {token}`.
3. `POST /api/logout` saat selesai (opsional).

---

*Dokumen ini diselaraskan dengan kode di `routes/api.php` dan controller terkait.*
