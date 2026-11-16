<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();

$page_title = 'Data Pegawai';
$page_subtitle = 'Kelola seluruh data personil dan informasi kepegawaian PT MAKMUR';

// 1. Tangkap parameter pencarian kata kunci dan filter departemen dari URL
$search = trim($_GET['search'] ?? '');
$departemen = trim($_GET['departemen'] ?? '');

// 2. Pengaturan paginasi data (10 data per halaman)
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 3. Susun query pencarian dinamis dengan PDO Prepared Statement
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(nip LIKE ? OR nama LIKE ? OR email LIKE ? OR jabatan LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($departemen)) {
    $whereClauses[] = "departemen = ?";
    $params[] = $departemen;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// 4. Hitung total jumlah record untuk menentukan jumlah halaman
$countSql = "SELECT COUNT(*) AS total FROM employees {$whereSql}";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalRows = (int)$stmtCount->fetch()['total'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));

if ($page > $totalPages && $totalRows > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 5. Ambil data pegawai sesuai batas halaman (LIMIT & OFFSET)
$dataSql = "SELECT * FROM employees {$whereSql} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}";
$stmtData = $pdo->prepare($dataSql);
$stmtData->execute($params);
$employees = $stmtData->fetchAll();

// 6. Ambil daftar departemen dari Master Departemen (fallback ke distinct employees)
$stmtDept = $pdo->query("SELECT nama FROM departments ORDER BY nama ASC");
$departments = $stmtDept->fetchAll(PDO::FETCH_COLUMN);
if (empty($departments)) {
    $stmtDept = $pdo->query("SELECT DISTINCT departemen FROM employees ORDER BY departemen ASC");
    $departments = $stmtDept->fetchAll(PDO::FETCH_COLUMN);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid p-0">

    <!-- Card Filter & Pencarian (Kompak, Rapat & Presisi Sesuai Permintaan) -->
    <div class="card-ems mb-3 fade-in">
        <div class="p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width: 4px; height: 16px; background: var(--accent-primary); border-radius: 4px;"></div>
                <h6 class="fw-bold mb-0" style="color: var(--text-primary); font-size: 0.9rem; letter-spacing: 0.5px;">
                    FILTER DATA
                </h6>
            </div>
            <form action="index.php" method="GET" class="row g-2 align-items-center">
                <!-- Pilihan Departemen -->
                <div class="col-12 col-md-4">
                    <select name="departemen" class="form-select form-ems-select" onchange="this.form.submit()">
                        <option value="">Semua Departemen</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= e($dept) ?>" <?= ($departemen === $dept) ? 'selected' : '' ?>>
                                <?= e($dept) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Kata Kunci Pencarian -->
                <div class="col-12 col-md-6">
                    <div class="input-group search-input-group">
                        <span class="input-group-text search-icon">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" 
                               name="search" 
                               class="form-control form-ems-control" 
                               placeholder="Cari nama pegawai, NIP, email, jabatan..." 
                               value="<?= e($search) ?>"
                               autocomplete="off">
                    </div>
                </div>

                <!-- Tombol Cari & Reset (Kotak dengan Sudut Rounded Lembut) -->
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-search-icon" title="Cari Data">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="index.php" class="btn btn-reset-icon" title="Reset Semua Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Card Tabel Data Pegawai (Sesuai Referensi Gambar Pengguna) -->
    <div class="card-ems fade-in delay-2">
        <div class="card-ems-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 4px; height: 18px; background: var(--accent-primary); border-radius: 4px;"></div>
                <h5 class="fw-bold mb-0" style="color: var(--text-primary); font-size: 1.15rem;">
                    Daftar Data Pegawai
                </h5>
            </div>
            <a href="export.php<?= (!empty($departemen) || !empty($search)) ? '?' . http_build_query(['search' => $search, 'departemen' => $departemen]) : '' ?>" 
               class="btn btn-sm btn-excel" 
               title="Download Data Pegawai ke Format Excel">
                <i class="bi bi-file-earmark-excel-fill me-1"></i> Download Data
            </a>
        </div>

        <div class="table-responsive">
            <table class="table-ems mb-0" id="table-employees">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">NO</th>
                        <th style="min-width: 240px;">NAMA PEGAWAI</th>
                        <th style="min-width: 220px;">KONTAK</th>
                        <th style="min-width: 200px;">JABATAN & DEPT</th>
                        <th style="min-width: 160px;">TANGGAL MASUK</th>
                        <th class="text-center" style="min-width: 140px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $idx => $emp): ?>
                            <tr id="row-employee-<?= $emp['id'] ?>">
                                <!-- Nomor Urut (Jelas Terlihat) -->
                                <td class="text-center fw-semibold text-secondary" style="color: var(--text-secondary) !important;">
                                    <?= $offset + $idx + 1 ?>
                                </td>

                                <!-- 1. Nama Pegawai & NIP Monospace -->
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= getEmployeePhotoUrl($emp['foto'], $emp['nama']) ?>" 
                                             alt="<?= e($emp['nama']) ?>" 
                                             class="rounded-circle me-3 border border-2" 
                                             width="42" 
                                             height="42" 
                                             style="object-fit: cover; border-color: var(--border-strong) !important;">
                                        <div>
                                            <a href="detail.php?id=<?= $emp['id'] ?>" class="fw-bold d-block" style="color: var(--text-primary); font-size: 0.94rem;">
                                                <?= e($emp['nama']) ?>
                                            </a>
                                            <span class="emp-nip-mono"><?= e($emp['nip']) ?></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- 2. Kontak -->
                                <td>
                                    <div class="emp-contact-item">
                                        <i class="bi bi-envelope"></i>
                                        <a href="mailto:<?= e($emp['email']) ?>"><?= e($emp['email']) ?></a>
                                    </div>
                                    <div class="emp-contact-item text-phone mt-1">
                                        <i class="bi bi-telephone"></i>
                                        <span><?= e($emp['no_hp']) ?></span>
                                    </div>
                                </td>

                                <!-- 3. Jabatan & Departemen Tag (Dengan Ikon Gedung, Tanpa Titik Membingungkan) -->
                                <td>
                                    <div class="fw-bold" style="color: var(--text-primary); font-size: 0.88rem;"><?= e($emp['jabatan']) ?></div>
                                    <div class="emp-dept-tag mt-1">
                                        <i class="bi bi-building me-1 opacity-75"></i> <?= e($emp['departemen']) ?>
                                    </div>
                                </td>

                                <!-- 4. Tanggal Masuk (Langsung Format Indonesia Tanpa Icon) -->
                                <td>
                                    <span class="text-date-clean">
                                        <?= formatDateIndo($emp['tanggal_masuk']) ?>
                                    </span>
                                </td>

                                <!-- 5. Aksi: Tombol Kotak dengan Sudut Rounded Lembut & Berwarna Beda -->
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center justify-content-center gap-2">
                                        <!-- Detail: Kotak Biru Cyan Solid -->
                                        <a href="detail.php?id=<?= $emp['id'] ?>" 
                                           class="btn-action-box btn-box-detail" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Lihat Detail Profil">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        <!-- Edit: Kotak Oranye Amber Solid -->
                                        <a href="edit.php?id=<?= $emp['id'] ?>" 
                                           class="btn-action-box btn-box-edit" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Edit Data Pegawai">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        <!-- Hapus: Kotak Merah Crimson Solid (AJAX SweetAlert2) -->
                                        <button type="button" 
                                                class="btn-action-box btn-box-delete btn-delete-ajax" 
                                                data-url="hapus.php" 
                                                data-id="<?= $emp['id'] ?>" 
                                                data-name="<?= e($emp['nama']) ?>" 
                                                data-row-id="#row-employee-<?= $emp['id'] ?>" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                title="Hapus Pegawai">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div style="color: var(--text-muted);">
                                    <i class="bi bi-folder-x fs-1 d-block mb-2 text-warning"></i>
                                    <h5 class="fw-bold" style="color: var(--text-primary);">Tidak ada data pegawai yang cocok</h5>
                                    <p class="small mb-3" style="color: var(--text-secondary);">Coba ubah kata kunci pencarian atau bersihkan filter departemen.</p>
                                    <a href="index.php" class="btn btn-sm btn-orange">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Bersihkan Filter
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Keterangan Jumlah Data di Tengah (Persis Sesuai Referensi Gambar) -->
        <div class="py-3 text-center border-top border-secondary border-opacity-25">
            <span style="font-size: 0.85rem; color: var(--text-secondary);">
                Menampilkan <strong><?= count($employees) ?></strong> dari total <strong><?= $totalRows ?></strong> data
            </span>
        </div>

        <!-- Paginasi jika lebih dari 1 halaman -->
        <?php if ($totalPages > 1): ?>
            <div class="card-ems-body border-top border-secondary border-opacity-25 d-flex justify-content-center py-3">
                <div class="pagination-ems">
                    <ul class="pagination pagination-sm m-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&departemen=<?= urlencode($departemen) ?>">&laquo;</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($page === $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&departemen=<?= urlencode($departemen) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&departemen=<?= urlencode($departemen) ?>">&raquo;</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

