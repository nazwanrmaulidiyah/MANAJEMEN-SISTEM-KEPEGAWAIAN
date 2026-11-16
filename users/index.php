<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();

$page_title = 'Kelola Admin';
$page_subtitle = 'Kelola data dan hak akses akun administrator sistem SIMAWA PT MAKMUR';

$errors = [];

// Proteksi Keamanan CSRF Token untuk seluruh request POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Token keamanan CSRF tidak valid atau sesi telah berakhir. Silakan coba kembali.';
        header('Location: index.php');
        exit;
    }
}

// 1. Proses Tambah User Baru (via Modal Tambah)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = 'Administrator'; // Semua pengguna bertindak sebagai Administrator divisi
    $phone = trim($_POST['phone'] ?? '');
    $unit = trim($_POST['unit'] ?? 'Kantor Pusat');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validasi
    if (empty($name)) {
        $errors[] = 'Nama lengkap pengguna wajib diisi.';
    } elseif (strlen($name) < 3) {
        $errors[] = 'Nama pengguna minimal 3 karakter.';
    }

    if (empty($email)) {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmtCek->execute([$email]);
        if ($stmtCek->fetchColumn() > 0) {
            $errors[] = 'Email "' . htmlspecialchars($email) . '" sudah digunakan.';
        }
    }

    if (empty($password)) {
        $errors[] = 'Password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal terdiri dari 6 karakter.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmtInsert = $pdo->prepare("INSERT INTO users (name, email, role, phone, instansi, unit, password, created_at, updated_at) VALUES (?, ?, ?, ?, 'PT MAKMUR', ?, ?, NOW(), NOW())");
        $stmtInsert->execute([$name, $email, $role, $phone, $unit, $hash]);
        $_SESSION['success'] = "Pengguna baru '{$name}' berhasil didaftarkan!";
        header('Location: index.php');
        exit;
    }
}

// 2. Proses Edit Data User (via Modal Edit)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $unit = trim($_POST['unit'] ?? 'Kantor Pusat');

    if ($id <= 0) {
        $errors[] = 'ID pengguna tidak valid.';
    }
    if (empty($name)) {
        $errors[] = 'Nama pengguna wajib diisi.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    } else {
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmtCek->execute([$email, $id]);
        if ($stmtCek->fetchColumn() > 0) {
            $errors[] = 'Email sudah digunakan pengguna lain.';
        }
    }

    if (empty($errors)) {
        $stmtUp = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, unit = ?, updated_at = NOW() WHERE id = ?");
        $stmtUp->execute([$name, $email, $phone, $unit, $id]);

        // Jika mengupdate akun diri sendiri, perbarui session
        if ($id === (int)($_SESSION['user']['id'] ?? 0)) {
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
        }

        $_SESSION['success'] = "Data pengguna '{$name}' berhasil diperbarui!";
        header('Location: index.php');
        exit;
    }
}

// 3. Proses Reset Password User (via Modal Reset)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $id = (int)($_POST['id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirm = $_POST['new_password_confirm'] ?? '';

    if ($id <= 0) {
        $errors[] = 'ID pengguna tidak valid.';
    } elseif (empty($new_password) || strlen($new_password) < 6) {
        $errors[] = 'Password baru minimal 6 karakter.';
    } elseif ($new_password !== $new_password_confirm) {
        $errors[] = 'Konfirmasi password baru tidak cocok.';
    }

    if (empty($errors)) {
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmtPass = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmtPass->execute([$hash, $id]);
        $_SESSION['success'] = 'Password pengguna berhasil diperbarui!';
        header('Location: index.php');
        exit;
    }
}

// 4. Proses Hapus User
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id === (int)($_SESSION['user']['id'] ?? 0)) {
        $_SESSION['error'] = 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.';
    } else {
        $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmtDel->execute([$id]);
        $_SESSION['success'] = 'Pengguna berhasil dihapus dari sistem.';
    }
    header('Location: index.php');
    exit;
}

