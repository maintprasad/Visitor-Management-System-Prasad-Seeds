# VMS Prasad Seeds Indonesia — Pasuruan (Panel Security)

Panel kontrol untuk tim security **Unit 1** dan **Unit 2** Pasuruan, versi modular PHP 8.5 (tanpa Composer).
Bekerja bersama dua aplikasi lain:

```
Visitor ──► 3. Check-In  ──┐   (QR code disimpan di HP visitor)
                           ├──►  4. Panel + API (folder ini)  ◄── Security Unit 1 / Unit 2
Visitor/Gate ► 2. Check-Out┘
```

Alur: visitor daftar di **Check-In** (status *Waiting Approval*) → dapat QR → security unit terkait
**approve** di panel (status *Inside*) → saat pulang QR di-scan di **Check-Out** (atau di panel).

## Pemisahan Unit 1 / Unit 2

- Setiap baris data (`visitors`, `equipment`, `absen_kontraktor`, `logs`) punya kolom `unit_id` (`U1` / `U2`).
- **Login terpisah per unit.** Akun `security` terkunci ke satu unit; unit diambil dari token di server,
  bukan dari kiriman browser, sehingga tidak bisa melihat/mengubah data unit lain.
- Akun `admin` boleh berpindah unit lewat pemilih unit di sidebar (data tetap dipisah, tidak digabung).
- App Check-In / Check-Out dikunci ke unit lewat URL: `...?unit=U1` atau `...?unit=U2` (cetak QR/poster berbeda per gerbang).
  Check-out visitor Unit 2 lewat app yang dikunci Unit 1 akan ditolak.

## Struktur

```
bootstrap.php            autoloader PSR-4 (namespace Vsm\) + .env
public/index.php         front controller lokal (Apache/XAMPP & php -S)
api/index.php            front controller Vercel
src/
  App.php                dispatcher /api + penentuan unit (Context)
  Core/                  Env, Request, Response(CORS), Token(HMAC), Context, Action, Module, Clock, Ids
  Database/              Connection, MysqlConnection, TursoConnection (HTTP), Database(factory), Migrator
  Modules/
    Auth/                LOGIN, ME, GET_UNITS, PING
    Visitors/            CHECKIN_VISITOR, CHECKOUT_VISITOR, GET_VISITOR, UPDATE_STATUS, DELETE_ROW, driver gate pass
    Equipment/           ADD_EQUIPMENT, EQUIPMENT_OUT
    Absen/               ADD_ABSEN_KONTRAKTOR
    Logs/                ADD_LOG, SCAN_QR
    Sync/                GET_SHEET_DATA, GET_SHEET_VERSION (urutan kolom = header database_vsm.xlsx)
views/                   layout.php + partials/ pages/ modals/ (HTML panel, dipecah dari index.html lama)
public/assets/css|js     CSS & JS modular (core/, pages/, actions/)
database/schema/         mysql.sql  dan  turso.sql  (struktur identik)
bin/                     migrate.php, create-user.php, import-xlsx.php
_legacy/index.gas.html   file asli versi Google Apps Script (arsip, boleh dihapus)
```

Protokol API sama dengan versi Apps Script: `POST {action, data}` → `{ok:true,...}` / `{ok:false,error}`;
`GET ?action=...`. Hanya `LOGIN`, `PING`, `GET_UNITS`, `GET_VISITOR`, `CHECKIN_VISITOR`, `CHECKOUT_VISITOR`,
`ADD_ABSEN_KONTRAKTOR` yang publik (dipakai app 2 & 3); sisanya wajib token login.

## Database (berdasarkan `database_vsm.xlsx`)

