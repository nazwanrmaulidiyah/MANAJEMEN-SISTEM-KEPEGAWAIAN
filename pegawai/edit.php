<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();

// 1. Ambil ID dan data pegawai saat ini dari database via PDO
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    $_SESSION['error'] = 'Data pegawai tidak ditemukan.';
    header('Location: index.php');
    exit;
}

$page_title = 'Edit Data Pegawai';
$page_subtitle = 'Perbarui data dan informasi personil PT MAKMUR';

// 2. Siapkan penampung error dan data form (gunakan data POST jika ada, atau data database)
$errors = [];
$nip = $_POST['nip'] ?? $employee['nip'];
$nama = $_POST['nama'] ?? $employee['nama'];
$email = $_POST['email'] ?? $employee['email'];
$no_hp = $_POST['no_hp'] ?? $employee['no_hp'];
$jabatan = $_POST['jabatan'] ?? $employee['jabatan'];
$departemen = $_POST['departemen'] ?? $employee['departemen'];
$tanggal_masuk = $_POST['tanggal_masuk'] ?? $employee['tanggal_masuk'];
$alamat = $_POST['alamat'] ?? $employee['alamat'];

// Ambil daftar departemen dari Master Departemen
$stmtDept = $pdo->query("SELECT nama FROM departments ORDER BY nama ASC");
$departments = $stmtDept->fetchAll(PDO::FETCH_COLUMN);
if (empty($departments)) {
    $departments = ['Teknologi Informasi', 'Human Resources', 'Keuangan', 'Pemasaran', 'Operasional', 'Desain & Kreatif'];
}
if (!in_array($departemen, $departments)) {
    $departments[] = $departemen;
}

