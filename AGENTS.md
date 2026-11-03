# Website Monitoring

Proyek Laravel 13 untuk demo lokal satu host. Ikuti batasan berikut saat mengubah aplikasi:

- Gunakan Blade, CSS terpisah, dan JavaScript murni. Jangan tambahkan React, Vue, Livewire, Inertia, atau dependency yang tidak dibutuhkan.
- Maksimal 300 baris per file source. Pisahkan controller, validasi request, dan service monitoring.
- Pertahankan perlindungan SSRF: validasi seluruh DNS, pin tujuan cURL, validasi ulang setiap redirect, tanpa proxy, TLS aktif, dan sink terbatas.
- Pemeriksaan manual/scheduler serta edit/hapus memakai kunci target yang sama.
- Jangan menambahkan hasil monitoring dummy atau menyimpan credential dalam Git.
- Seluruh UI menggunakan bahasa Indonesia, Helvetica Neue, teks #111111, background #F4F5F0, dan aksen fungsional #2446A6. Headline desktop harus satu baris.
- Jalankan `php artisan test` dan `npm run build` setelah perubahan terkait. HTTP dan DNS pengujian harus dimock.
- Aplikasi tidak memiliki autentikasi. Jangan menerbitkan pengelolaan target ke internet tanpa permintaan dan perlindungan tambahan.