| Sheet Excel | Tabel / View | Catatan |
|---|---|---|
| Visitors | `visitors` | semua 37 kolom + `unit_id`, `updated_at`, `nama_personil` |
| Contractors | view `contractors` | turunan Visitors (tipe Contractor) — bukan tabel terpisah agar tidak ada data ganda |
| Drivers | view `drivers` | turunan Visitors (tipe Driver) |
| Equipment | `equipment` | kolom `Condition` → `item_condition` (kata terlarang di MySQL) |
| AbsenKontraktor | `absen_kontraktor` | |
| Logs | `logs` | |
| — | `units`, `users` | baru: unit & akun login |

Waktu disimpan sebagai teks ISO-8601 UTC (sama dengan Excel) di MySQL maupun Turso.

## A. Uji coba lokal (MySQL / XAMPP)

```bash
cd "4. Visitor-Management-System-Prasad-Seeds-main"
copy .env.example .env            # isi APP_KEY (string acak ≥ 32 karakter); DB_DRIVER=mysql
# buat database kosong "vsm_pasuruan" (phpMyAdmin), lalu:
php bin/migrate.php
php bin/create-user.php --username=security.u1 --name="Security Unit 1" --unit=U1 --password=...
php bin/create-user.php --username=security.u2 --name="Security Unit 2" --unit=U2 --password=...
php bin/create-user.php --username=admin --name="Administrator" --role=admin --unit=U1 --password=...
php bin/import-xlsx.php ../database_vsm.xlsx --unit=U1       # data lama -> unit yang Anda tentukan
```

Jalankan: `php -S localhost:8000 -t public public/index.php` → buka `http://localhost:8000/`
atau lewat Apache XAMPP: `http://localhost/vsm_pasuruan/4.%20Visitor-Management-System-Prasad-Seeds-main/public/`.

App 2 & 3: edit `config.js` masing-masing (`API_BASE`), lalu buka mis.
`http://localhost/vsm_pasuruan/3.%20Visitor-Check-In-Prasad-main/index.html?unit=U1`.

> `import-xlsx.php` memberi **semua** baris Excel ke satu unit (`--unit`). Jalankan sekali saja;
> `--wipe` menghapus dulu data unit tersebut. Nomor telepon dari Excel yang kehilangan angka 0 di depan (`8573…`) dipulihkan menjadi `08573…`.

## B. Deploy Vercel (Turso)

1. Buat database: `turso db create vsm-pasuruan`, lalu `turso db show vsm-pasuruan --url` dan `turso db tokens create vsm-pasuruan`.
2. Isi `.env` lokal sementara dengan `DB_DRIVER=turso`, `TURSO_DATABASE_URL`, `TURSO_AUTH_TOKEN`, lalu jalankan
   `php bin/migrate.php`, `php bin/create-user.php ...` dan `php bin/import-xlsx.php ...` (sama seperti di atas) — langsung mengisi Turso.
3. Di Vercel → Environment Variables: `DB_DRIVER=turso`, `TURSO_DATABASE_URL`, `TURSO_AUTH_TOKEN`, `APP_KEY`,
   `CORS_ORIGINS` (domain app Check-In/Out, atau `*`). Deploy folder ini (`vercel.json` sudah disiapkan).
4. App 2 & 3 adalah file statis: deploy sebagai project Vercel terpisah (atau hosting statis apa pun) dan ubah `API_BASE` di `config.js`
   ke `https://<project-panel>.vercel.app/api`.

PHP di Vercel memakai runtime komunitas (`vercel-php`); cek versi runtime & PHP terbaru yang didukungnya sebelum deploy.

## Pengaturan lain (`.env`)

| Variabel | Fungsi |
|---|---|
| `TOKEN_TTL_HOURS` | lama sesi login (default 12 jam) |
| `PUBLIC_ACTIVE_LIST` | `true` untuk mengaktifkan tab "Daftar" di app Check-Out (menampilkan nama visitor yang sedang di dalam tanpa login) — default mati |
| `APP_BASE_PATH` | prefix URL bila deteksi subfolder otomatis salah |
| `APP_DEBUG` | `true` menampilkan pesan error asli di respons API |
