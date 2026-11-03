# Keamanan pemeriksaan HTTP

## Alur koneksi

1. `WebsiteRequest` meminta `PublicTarget` memeriksa URL dan DNS ketika target disimpan.
2. `WebsiteMonitor` mengulangi validasi sebelum setiap koneksi, termasuk setiap redirect.
3. Seluruh jawaban A/AAAA harus berupa alamat publik. Jawaban campuran publik/privat ditolak.
4. `SafeHttpClient` memakai handler cURL secara eksplisit. `CURLOPT_RESOLVE` mengikat hostname dan port ke satu IP yang sudah divalidasi, sambil mempertahankan hostname untuk Host, SNI, dan verifikasi sertifikat.
5. Koneksi baru dibuat pada setiap hop. Pemakaian ulang koneksi dan proxy dinonaktifkan. DNS yang berubah setelah validasi tidak mengganti tujuan koneksi.

URL hanya boleh memakai HTTP port 80 atau HTTPS port 443. Userinfo, karakter kontrol, backslash, fragmen, nama internal, localhost, IP privat, loopback, link-local, multicast, serta rentang khusus ditolak. IPv6 dibatasi ke alamat global 2000::/3, di luar rentang terlarang dan mekanisme terowongan 6to4/Teredo.

## Batas sumber daya

- Maksimal tiga redirect, diikuti secara manual.
- Timeout koneksi tiga detik dan total permintaan delapan detik per hop; maksimal empat hop.
- Body satu MiB, termasuk body tanpa Content-Length. Sink menghentikan penulisan ketika batas terlampaui. Content-Length terlalu besar ditolak sebelum body dibaca.
- Dekompresi otomatis dimatikan; aplikasi tidak mengeksekusi atau menampilkan isi body target.
- Rate limit global per target: dua pemeriksaan manual per menit. Per IP: sepuluh per menit.
- Lock target 90 detik, digunakan oleh semua pemeriksaan serta perubahan/hapus target. Scheduler juga memakai `withoutOverlapping`.
- Error target disimpan sebagai pesan aman. Detail exception dan credential tidak ditampilkan.

Guzzle 8 memerlukan opsi `proxy` dan `protocols` pada tingkat request. Opsi cURL hanya dipakai untuk DNS pinning dan kebijakan koneksi yang didukung. Pengujian membentuk handle cURL tanpa jaringan untuk mendeteksi opsi yang tidak kompatibel.

## Batas penerapan

Aplikasi ditujukan untuk demo lokal satu host. Jalankan server pada 127.0.0.1. Bila memakai MAMP, document root harus menunjuk ke `public/`, bukan root proyek; gunakan PHP 8.5 dan pastikan host tidak dapat diakses dari internet. `.env`, database, vendor, dan log tidak boleh disajikan oleh web server atau masuk Git.

Jangan menerbitkan pengelolaan target tanpa autentikasi, otorisasi, audit, kuota, batas jumlah target, dan firewall egress. Untuk banyak server, ganti file cache dengan backend lock bersama. DNS menggunakan resolver sistem sehingga kecepatan resolusi bergantung pada konfigurasi mesin; gunakan resolver tepercaya. Diperlukan cURL: aplikasi gagal tertutup ketika ekstensi tersebut tidak tersedia.

Tidak ada data dummy atau target bawaan. Riwayat URL lama tetap berada di SQLite setelah URL diubah; tampilan dan statistik hanya menggunakan URL saat ini. Penghapusan website menghapus seluruh riwayatnya. Lakukan cadangan SQLite bila diperlukan.

Referensi: [Laravel HTTP Client](https://laravel.com/docs/13.x/http-client), [Laravel Scheduler](https://laravel.com/docs/13.x/scheduling), [cURL RESOLVE](https://curl.se/libcurl/c/CURLOPT_RESOLVE.html).
