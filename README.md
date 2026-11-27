# Sistem Informasi Jadwal Kontrol Pasien

## Innovation Dental Care Pontianak

### Deskripsi Proyek

Sistem Informasi Jadwal Kontrol Pasien Berbasis Web merupakan aplikasi yang dikembangkan untuk membantu pengelolaan jadwal kontrol pasien di Innovation Dental Care Pontianak.

Sistem ini berfokus pada pasien lama, khususnya pasien yang menjalani perawatan behel dan membutuhkan kontrol secara rutin setiap bulan. Melalui sistem ini, admin dapat mengelola data pasien dan menentukan jadwal kontrol, sedangkan pasien dapat melihat jadwal, melakukan konfirmasi kehadiran, serta mengajukan perubahan jadwal apabila berhalangan hadir.

### Tujuan Proyek

1. Mempermudah admin dalam mengelola data pasien.
2. Membantu proses penjadwalan kontrol pasien.
3. Memudahkan pasien dalam mengetahui jadwal kontrol.
4. Mengurangi risiko pasien lupa terhadap jadwal kontrol.
5. Membantu komunikasi antara pihak klinik dan pasien.

### Teknologi yang Digunakan

**Teknologi pengembangan:**
- PHP
- MySQL
- HTML
- CSS

**Perangkat pengembangan:**
- Laragon
- Visual Studio Code
- HeidiSQL
- Git dan GitHub

### Hak Akses Pengguna

**1. Admin**

- Login dan logout ke sistem.
- Melihat dashboard admin.
- Melihat, menambahkan, mengubah, dan menghapus data pasien.
- Menambahkan akun pasien.
- Melihat informasi dokter dan jadwal praktik.
- Menambahkan dan mengubah jadwal kontrol pasien.
- Melihat serta mengelola konfirmasi kehadiran pasien.
- Mengelola pengajuan perubahan jadwal dari pasien.
- Memperbarui status jadwal kontrol pasien.

**2. Pasien**

- Login ke sistem menggunakan akun yang dibuat oleh admin.
- Melihat dashboard pasien.
- Melihat jadwal kontrol pribadi.
- Melakukan konfirmasi kehadiran.
- Mengajukan perubahan jadwal apabila berhalangan hadir.
- Melihat informasi terkait jadwal kontrol.

### Ketentuan Jadwal Klinik

Klinik beroperasi pada hari Senin sampai Sabtu, pukul 18.00–21.00 WIB, dan tutup pada hari Minggu.

**Jadwal praktik dokter:**
- Senin dan Rabu: drg. Trisnawati
- Selasa dan Jumat: drg. Serina
- Kamis dan Sabtu: drg. Teisya

### Struktur Database

Database yang digunakan bernama `db_innovation_dental_care` dan terdiri dari lima tabel utama:

1. `users` – Menyimpan data akun pengguna dan hak akses.
2. `pasien` – Menyimpan data pasien.
3. `dokter` – Menyimpan data dokter.
4. `jadwal_kontrol` – Menyimpan jadwal kontrol pasien.
5. `pengajuan_perubahan` – Menyimpan pengajuan perubahan jadwal dari pasien.

### Tahapan Pengerjaan

#### Tahap 1 – Persiapan dan Pembuatan Dasar Sistem

Pada tahap pertama, kegiatan yang dilakukan meliputi:

1. Menentukan konsep dan kebutuhan sistem informasi jadwal kontrol pasien.
2. Menyiapkan lingkungan pengembangan menggunakan Laragon, VS Code, dan HeidiSQL.
3. Membuat struktur folder dan database beserta lima tabel utama.
4. Menambahkan data dokter dan jadwal praktik ke database.
5. Membuat serta menguji koneksi PHP dengan database MySQL.
6. Mengembangkan fitur login, autentikasi, dan pembatasan hak akses admin dan pasien.
7. Membuat dashboard admin dan pasien.
8. Membuat halaman untuk menampilkan dan menambahkan data pasien.

Pada tahap ini, koneksi database dan login admin telah berhasil diuji, serta fitur dasar pengelolaan pasien mulai dikembangkan.

#### Tahap 2 – Pengembangan Fitur dan Tampilan Sistem

Pada tahap kedua, kegiatan yang dilakukan meliputi:

1. Mengembangkan dashboard admin dan dashboard pasien.
2. Menambahkan fitur pengelolaan data pasien, meliputi tambah, edit, dan hapus pasien.
3. Membuat halaman informasi dokter beserta jadwal praktik.
4. Mengembangkan fitur pengelolaan jadwal kontrol pasien.
5. Membuat fitur pengelolaan konfirmasi dan pengajuan perubahan jadwal oleh admin.
6. Membuat halaman jadwal kontrol pasien.
7. Mengembangkan fitur konfirmasi kehadiran pasien.
8. Membuat fitur pengajuan perubahan jadwal kontrol oleh pasien.
9. Mengembangkan halaman utama, halaman login, serta tampilan website menggunakan CSS dan menambahkan aset gambar.
10. Mengelola versi proyek menggunakan Git dan GitHub dengan melakukan commit dan push perubahan ke branch `development`.

Pada tahap ini, halaman utama, fitur admin, fitur pasien, autentikasi, dan tampilan website telah dikembangkan. Hasil pengembangan juga telah diunggah ke repository GitHub pada branch `development`.