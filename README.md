# Fleet & Rental System

Aplikasi internal untuk mengelola unit kendaraan dan pemesanan sewa berbasis kalender WIB. Fitur utama mencakup master data merk/tipe, kendaraan, pelanggan, booking rental, validasi overlap, harga dalam USD cents, diskon otomatis, dan riwayat lifecycle.

## Menjalankan aplikasi

### Prasyarat

- PHP 8.3 atau lebih baru
- Composer
- Node.js dan npm
- PostgreSQL

### 1. Siapkan database dan environment

Buat database PostgreSQL, misalnya `fleet_rental`, lalu salin environment example:

```bash
cp .env.example .env
```

Isi konfigurasi berikut di `.env` dengan kredensial PostgreSQL lokal Anda. Jangan commit file `.env`.

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fleet_rental
DB_USERNAME=postgres_username
DB_PASSWORD=postgres_password

ADMIN_USERNAME=admin@example.test
ADMIN_PASSWORD=password_admin_anda
```

### 2. Install dependency

```bash
composer install
npm install
```

### 3. Generate key dan siapkan database

```bash
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

`migrate --seed` membuat seluruh tabel aplikasi, termasuk tabel `sessions` dan `cache`, membuat Admin dari nilai environment, serta menyediakan master data awal:

```text
Toyota: Avanza, Innova
Honda: Brio, CR-V, Civic
```

Untuk mengulang database **lokal** dari awal:

```bash
php artisan migrate:fresh --seed
```

> Perintah tersebut menghapus seluruh data database yang sedang dipakai. Jangan gunakan pada database production.

### 4. Jalankan server

Terminal pertama:

```bash
php artisan serve
```

Terminal kedua:

```bash
npm run dev
```

Aplikasi tersedia di `http://localhost:8000`. Root URL akan mengarahkan ke halaman login. Masuk menggunakan `ADMIN_USERNAME` dan `ADMIN_PASSWORD` dari `.env`.

Vite digunakan untuk memproses asset Blade/Tailwind dan interaksi frontend seperti preview availability, dependent dropdown Merk → Tipe, serta rincian harga rental. Untuk build production:

```bash
npm run build
```

### 5. Menjalankan test

```bash
composer test
composer validate --no-check-publish
```

## Arsitektur

Saya memilih untuk mengguakan framework laravel modular monolith dengan PostgreSQL, Blade, Tailwind, dan Vite karena kebutuhan assessment berpusat pada workflow Admin internal, formulir, tabel, serta transaksi rental yang harus konsisten. Satu aplikasi server-rendered menjaga deployment tetap sederhana, sementara PostgreSQL mendukung transaction dan penguncian data kendaraan saat validasi booking dilakukan.

Struktur kode mengikuti layer Domain, Application, Infrastructure, dan Presentation. Domain menyimpan aturan murni seperti normalisasi plat, tanggal inclusive, overlap, dan pricing; Application menangani use case serta transaction boundary; Infrastructure menangani Eloquent/PostgreSQL; Presentation berisi controller, route, Blade, dan validasi request. Pemisahan ini menjaga controller tidak menjadi tempat aturan bisnis dan membuat formula maupun rejection path mudah diuji.

### Prinsip clean code dan trade-off

Penulisan kode mengutamakan nama yang menjelaskan maksud, tanggung jawab class yang terfokus, dependency injection melalui contract, value object untuk aturan domain, serta controller yang tipis. Business rule utama ditempatkan di Domain atau Application, sedangkan detail Laravel, Eloquent, dan PostgreSQL dibatasi di Infrastructure dan Presentation. Test unit digunakan untuk aturan murni, sementara test feature/integration digunakan untuk memastikan transaction, persistence, dan rejection path berjalan sesuai requirement.

Karena waktu assessment terbatas, implementasi ini memprioritaskan alur utama Admin dan correctness pada workflow rental dibanding refactoring menyeluruh. Beberapa area masih merupakan technical debt yang diketahui: sebagian contract masih menggunakan `mixed` atau array, business rule blocking rental perlu lebih tersentralisasi, error mapping ke field perlu diperjelas, dan coverage integration untuk seluruh lifecycle serta concurrency masih perlu diperluas. Trade-off ini didokumentasikan secara eksplisit agar batas antara clean-code intent dan pekerjaan lanjutan tetap jelas; penambahan fitur berikutnya sebaiknya tidak memperluas technical debt tersebut.

## Logika overlap dan harga rental

Tanggal rental memakai kalender WIB dan bersifat inclusive. Karena itu rental dari 26 sampai 27 Agustus dihitung sebagai dua hari: 26 dan 27 sama-sama termasuk periode sewa. Kendaraan yang sama ditolak bila rentang yang diminta memenuhi rumus berikut terhadap rental yang memblokir:

```text
requested_start <= existing_effective_end
AND requested_end >= existing_start
```

Tanggal yang bertemu di boundary tetap dianggap overlap. Preview ketersediaan hanya membantu UI; validasi yang sama diulang saat simpan di dalam transaction, sehingga request yang dimanipulasi tetap ditolak. Rental booked dan active memblokir kendaraan; kendaraan archive tidak dapat dipilih untuk rental baru.

Harga memakai integer USD cents. Durasi dihitung dengan `(end_date - start_date) + 1`; rental lebih dari 7 hari mendapatkan diskon 10%, dengan pembulatan half-up untuk hasil akhir. UI menampilkan rincian transparan: durasi, tarif harian, subtotal, diskon, dan total. Snapshot harga disimpan saat booking agar perubahan tarif kendaraan di masa depan tidak mengubah rental historis.

## Penggunaan AI

AI digunakan sebagai pair-programming assistant untuk membantu pembuatan backend Laravel, UI Blade/Tailwind, test, review, dan dokumentasi. Namun aturan bisnis tidak dibuat secara bebas oleh AI: alur kerja selalu dimulai dari [`AGENTS.md`](AGENTS.md), kemudian requirement utama dibaca dari [`docs/PRD.md`](docs/PRD.md).

Setelah requirement dipahami, keputusan domain dan arsitektur dirujuk melalui [`docs/domain/`](docs/domain/), [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md), serta [`docs/ADR/`](docs/ADR/). Saya tetap memeriksa hasil AI melalui test, review, migration, dan pengujian browser end-to-end.

## Referensi penting

- [`docs/PRD.md`](docs/PRD.md) — product requirement baseline
- [`docs/domain/fleet-rental-domain-semantics.md`](docs/domain/fleet-rental-domain-semantics.md) — aturan domain
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — arsitektur dan batas layer
