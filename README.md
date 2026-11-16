# SIMAWA — Employee Management System
**Sistem Informasi Manajemen Kepegawaian PT MAKMUR**

Aplikasi berbasis web untuk pencatatan, pengelolaan, dan visualisasi data personil pegawai PT MAKMUR. Sistem ini dibangun menggunakan PHP Native dengan driver PDO (PHP Data Objects), database MySQL, serta antarmuka modern yang responsif.

---

## 📌 Fitur Utama

1. **Autentikasi & Keamanan Sesi**:
   - Login administrator dengan enkripsi sandi aman (`password_verify` Bcrypt).
   - Verifikasi Captcha matematika otomatis untuk mencegah brute force.
   - Proteksi sesi global (`requireAuth`) dan manajemen token sesi.
2. **Dashboard Eksekutif & Visualisasi Data**:
   - Kartu metrik total personil pegawai dan total unit departemen.
   - Grafik batang interaktif sebaran pegawai per divisi menggunakan Chart.js.
   - Tabel pratinjau daftar personil terbaru dengan badge divisi.
3. **Manajemen Pegawai (CRUD Master)**:
   - **Tabel Data Pegawai**: Pencarian multi-kriteria, filter per departemen, dan paginasi otomatis.
   - **Tambah Pegawai**: Validasi NIP unik 18 digit, email valid, nomor telepon, dan upload foto profil.
   - **Edit Pegawai**: Pembaruan informasi personal dan pergantian foto berkas.
   - **Detail Pegawai**: Profil lengkap disertai algoritma penghitung masa kerja (*tenure*) otomatis.
   - **Hapus Interaktif**: Penghapusan data via AJAX jQuery dengan konfirmasi modal SweetAlert2.
4. **Kelola Master Departemen**:
   - Pencatatan unit divisi kerja perusahaan.
   - Proteksi integritas relasi database Foreign Key (`ON DELETE RESTRICT`).
5. **Kelola Administrator**:
   - Pengelolaan akun admin pengelola sistem.
   - Proteksi keamanan hapus mandiri (*Self-Delete Protection*).

---

## 📂 Struktur Direktori

```text
SIMAWA/
├── assets/
│   ├── css/
│   │   ├── app.css              # Styling utama aplikasi & tabel
│   │   ├── dashboard.css        # Styling khusus halaman dashboard
│   │   └── login.css            # Styling halaman login & background
│   ├── img/                     # Berkas logo & aset grafis
│   ├── js/
│   │   └── app.js               # Script validasi, AJAX delete, & interaksi
│   ├── uploads/
│   │   └── employees/           # Direktori penyimpanan berkas foto pegawai
│   └── vendor/                  # Library offline (Bootstrap, jQuery, Chart.js, SweetAlert2)
├── auth/
│   ├── login.php                # Halaman login & verifikasi autentikasi
│   └── logout.php               # Handler logout & penghapusan sesi
├── config/
│   ├── database.php             # Konfigurasi koneksi PDO terpusat & session auth
│   └── koneksi.php              # Kompatibilitas helper koneksi
├── dashboard/
│   └── index.php                # Halaman utama dashboard & visualisasi grafik
├── database/
│   └── database.sql             # Skema tabel database relasional & data awal
├── departemen/
│   └── index.php                # Modul pengelolaan master departemen
├── includes/
│   ├── header.php               # Komponen header navigasi & sidebar modular
│   └── footer.php               # Komponen penutup & inisialisasi skrip JS
├── pegawai/
│   ├── index.php                # Halaman daftar personil pegawai & filter
│   ├── tambah.php               # Form input pegawai baru
│   ├── edit.php                 # Form edit data pegawai
│   ├── detail.php               # Halaman tampilan detail profil pegawai
│   ├── export.php               # Ekspor data pegawai
│   └── hapus.php                # Handler hapus data pegawai
├── proses/
│   ├── simpan.php               # Pemrosesan simpan pegawai
│   ├── update.php               # Pemrosesan pembaruan pegawai
│   └── hapus.php                # Handler AJAX penghapusan pegawai
├── users/
│   └── index.php                # Modul pengelolaan akun administrator
├── index.php                    # Router utama status login
└── README.md                    # Dokumentasi proyek
```

---

## ⚙️ Persyaratan Sistem & Instalasi

1. **Web Server**: Apache (Laragon / XAMPP)
2. **PHP**: Versi 7.4 atau lebih baru (ekstensi `pdo_mysql` aktif)
3. **Database**: MySQL 5.7 / 8.0 atau MariaDB

### Langkah Menjalankan:
1. Pastikan server web Apache dan MySQL telah aktif di Laragon/XAMPP.
2. Buat database baru bernama `ems_db` di phpMyAdmin / MySQL.
3. Import berkas `database/database.sql` ke dalam database `ems_db`.
4. Buka peramban (browser) dan akses URL:
   ```
   http://localhost/SIMAWA/
   ```

---

## 🔑 Kredensial Pengguna

* **Email**: `admin@ems.test`
* **Kata Sandi**: `password123`
* **Captcha**: Masukkan hasil penjumlahan matematika yang tampil pada halaman login.