// 3. Proses submit update pegawai (method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi Keamanan CSRF Token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Token keamanan CSRF tidak valid atau sesi telah berakhir. Silakan coba kirim kembali formulir.';
    }

    $nip = trim($nip);
    $nama = trim($nama);
    $email = trim($email);
    $no_hp = trim($no_hp);
    $jabatan = trim($jabatan);
    $departemen = trim($departemen);
    $tanggal_masuk = trim($tanggal_masuk);
    $alamat = trim($alamat);

    // 4. Validasi input: NIP unik (kecuali milik pegawai ini sendiri)
    if (empty($nip)) {
        $errors['nip'] = 'NIP wajib diisi.';
    } elseif (!preg_match('/^[0-9]{18}$/', $nip)) {
        $errors['nip'] = 'NIP harus terdiri dari tepat 18 digit angka.';
    } else {
        $stmtCheck = $pdo->prepare("SELECT id FROM employees WHERE nip = ? AND id != ? LIMIT 1");
        $stmtCheck->execute([$nip, $id]);
        if ($stmtCheck->fetch()) {
            $errors['nip'] = 'NIP sudah digunakan oleh pegawai lain.';
        }
    }

    if (empty($nama)) $errors['nama'] = 'Nama lengkap wajib diisi.';

    // Validasi Email (wajib, format valid, dan unik kecuali milik pegawai ini)
    if (empty($email)) {
        $errors['email'] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    } else {
        $stmtCheckEmail = $pdo->prepare("SELECT id FROM employees WHERE email = ? AND id != ? LIMIT 1");
        $stmtCheckEmail->execute([$email, $id]);
        if ($stmtCheckEmail->fetch()) {
            $errors['email'] = 'Alamat email sudah digunakan oleh pegawai lain.';
        }
    }

    if (empty($no_hp)) $errors['no_hp'] = 'Nomor HP wajib diisi.';
    if (empty($jabatan)) $errors['jabatan'] = 'Jabatan wajib diisi.';
    if (empty($departemen)) $errors['departemen'] = 'Departemen wajib dipilih.';
    if (empty($tanggal_masuk)) $errors['tanggal_masuk'] = 'Tanggal masuk wajib diisi.';
    if (empty($alamat)) $errors['alamat'] = 'Alamat lengkap wajib diisi.';

    // 5. Proses penggantian foto jika ada berkas baru diunggah
    $fotoFilename = $employee['foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $errors['foto'] = 'Terjadi kesalahan saat mengunggah foto.';
        } else {
            $fileTmp = $_FILES['foto']['tmp_name'];
            $fileName = $_FILES['foto']['name'];
            $fileSize = $_FILES['foto']['size'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($fileExt, $allowedExts)) {
                $errors['foto'] = 'Format file foto harus JPG, JPEG, PNG, atau WEBP.';
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $errors['foto'] = 'Ukuran file foto maksimal adalah 2MB.';
            } else {
                // Verifikasi file gambar asli menggunakan getimagesize dan finfo_file MIME type
                $imageInfo = @getimagesize($fileTmp);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $realMime = $finfo ? finfo_file($finfo, $fileTmp) : '';
                if ($finfo) finfo_close($finfo);

                if (!$imageInfo || !in_array($realMime, $allowedMimes)) {
                    $errors['foto'] = 'Berkas yang diunggah bukan file gambar valid atau telah rusak.';
                } else {
                    // Hapus file foto lama dari folder server jika ada
                    if (!empty($employee['foto']) && file_exists(UPLOAD_DIR . $employee['foto'])) {
                        @unlink(UPLOAD_DIR . $employee['foto']);
                    }

                    $fotoFilename = 'emp_' . time() . '_' . uniqid() . '.' . $fileExt;
                    if (!is_dir(UPLOAD_DIR)) {
                        mkdir(UPLOAD_DIR, 0777, true);
                    }
                    if (!move_uploaded_file($fileTmp, UPLOAD_DIR . $fotoFilename)) {
                        $errors['foto'] = 'Gagal menyimpan file foto ke server.';
                    }
                }
            }
        }
    }

    // 6. Jika semua input valid, jalankan query UPDATE dengan PDO
    if (empty($errors)) {
        $updateSql = "UPDATE employees SET 
            nip = ?, nama = ?, email = ?, no_hp = ?, jabatan = ?, departemen = ?, 
            tanggal_masuk = ?, alamat = ?, foto = ?, updated_at = NOW() 
            WHERE id = ?";
        
        $stmtUpdate = $pdo->prepare($updateSql);
        $stmtUpdate->execute([
            $nip, $nama, $email, $no_hp, $jabatan, $departemen, $tanggal_masuk, $alamat, $fotoFilename, $id
        ]);

        // 7. Simpan notifikasi sukses dan arahkan ke detail profil pegawai
        $_SESSION['success'] = "Data pegawai '{$nama}' berhasil diperbarui!";
        header("Location: detail.php?id={$id}");
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card-ems fade-in">
                <div class="card-ems-header justify-content-start">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 4px; height: 18px; background: var(--accent-primary); border-radius: 4px;"></div>
                        <h5 class="fw-bold mb-0" style="color: var(--text-primary); font-size: 1.1rem; letter-spacing: -0.2px;">
                            Edit Data Pegawai
                        </h5>
                    </div>
                </div>

                <div class="card-ems-body p-4 p-md-5">
                    <?php if (!empty($errors['csrf'])): ?>
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Sesi Berakhir',
                                    text: <?= json_encode($errors['csrf']) ?>,
                                    showConfirmButton: true,
                                    confirmButtonColor: '#f97316'
                                });
                            });
                        </script>
                    <?php endif; ?>
                    <form action="edit.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" id="form-employee-edit" class="form-confirm-edit" novalidate autocomplete="off">
                        <?= csrfField() ?>
                        <div class="row g-4 align-items-stretch">

                            <!-- Kolom Kiri: Foto Saat Ini & Preview Pengganti (Tinggi Selaras Menyesuaikan Garis Bawah Alamat) -->
                            <div class="col-12 col-md-4 d-flex flex-column text-center">
                                <div class="upload-photo-frame flex-grow-1 w-100 d-flex flex-column justify-content-center align-items-center">
                                    <label class="form-ems-label mb-3 d-block fw-bold" style="color: var(--text-primary); font-size: 0.95rem;">
                                        Foto Profil Pegawai
                                    </label>
                                    <div class="mb-3 d-flex justify-content-center">
                                        <div class="position-relative" style="width: 140px; height: 140px;">
                                            <img src="<?= getEmployeePhotoUrl($employee['foto'], $employee['nama']) ?>" 
                                                 id="preview-foto" 
                                                 alt="<?= e($employee['nama']) ?>" 
                                                 class="rounded-circle shadow" 
                                                 style="width: 140px; height: 140px; object-fit: cover; border: 3px solid var(--accent-primary);">
                                        </div>
                                    </div>
                                    <input type="file" 
                                           name="foto" 
                                           id="foto" 
                                           class="form-control form-ems-control <?= !empty($errors['foto']) ? 'is-invalid' : '' ?>" 
                                           accept="image/png, image/jpeg, image/jpg, image/webp">
                                    <div class="mt-2" style="font-size: 0.82rem; color: var(--text-muted); font-weight: 500;">
                                        Kosongkan jika tidak mengganti &bull; Maks 2MB
                                    </div>
                                    <?php if (!empty($errors['foto'])): ?>
                                        <div class="invalid-feedback d-block"><?= e($errors['foto']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Field Edit Berkelompok Jelas -->
                            <div class="col-12 col-md-8">
                                <div class="row g-3">
                                    <!-- Sub-Header 1: Data Identitas -->
                                    <div class="col-12">
                                        <div class="form-sub-header">
                                            DATA IDENTITAS PEGAWAI
                                        </div>
                                    </div>

                                    <!-- NIP -->
                                    <div class="col-12 col-sm-6">
                                        <label for="nip" class="form-ems-label">Nomor Induk Pegawai (NIP)<span class="required">*</span></label>
                                        <input type="text" 
                                               name="nip" 
                                               id="nip" 
                                               class="form-control form-ems-control <?= !empty($errors['nip']) ? 'is-invalid' : '' ?>" 
                                               value="<?= e($nip) ?>" 
                                               maxlength="18" 
                                               inputmode="numeric" 
                                               pattern="[0-9]{18}" 
                                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 18);" 
                                               required>
                                        <?php if (!empty($errors['nip'])): ?>
                                            <div class="invalid-feedback d-block"><?= e($errors['nip']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Nama Lengkap -->
                                    <div class="col-12 col-sm-6">
                                        <label for="nama" class="form-ems-label">Nama Lengkap<span class="required">*</span></label>
                                        <input type="text" 
                                               name="nama" 
                                               id="nama" 
                                               class="form-control form-ems-control <?= !empty($errors['nama']) ? 'is-invalid' : '' ?>" 
                                               value="<?= e($nama) ?>" 
                                               required>
                                        <?php if (!empty($errors['nama'])): ?>
                                            <div class="invalid-feedback d-block"><?= e($errors['nama']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Email -->
                                    <div class="col-12 col-sm-6">
                                        <label for="email" class="form-ems-label">Alamat Email<span class="required">*</span></label>
                                        <input type="email" 
                                               name="email" 
                                               id="email" 
                                               class="form-control form-ems-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" 
                                               value="<?= e($email) ?>" 
                                               required>
                                        <?php if (!empty($errors['email'])): ?>
                                            <div class="invalid-feedback d-block"><?= e($errors['email']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Nomor HP -->
                                    <div class="col-12 col-sm-6">
                                        <label for="no_hp" class="form-ems-label">Nomor Telepon / WhatsApp<span class="required">*</span></label>
                                        <input type="tel" 
                                               name="no_hp" 
                                               id="no_hp" 
                                               class="form-control form-ems-control <?= !empty($errors['no_hp']) ? 'is-invalid' : '' ?>" 
                                               value="<?= e($no_hp) ?>" 
                                               required>
                                        <?php if (!empty($errors['no_hp'])): ?>
                                            <div class="invalid-feedback d-block"><?= e($errors['no_hp']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Sub-Header 2: Informasi Jabatan & Alamat -->
                                    <div class="col-12 mt-4">
                                        <div class="form-sub-header">
                                            INFORMASI JABATAN & DOMISILI
                                        </div>
                                    </div>

                                    <!-- Jabatan -->
                                    <div class="col-12 col-sm-6">
                                        <label for="jabatan" class="form-ems-label">Posisi / Jabatan<span class="required">*</span></label>
                                        <input type="text" 
                                               name="jabatan" 
                                               id="jabatan" 
                                               class="form-control form-ems-control <?= !empty($errors['jabatan']) ? 'is-invalid' : '' ?>" 
                                               value="<?= e($jabatan) ?>" 
                                               required>
                                        <?php if (!empty($errors['jabatan'])): ?>
                                            <div class="invalid-feedback d-block"><?= e($errors['jabatan']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Departemen -->
                                    <div class="col-12 col-sm-6">
                                        <label for="departemen" class="form-ems-label">Departemen / Divisi<span class="required">*</span></label>
                                        <select name="departemen" id="departemen" class="form-select form-ems-select <?= !empty($errors['departemen']) ? 'is-invalid' : '' ?>" required>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?= e($dept) ?>" <?= ($departemen === $dept) ? 'selected' : '' ?>>
                                                    <?= e($dept) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (!empty($errors['departemen'])): ?>
                                            <div class="invalid-feedback d-block"><?= e($errors['departemen']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Tanggal Masuk -->
                                    <div class="col-12">
                                        <label for="tanggal_masuk" class="form-ems-label">Tanggal Mulai Bekerja<span class="required">*</span></label>
                                        <input type="date" 
                                               name="tanggal_masuk" 
                                               id="tanggal_masuk" 
                                               class="form-control form-ems-control <?= !empty($errors['tanggal_masuk']) ? 'is-invalid' : '' ?>" 
                                               value="<?= e($tanggal_masuk) ?>" 
                                               required>
                                        <?php if (!empty($errors['tanggal_masuk'])): ?>
                                            <div class="invalid-feedback d-block"><?= e($errors['tanggal_masuk']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Alamat Lengkap -->
                                    <div class="col-12">
                                        <label for="alamat" class="form-ems-label">Alamat Lengkap Tempat Tinggal<span class="required">*</span></label>
                                        <textarea name="alamat" 
                                                  id="alamat" 
                                                  rows="3" 
                                                  class="form-control form-ems-control <?= !empty($errors['alamat']) ? 'is-invalid' : '' ?>" 
                                                  required><?= e($alamat) ?></textarea>
                                        <?php if (!empty($errors['alamat'])): ?>
                                            <div class="invalid-feedback d-block"><?= e($errors['alamat']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Action Buttons: Garis Batas Bawah Melintang Penuh di Bawah Alamat & Kotak Foto -->
                        <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top border-secondary border-opacity-25">
                            <a href="index.php" class="btn btn-danger-custom">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-orange">
                                Simpan Data Pegawai
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

