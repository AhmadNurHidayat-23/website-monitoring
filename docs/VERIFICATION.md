# Catatan verifikasi

Dilakukan pada 3 November 2026 (WIB), pada macOS dengan PHP 8.5.12, Laravel 13.39.0, Node.js 26.12.1, dan SQLite.

## Pengujian otomatis

- `php artisan test`: **64 pengujian, 156 assertion lulus**.
- PHP MAMP 8.5.2: seluruh 64 pengujian juga lulus dan koneksi nyata menerima HTTP 200.
- `npm run build`: berhasil membangun aset Vite produksi.
- `composer validate --no-check-publish`: valid; lockfile sesuai dependensi.
- Semua file source aplikasi kurang dari 300 baris; lockfile, vendor, cache, dan hasil build bukan source aplikasi.

Cakupan mencakup CRUD, validasi, escaping nama, pagination, statistik aktual, pemisahan riwayat setelah URL berubah, rate limit, lock target, penjadwalan interval, HTTP 200/503, timeout, connection error, redirect, DNS rebinding, IPv4/IPv6 khusus, proxy, TLS, dan batas body termasuk penulisan bertahap. Transport cURL dibentuk tanpa mengirim request dalam pengujian kompatibilitas. Seluruh pengujian otomatis menggunakan mock HTTP dan DNS; tidak memerlukan akses ke website eksternal.

## Pemeriksaan nyata

`php artisan schedule:list` menampilkan `monitor:check` setiap menit. `php artisan schedule:run` berhasil mengeksekusi command. Dua eksekusi `monitor:check --force` juga selesai, masing-masing memeriksa tiga target.

Target lokal yang ditambahkan melalui formulir:

| Target | Hasil terakhir saat screenshot | Durasi |
| --- | --- | --- |
| https://example.com | HTTP 200 / Up | 127 ms |
| https://laravel.com | HTTP 200 / Up | 604 ms |
| https://www.php.net | HTTP 200 / Up | 107 ms |

Angka tersebut adalah hasil aktual saat pengujian, bukan benchmark atau jaminan status saat ini. Riwayat Example juga menyimpan satu kegagalan transport selama implementasi; catatan tidak diubah atau diganti data fiktif. Database lokal tidak masuk repository.

## Browser

- CRUD tambah dan tampilan detail diverifikasi melalui formulir nyata.
- URL `http://127.0.0.1` ditolak dengan pesan validasi Indonesia.
- Halaman utama diperiksa pada lebar 320, 390, 768, 1024, dan 1280 piksel.
- Detail dan formulir diperiksa pada mobile; tidak ada horizontal overflow.
- Headline utama satu baris pada desktop dan tablet.
- Warna seluruh teks yang diperiksa adalah `rgb(17, 17, 17)`; aksen royal blue terbatas pada fungsi visual.
- Grafik memiliki jumlah titik sama dengan data pemeriksaan yang disematkan dari server.
- Tidak ada error atau warning JavaScript pada halaman yang diperiksa.

Screenshot asli tersedia di `docs/images/`. Screenshot hanya merekam UI lokal dengan target publik; tidak ada credential di dalamnya.

## Batas verifikasi

Mock menguji kondisi gagal secara deterministik. Pemeriksaan nyata hanya berasal dari mesin lokal, bukan pengujian multiwilayah atau audit keamanan independen. CI GitHub disertakan; status eksekusinya dapat dilihat di tab Actions repository.
