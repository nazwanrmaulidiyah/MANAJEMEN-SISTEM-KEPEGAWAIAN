<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();

$page_title = 'Kelola Departemen';
$page_subtitle = 'Kelola struktur divisi dan departemen operasional PT MAKMUR';

$errors = [];

// Proteksi Keamanan CSRF Token untuk seluruh request POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Token keamanan CSRF tidak valid atau sesi telah berakhir. Silakan coba kembali.';
        header('Location: index.php');
        exit;
    }
}

// 1. Proses Tambah Departemen Baru (via Modal Tambah)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama = trim($_POST['nama'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Validasi
    if (empty($nama)) {
        $errors[] = 'Nama departemen wajib diisi.';
    } elseif (strlen($nama) < 3) {
        $errors[] = 'Nama departemen minimal 3 karakter.';
    } else {
        // Cek duplikasi nama
        $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE nama = ?");
        $stmtCek->execute([$nama]);
        if ($stmtCek->fetchColumn() > 0) {
            $errors[] = 'Nama departemen "' . htmlspecialchars($nama) . '" sudah ada di sistem.';
        }
    }

    if (empty($errors)) {
        $stmtInsert = $pdo->prepare("INSERT INTO departments (nama, keterangan) VALUES (?, ?)");
        $stmtInsert->execute([$nama, $keterangan]);
        $_SESSION['success'] = "Departemen '{$nama}' berhasil ditambahkan!";
        header('Location: index.php');
        exit;
    }
}

// 2. Proses Hapus Departemen
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus') {
    $id = (int)($_POST['id'] ?? 0);

    // Ambil nama departemen
    $stmtDept = $pdo->prepare("SELECT nama FROM departments WHERE id = ?");
    $stmtDept->execute([$id]);
    $dept = $stmtDept->fetch();

    if ($dept) {
        // Cek apakah masih digunakan oleh pegawai
        $stmtCekEmp = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE departemen = ?");
        $stmtCekEmp->execute([$dept['nama']]);
        $empCount = (int)$stmtCekEmp->fetchColumn();

        if ($empCount > 0) {
            $_SESSION['error'] = 'Departemen "' . $dept['nama'] . '" tidak dapat dihapus karena masih memiliki ' . $empCount . ' pegawai aktif.';
        } else {
            $stmtDel = $pdo->prepare("DELETE FROM departments WHERE id = ?");
            $stmtDel->execute([$id]);
            $_SESSION['success'] = 'Departemen "' . $dept['nama'] . '" berhasil dihapus.';
        }
    }
    header('Location: index.php');
    exit;
}

// 3. Ambil seluruh data departemen beserta kalkulasi total pegawai
$stmt = $pdo->query("
    SELECT d.id, d.nama, d.keterangan,
           (SELECT COUNT(*) FROM employees e WHERE e.departemen = d.nama) AS total_pegawai
    FROM departments d 
    ORDER BY d.id ASC
");
$departmentsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    <!-- 1 Kartu Tabel Penuh: Daftar Departemen (Tanpa Split 2 Kolom) -->
    <div class="card-ems fade-in">
        <div class="card-ems-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 4px; height: 18px; background: var(--accent-primary); border-radius: 4px;"></div>
                <h5 class="fw-bold mb-0" style="color: var(--text-primary); font-size: 1.05rem;">
                    Kelola Departemen
                </h5>
            </div>

            <!-- Aksi di Sebelah Kanan: Tombol Tambah Departemen (Selaras dengan Tombol Lain) -->
            <button type="button" class="btn-tambah-user" data-bs-toggle="modal" data-bs-target="#modalTambahDepartemen">
                <i class="bi bi-plus-lg"></i> Tambah Departemen
            </button>
        </div>

        <div class="table-responsive">
            <table class="table-ems mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 70px;">NO</th>
                        <th>NAMA DEPARTEMEN</th>
                        <th class="text-center" style="width: 180px;">TOTAL PEGAWAI</th>
                        <th class="text-center" style="width: 100px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($departmentsList)): ?>
                        <?php foreach ($departmentsList as $idx => $d): ?>
                            <tr>
                                <!-- Kolom 1: No (Jelas Terlihat) -->
                                <td class="text-center fw-semibold text-secondary" style="color: var(--text-secondary) !important;">
                                    <?= $idx + 1 ?>
                                </td>

                                <!-- Kolom 2: Nama Departemen (Selaras Data Pegawai, Tidak Bold) -->
                                <td>
                                    <span style="color: var(--text-primary); font-size: 0.92rem; font-weight: 500;">
                                        <?= e($d['nama']) ?>
                                    </span>
                                </td>

                                <!-- Kolom 3: Total Pegawai (Teks Langsung Bersih, Tidak Bold Berlebihan) -->
                                <td class="text-center" style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 500;">
                                    <?= (int)$d['total_pegawai'] ?> Orang
                                </td>

                                <!-- Kolom 4: Aksi (Tombol Merah Selaras) -->
                                <td class="text-center">
                                    <form action="index.php" method="POST" class="d-inline form-delete-confirm" data-name="Departemen <?= e($d['nama']) ?>" data-title="Hapus Departemen?">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="hapus">
                                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                        <button type="submit" class="btn-action-box btn-box-delete" title="Hapus Departemen">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div style="color: var(--text-muted);">
                                    <i class="bi bi-building-x fs-1 d-block mb-2 text-warning"></i>
                                    <h5 class="fw-bold" style="color: var(--text-primary);">Belum Ada Departemen</h5>
                                    <p class="small mb-3" style="color: var(--text-secondary);">
                                        Belum ada data departemen yang terdaftar di dalam sistem.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="py-3 px-4 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span style="font-size: 0.85rem; color: var(--text-secondary);">
                Menampilkan <?= count($departmentsList) ?> departemen aktif
            </span>
        </div>
    </div>

</div>

<!-- ========================================================
     MODAL: Tambah Departemen Baru
     ======================================================== -->
<div class="modal fade" id="modalTambahDepartemen" tabindex="-1" aria-labelledby="modalTambahDepartemenLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 4px; height: 18px; background: var(--accent-primary); border-radius: 4px;"></div>
                    <h5 class="modal-title fw-bold" id="modalTambahDepartemenLabel" style="color: var(--text-primary); font-size: 1.1rem;">
                        Tambah Departemen Baru
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php" method="POST" autocomplete="off">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-ems-label">Nama Departemen <span class="required">*</span></label>
                        <input type="text" 
                               name="nama" 
                               class="form-control form-ems-control" 
                               placeholder="Contoh: Logistik & Distribusi" 
                               required>
                    </div>

                    <div class="mb-2">
                        <label class="form-ems-label">Keterangan / Fungsi Divisi</label>
                        <textarea name="keterangan" 
                                  class="form-control form-ems-control" 
                                  rows="3" 
                                  placeholder="Deskripsi tugas atau unit kerja (opsional)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-danger-custom" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-orange">
                        Simpan Departemen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