// 5. Filter & Pencarian (Hanya Kata Kunci Saja Sesuai Permintaan)
$filter_q = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if (!empty($filter_q)) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$filter_q%";
    $params[] = "%$filter_q%";
    $params[] = "%$filter_q%";
}

$sql = "SELECT id, name, email, role, phone, instansi, unit, created_at FROM users";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid p-0">

    <!-- SweetAlert2 Error Notifikasi -->
    <?php if (!empty($errors)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const isLight = document.documentElement.getAttribute('data-theme') === 'light';
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    html: <?= json_encode('<div class="text-start ps-2">' . implode('<br>', array_map('htmlspecialchars', $errors)) . '</div>') ?>,
                    showConfirmButton: true,
                    confirmButtonColor: '#f97316',
                    background: isLight ? '#ffffff' : '#141724',
                    color: isLight ? '#0f172a' : '#f8fafc'
                });
            });
        </script>
    <?php endif; ?>

    <!-- 1. Kotak Filter & Pencarian (Kompak & Rapat Sesuai Permintaan) -->
    <div class="card-ems mb-3 fade-in">
        <div class="p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width: 4px; height: 16px; background: var(--accent-primary); border-radius: 4px;"></div>
                <h6 class="fw-bold mb-0" style="color: var(--text-primary); font-size: 0.9rem; letter-spacing: 0.5px;">
                    FILTER DATA
                </h6>
            </div>
            <form action="index.php" method="GET" class="row g-2 align-items-center">
                <!-- Kata Kunci Pencarian -->
                <div class="col-12 col-md-10">
                    <div class="input-group search-input-group">
                        <span class="input-group-text search-icon">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" 
                               name="q" 
                               class="form-control form-ems-control" 
                               placeholder="Cari nama admin, email, telepon..." 
                               value="<?= e($filter_q) ?>"
                               autocomplete="off">
                    </div>
                </div>

                <!-- Tombol Submit & Reset -->
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-search-icon" title="Cari Admin">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="index.php" class="btn btn-reset-icon" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Tabel Kelola Admin (Full Width 100%, Dengan Tombol Tambah di Kanan Atas) -->
    <div class="card-ems fade-in delay-1">
        <div class="card-ems-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 4px; height: 18px; background: var(--accent-primary); border-radius: 4px;"></div>
                <h5 class="fw-bold mb-0" style="color: var(--text-primary); font-size: 1.05rem;">
                    Kelola Admin
                </h5>
            </div>
            <!-- Tombol Tambah Admin Baru -->
            <button type="button" class="btn-tambah-user" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                <i class="bi bi-plus-lg"></i> Tambah Admin Baru
            </button>
        </div>

        <div class="table-responsive">
            <table class="table-ems mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">NO</th>
                        <th style="min-width: 200px;">ADMINISTRATOR</th>
                        <th style="min-width: 220px;">KONTAK</th>
                        <th style="min-width: 150px;">KETERANGAN</th>
                        <th style="min-width: 200px;">PENUGASAN / INSTANSI</th>
                        <th class="text-center" style="width: 140px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usersList)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div style="color: var(--text-muted);">
                                    <i class="bi bi-person-x fs-1 d-block mb-2 text-warning"></i>
                                    <h5 class="fw-bold" style="color: var(--text-primary);">Data Pengguna Tidak Ditemukan</h5>
                                    <p class="small mb-3" style="color: var(--text-secondary);">
                                        <?php if (!empty($filter_q)): ?>
                                            Tidak ada akun pengguna yang cocok dengan kata kunci pencarian "<strong><?= e($filter_q) ?></strong>".
                                        <?php else: ?>
                                            Belum ada data pengguna yang terdaftar di dalam sistem.
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($filter_q)): ?>
                                        <a href="index.php" class="btn btn-sm btn-orange">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Bersihkan Pencarian
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usersList as $idx => $u): ?>
                            <?php 
                                $isCurrent = ($u['id'] == ($_SESSION['user']['id'] ?? 0)); 
                                $userRole = $u['role'] ?? 'Administrator';
                            ?>
                            <tr>
                                <!-- No -->
                                <td class="text-center fw-semibold text-secondary" style="color: var(--text-secondary) !important;">
                                    <?= $idx + 1 ?>
                                </td>

                                <!-- Pengguna (Teks Saja, Tanpa Foto, Tanpa Tag Anda) -->
                                <td>
                                    <div>
                                        <div class="fw-bold" style="color: var(--text-primary); font-size: 0.94rem;">
                                            <?= e($u['name']) ?>
                                        </div>
                                        <div style="font-size: 0.82rem; color: var(--text-secondary); margin-top: 3px; font-weight: 500;">
                                            Dibuat: <?= formatDateIndo($u['created_at']) ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kontak (Sama Persis Seperti di Data Pegawai) -->
                                <td>
                                    <div class="emp-contact-item">
                                        <i class="bi bi-envelope"></i>
                                        <a href="mailto:<?= e($u['email']) ?>"><?= e($u['email']) ?></a>
                                    </div>
                                    <?php if (!empty($u['phone'])): ?>
                                        <div class="emp-contact-item text-phone mt-1">
                                            <i class="bi bi-telephone"></i>
                                            <span><?= e($u['phone']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Keterangan (Jabatan/Role Langsung Teks) -->
                                <td>
                                    <span class="fw-semibold" style="font-size: 0.88rem; color: var(--text-primary);">
                                        <?= e($userRole) ?>
                                    </span>
                                </td>

                                <!-- Penugasan / Instansi -->
                                <td>
                                    <div>
                                        <div class="fw-bold" style="color: var(--text-primary); font-size: 0.88rem;">
                                            <?= e($u['instansi'] ?? 'PT MAKMUR') ?>
                                        </div>
                                        <div style="font-size: 0.82rem; color: var(--text-secondary); margin-top: 3px; font-weight: 500;">
                                            <?= e($u['unit'] ?? 'Kantor Pusat') ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Aksi (3 Kotak Aksi Selaras Data Pegawai: Edit Oranye, Kunci Cyan, Hapus Merah) -->
                                <td class="text-center">
                                    <div class="d-inline-flex gap-2">
                                        <!-- Tombol Edit (Oranye) -->
                                        <button type="button" 
                                                class="btn-action-box btn-box-edit btn-trigger-edit" 
                                                title="Edit Data User"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditUser"
                                                data-id="<?= $u['id'] ?>"
                                                data-name="<?= e($u['name']) ?>"
                                                data-email="<?= e($u['email']) ?>"
                                                data-phone="<?= e($u['phone'] ?? '') ?>"
                                                data-unit="<?= e($u['unit'] ?? 'Kantor Pusat') ?>">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>

                                        <!-- Tombol Reset Password (Cyan Selaras Detail) -->
                                        <button type="button" 
                                                class="btn-action-box btn-box-key btn-trigger-reset" 
                                                title="Reset Password"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalResetPassword"
                                                data-id="<?= $u['id'] ?>"
                                                data-name="<?= e($u['name']) ?>">
                                            <i class="bi bi-key-fill" style="transform: rotate(90deg); display: inline-block;"></i>
                                        </button>

                                        <!-- Tombol Hapus (Merah Selaras dengan SweetAlert2) -->
                                        <?php if (!$isCurrent): ?>
                                            <form action="index.php" method="POST" class="d-inline form-delete-confirm" data-name="<?= e($u['name']) ?>" data-title="Hapus Pengguna?">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="hapus">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn-action-box btn-box-delete" title="Hapus User">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn-action-box btn-box-delete btn-self-delete-alert" 
                                                    title="Akun Anda yang sedang aktif">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="py-3 px-4 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span style="font-size: 0.85rem; color: var(--text-secondary);">
                Menampilkan <?= count($usersList) ?> akun pengguna terdaftar
            </span>
        </div>
    </div>

</div>

<!-- ========================================================
     MODAL 1: Tambah User Baru
     ======================================================== -->
<div class="modal fade" id="modalTambahUser" tabindex="-1" aria-labelledby="modalTambahUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 4px; height: 18px; background: var(--accent-primary); border-radius: 4px;"></div>
                    <h5 class="modal-title fw-bold" id="modalTambahUserLabel" style="color: var(--text-primary); font-size: 1.1rem;">
                        Tambah Pengguna Baru
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php" method="POST" autocomplete="off">
                <?= csrfField() ?>
                <!-- Decoy input agar browser password manager tidak otomatis mengisi email dan password -->
                <input type="text" style="display:none" tabindex="-1" autocomplete="username">
                <input type="password" style="display:none" tabindex="-1" autocomplete="current-password">

                <input type="hidden" name="action" value="tambah">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Nama Lengkap -->
                        <div class="col-12 col-md-6">
                            <label class="form-ems-label">Nama Lengkap <span class="required">*</span></label>
                            <input type="text" name="name" class="form-control form-ems-control" placeholder="Contoh: Achmad Tobroni, S.T." autocomplete="off" required>
                        </div>

                        <!-- Email -->
                        <div class="col-12 col-md-6">
                            <label class="form-ems-label">Alamat Email <span class="required">*</span></label>
                            <input type="email" name="email" class="form-control form-ems-control" placeholder="nama@makmur.co.id" autocomplete="new-password" required>
                        </div>

                        <!-- Nomor Telepon -->
                        <div class="col-12 col-md-6">
                            <label class="form-ems-label">Nomor Telepon / WhatsApp</label>
                            <input type="tel" name="phone" class="form-control form-ems-control" placeholder="081553890987" autocomplete="off">
                        </div>

                        <!-- Penugasan / Unit -->
                        <div class="col-12 col-md-6">
                            <label class="form-ems-label">Penugasan / Unit Divisi <span class="required">*</span></label>
                            <select name="unit" class="form-select form-ems-select" required>
                                <option value="Kantor Pusat">Kantor Pusat</option>
                                <option value="Divisi Teknologi & IT">Divisi Teknologi &amp; IT</option>
                                <option value="Divisi Human Resources">Divisi Human Resources</option>
                                <option value="Divisi Operasional & Lapangan">Divisi Operasional &amp; Lapangan</option>
                                <option value="Divisi Keuangan">Divisi Keuangan</option>
                                <option value="Unit Operasional 1">Unit Operasional 1</option>
                                <option value="Unit Pabrik & Produksi">Unit Pabrik &amp; Produksi</option>
                            </select>
                        </div>

                        <!-- Password -->
                        <div class="col-12 col-md-6">
                            <label class="form-ems-label">Password <span class="required">*</span></label>
                            <input type="password" name="password" class="form-control form-ems-control" placeholder="Minimal 6 karakter" autocomplete="new-password" required>
                        </div>

                        <!-- Konfirmasi Password -->
                        <div class="col-12 col-md-6">
                            <label class="form-ems-label">Konfirmasi Password <span class="required">*</span></label>
                            <input type="password" name="password_confirm" class="form-control form-ems-control" placeholder="Ulangi password" autocomplete="new-password" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-danger-custom" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-orange">
                        Simpan User Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL 2: Edit Data User
     ======================================================== -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 4px; height: 18px; background: var(--accent-primary); border-radius: 4px;"></div>
                    <h5 class="modal-title fw-bold" id="modalEditUserLabel" style="color: var(--text-primary); font-size: 1.1rem;">
                        Edit Data Pengguna
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php" method="POST" id="form-edit-user" autocomplete="off">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Nama Lengkap -->
                        <div class="col-12 col-md-6">
                            <label class="form-ems-label">Nama Lengkap <span class="required">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control form-ems-control" required>
                        </div>

                        <!-- Email -->
                        <div class="col-12 col-md-6">
                            <label class="form-ems-label">Alamat Email <span class="required">*</span></label>
                            <input type="email" name="email" id="edit_email" class="form-control form-ems-control" required>
                        </div>

                        <!-- Nomor Telepon -->
                        <div class="col-12 col-md-6">
                            <label class="form-ems-label">Nomor Telepon / WhatsApp</label>
                            <input type="tel" name="phone" id="edit_phone" class="form-control form-ems-control">
                        </div>

                        <!-- Penugasan / Unit -->
                        <div class="col-12 col-md-6">
                            <label class="form-ems-label">Penugasan / Unit Divisi <span class="required">*</span></label>
                            <select name="unit" id="edit_unit" class="form-select form-ems-select" required>
                                <option value="Kantor Pusat">Kantor Pusat</option>
                                <option value="Divisi Teknologi & IT">Divisi Teknologi &amp; IT</option>
                                <option value="Divisi Human Resources">Divisi Human Resources</option>
                                <option value="Divisi Operasional & Lapangan">Divisi Operasional &amp; Lapangan</option>
                                <option value="Divisi Keuangan">Divisi Keuangan</option>
                                <option value="Unit Operasional 1">Unit Operasional 1</option>
                                <option value="Unit Pabrik & Produksi">Unit Pabrik &amp; Produksi</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-danger-custom" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-orange">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================
     MODAL 3: Reset Password User
     ======================================================== -->
<div class="modal fade" id="modalResetPassword" tabindex="-1" aria-labelledby="modalResetPasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 4px; height: 18px; background: var(--accent-primary); border-radius: 4px;"></div>
                    <h5 class="modal-title fw-bold" id="modalResetPasswordLabel" style="color: var(--text-primary); font-size: 1.1rem;">
                        Reset Password Pengguna
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php" method="POST" id="form-reset-password" autocomplete="off">
                <?= csrfField() ?>
                <input type="text" style="display:none" tabindex="-1" autocomplete="username">
                <input type="password" style="display:none" tabindex="-1" autocomplete="current-password">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" id="reset_id">
                <div class="modal-body p-4">
                    <p class="mb-3" style="font-size: 0.9rem; color: var(--text-secondary);">
                        Mengatur ulang kata sandi untuk akun: <strong id="reset_user_name" style="color: var(--text-primary);"></strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-ems-label">Password Baru <span class="required">*</span></label>
                        <input type="password" name="new_password" class="form-control form-ems-control" placeholder="Minimal 6 karakter" autocomplete="new-password" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-ems-label">Konfirmasi Password Baru <span class="required">*</span></label>
                        <input type="password" name="new_password_confirm" class="form-control form-ems-control" placeholder="Ulangi password baru" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-danger-custom" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-orange">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script Pembantu untuk Pengisian Data Otomatis ke Modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Tangkap klik pada tombol Edit untuk mengisi modal
    const editButtons = document.querySelectorAll('.btn-trigger-edit');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id || '';
            document.getElementById('edit_name').value = this.dataset.name || '';
            document.getElementById('edit_email').value = this.dataset.email || '';
            document.getElementById('edit_phone').value = this.dataset.phone || '';
            document.getElementById('edit_unit').value = this.dataset.unit || 'Kantor Pusat';
        });
    });

    // 2. Tangkap klik pada tombol Reset Password untuk mengisi modal
    const resetButtons = document.querySelectorAll('.btn-trigger-reset');
    resetButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('reset_id').value = this.dataset.id || '';
            document.getElementById('reset_user_name').textContent = this.dataset.name || '';
            const passInputs = document.querySelectorAll('#modalResetPassword input[type="password"]');
            passInputs.forEach(inp => inp.value = '');
        });
    });

    // 3. Pastikan form modal Tambah User selalu kosong bersih saat dibuka
    const modalTambah = document.getElementById('modalTambahUser');
    if (modalTambah) {
        modalTambah.addEventListener('show.bs.modal', function () {
            const form = modalTambah.querySelector('form');
            if (form) {
                form.reset();
                form.querySelectorAll('input:not([type="hidden"])').forEach(inp => inp.value = '');
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
