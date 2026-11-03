# Website Monitoring

Aplikasi lokal untuk memantau ketersediaan website, mengukur waktu respons, dan menelusuri riwayat gangguan. Portofolio Laravel dengan data nyata, MVC, dan desain Swiss editorial: off-white, royal blue, serta Helvetica Neue hitam.

## Fitur

- Tambah, edit, dan hapus website dengan validasi server serta CSRF.
- Status Up, Down, atau Belum Diperiksa, kode HTTP, durasi, dan waktu pemeriksaan.
- Riwayat SQLite dengan pagination, grafik SVG dari 40 pemeriksaan terakhir, serta persentase keberhasilan.
- Pemeriksaan manual dengan rate limit dan pemeriksaan otomatis sesuai interval.
- Penanganan timeout, kegagalan koneksi, sertifikat TLS, redirect, dan respons terlalu besar.
- Kunci per website yang digunakan bersama oleh scheduler, pemeriksaan manual, edit, dan hapus.

## Teknologi

Laravel 13, Blade, SQLite, Laravel HTTP Client, Scheduler, Vite, HTML5, CSS3, dan JavaScript murni. Gunakan PHP 8.5, Composer 2, Node.js 22.12+ atau 24+, dan npm. PHP memerlukan cURL, OpenSSL, PDO SQLite, mbstring, XML, serta ekstensi standar Laravel.

## Instalasi dan menjalankan

```sh
git clone https://github.com/AhmadNurHidayat-23/website-monitoring.git
cd website-monitoring
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm ci
npm run build
php artisan serve --host=127.0.0.1
```

Buka http://127.0.0.1:8000. Klik **Tambah Website**, isi nama, URL HTTP/HTTPS publik, dan interval 1, 5, 10, 15, 30, atau 60 menit. Simpan, kemudian klik **Periksa Sekarang**. Detail menyediakan grafik, riwayat, edit, dan hapus. Database awal kosong; seluruh hasil berasal dari pemeriksaan nyata.

Di terminal terpisah, jalankan scheduler:

```sh
php artisan schedule:work
```

Scheduler mengevaluasi target setiap menit. Website diperiksa ketika intervalnya tercapai; respons 2xx berarti Up. Persentase keberhasilan adalah jumlah hasil Up dibagi seluruh pemeriksaan untuk URL saat ini. Mengganti URL mengosongkan ringkasan saat ini tanpa menghapus riwayat URL lama. Waktu menggunakan WIB.

## Pengujian

```sh
php artisan test
npm run build
```

Pengujian memakai mock HTTP/DNS dan SQLite sementara, tanpa layanan eksternal. Lihat [catatan verifikasi](docs/VERIFICATION.md).

## Keamanan dan batasan

Validasi menolak alamat internal dan DNS campuran. Koneksi cURL dikunci ke IP publik tervalidasi; proxy dinonaktifkan, TLS tetap diverifikasi, dan setiap redirect diperiksa ulang. Batas: tiga redirect, koneksi tiga detik, permintaan delapan detik per hop, dan respons satu MiB. Pemeriksaan manual dibatasi dua kali per website serta sepuluh kali per IP setiap menit.

Ini demo lokal tanpa autentikasi. Jangan membuka pengelolaan target ke internet. Kunci file ditujukan untuk satu host; instalasi banyak server memerlukan penyimpanan kunci bersama. Hasil mencerminkan sudut pandang mesin pemeriksa, bukan jaminan ketersediaan global. Lihat [rincian keamanan](docs/SECURITY.md).

Lisensi MIT.
