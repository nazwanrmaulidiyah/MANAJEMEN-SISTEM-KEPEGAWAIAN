<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();

// 1. Validasi parameter ID dari URL
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// 2. Ambil data lengkap satu pegawai dari database MySQL via PDO
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$employee = $stmt->fetch();

// 3. Jika data tidak ditemukan, beri pesan error dan kembalikan ke daftar pegawai
if (!$employee) {
    $_SESSION['error'] = 'Data pegawai tidak ditemukan.';
    header('Location: index.php');
    exit;
}

$page_title = 'Detail Pegawai';
$page_subtitle = 'Informasi profil dan biodata personil PT MAKMUR';

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <!-- 1 Kotak Card Utuh (Format Seragam dengan Tambah & Edit Pegawai) -->
            <div class="card-ems fade-in">
                <!-- Header di Pojok Kiri dengan Aksen Garis Oranye -->
                <div class="card-ems-header justify-content-start">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 4px; height: 18px; background: var(--accent-primary); border-radius: 4px;"></div>
                        <h5 class="fw-bold mb-0" style="color: var(--text-primary); font-size: 1.1rem; letter-spacing: -0.2px;">
                            Detail Data Pegawai
                        </h5>
                    </div>
                </div>

                <div class="card-ems-body p-4 p-md-5">
                    <div class="row g-4 align-items-stretch">

                        <!-- Kolom Kiri: Foto Profil Pegawai (Tinggi Selaras Menyesuaikan Garis Bawah Alamat) -->
                        <div class="col-12 col-md-4 d-flex flex-column text-center">
                            <div class="upload-photo-frame flex-grow-1 w-100 d-flex flex-column justify-content-center align-items-center">
                                <label class="form-ems-label mb-3 d-block fw-bold" style="color: var(--text-primary); font-size: 0.95rem;">
                                    Foto Profil Pegawai
                                </label>
                                <div class="mb-3 d-flex justify-content-center">
                                    <div class="position-relative" style="width: 140px; height: 140px;">
                                        <img src="<?= getEmployeePhotoUrl($employee['foto'], $employee['nama']) ?>" 
                                             alt="<?= e($employee['nama']) ?>" 
                                             class="rounded-circle shadow" 
                                             style="width: 140px; height: 140px; object-fit: cover; border: 3px solid var(--accent-primary);">
                                    </div>
                                </div>
                                <div class="fw-bold fs-6 mt-1" style="color: var(--text-primary);">
                                    <?= e($employee['nama']) ?>
                                </div>
                                <span class="emp-nip-mono mt-1" style="font-size: 0.85rem;">
                                    <?= e($employee['nip']) ?>
                                </span>
                                <div class="mt-3">
                                    <span class="emp-dept-tag">
                                        <i class="bi bi-building me-1"></i> <?= e($employee['departemen']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Detail Informasi Pegawai (Bentuk Form Satu Kotak Bersih) -->
                        <div class="col-12 col-md-8">
                            <div class="row g-3">
                                <!-- Sub Header 1: Informasi Pribadi & Kontak -->
                                <div class="col-12">
                                    <div class="form-sub-header">
                                        INFORMASI PRIBADI &amp; KONTAK
                                    </div>
                                </div>

                                <!-- NIP -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-ems-label">Nomor Induk Pegawai (NIP)</label>
                                    <input type="text" class="form-control form-ems-control" value="<?= e($employee['nip']) ?>" readonly style="background-color: var(--bg-surface) !important; cursor: default;">
                                </div>

                                <!-- Nama Lengkap -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-ems-label">Nama Lengkap</label>
                                    <input type="text" class="form-control form-ems-control" value="<?= e($employee['nama']) ?>" readonly style="background-color: var(--bg-surface) !important; cursor: default;">
                                </div>

                                <!-- Email -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-ems-label">Alamat Email</label>
                                    <input type="text" class="form-control form-ems-control" value="<?= e($employee['email']) ?>" readonly style="background-color: var(--bg-surface) !important; cursor: default;">
                                </div>

                                <!-- No HP / WA -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-ems-label">Nomor Telepon / WhatsApp</label>
                                    <input type="text" class="form-control form-ems-control" value="<?= e($employee['no_hp']) ?>" readonly style="background-color: var(--bg-surface) !important; cursor: default;">
                                </div>

                                <!-- Sub Header 2: Informasi Jabatan & Domisili -->
                                <div class="col-12 mt-4">
                                    <div class="form-sub-header">
                                        INFORMASI JABATAN &amp; DOMISILI
                                    </div>
                                </div>

                                <!-- Posisi / Jabatan -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-ems-label">Posisi / Jabatan</label>
                                    <input type="text" class="form-control form-ems-control" value="<?= e($employee['jabatan']) ?>" readonly style="background-color: var(--bg-surface) !important; cursor: default;">
                                </div>

                                <!-- Departemen -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-ems-label">Departemen / Divisi</label>
                                    <input type="text" class="form-control form-ems-control" value="<?= e($employee['departemen']) ?>" readonly style="background-color: var(--bg-surface) !important; cursor: default;">
                                </div>

                                <!-- Tanggal Mulai Bekerja -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-ems-label">Tanggal Mulai Bekerja</label>
                                    <input type="text" class="form-control form-ems-control" value="<?= formatDateIndo($employee['tanggal_masuk']) ?>" readonly style="background-color: var(--bg-surface) !important; cursor: default;">
                                </div>

                                <!-- Masa Kerja -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-ems-label">Masa Kerja</label>
                                    <input type="text" class="form-control form-ems-control" value="<?= calculateTenure($employee['tanggal_masuk']) ?>" readonly style="background-color: var(--bg-surface) !important; cursor: default;">
                                </div>

                                <!-- Alamat Lengkap -->
                                <div class="col-12">
                                    <label class="form-ems-label">Alamat Lengkap Tempat Tinggal</label>
                                    <textarea class="form-control form-ems-control" rows="3" readonly style="background-color: var(--bg-surface) !important; cursor: default;"><?= e($employee['alamat']) ?></textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Tombol Aksi: Garis Batas Bawah Melintang Penuh di Bawah Alamat & Kotak Foto -->
                    <div class="d-flex justify-content-end mt-4 pt-3 border-top border-secondary border-opacity-25">
                        <a href="index.php" class="btn btn-orange px-4 fw-semibold" style="border-radius: 10px;">
                            Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
