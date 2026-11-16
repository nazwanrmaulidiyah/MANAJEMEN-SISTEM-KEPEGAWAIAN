<?php
/**
 * SIMAWA — Dashboard Utama
 * PT MAKMUR
 * 
 * Halaman ringkasan metrik statistik personil, distribusi departemen, tren bulanan,
 * tabel personil terbaru, dan panel aksi cepat via asinkronus jQuery AJAX.
 */
require_once __DIR__ . '/../config/database.php';
requireAuth();

$page_title = 'Dashboard';
$page_subtitle = '';

// Ambil nama admin yang sedang aktif dari session
$adminName = $_SESSION['user']['name'] ?? 'Administrator';

// Format tanggal hari ini dalam Bahasa Indonesia
$namaHariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$namaBulanIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$hariIni = $namaHariIndo[date('w')];
$tglIni = date('d');
$blnIni = $namaBulanIndo[(int)date('m')];
$thnIni = date('Y');
$tanggalHariIni = "{$hariIni}, {$tglIni} {$blnIni} {$thnIni}";

// Query data awal via PHP (Render Instan, Bersih Tanpa Kedipan)
$totalPegawai = (int)$pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$totalDept = (int)$pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$pegawaiBaru = (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE tanggal_masuk >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
$totalAdmin = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Helper untuk menghasilkan 2 inisial huruf kapital (selaras dengan tampilan sebelumnya)
function getEmpInitials(string $name): string {
    $words = preg_split('/\s+/', trim($name));
    if (count($words) >= 2) {
        return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    }
    return mb_strtoupper(mb_substr($name, 0, 2));
}

// Query daftar departemen beserta jumlah pegawai (urut dari terbanyak sesuai visual sebelumnya)
$deptList = $pdo->query("
    SELECT d.id, d.nama, COUNT(e.id) AS total_pegawai
    FROM departments d
    LEFT JOIN employees e ON d.nama = e.departemen
    GROUP BY d.id, d.nama
    ORDER BY total_pegawai DESC, d.nama ASC
")->fetchAll();

// Query 5 pegawai terbaru
$recentEmployees = $pdo->query("
    SELECT id, nip, nama, jabatan, departemen, foto
    FROM employees
    ORDER BY id DESC
    LIMIT 5
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- CSS Khusus Dashboard (Terpisah & Non-Inline) -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css?v=<?= filemtime(__DIR__ . '/../assets/css/dashboard.css') ?>">

<div class="container-fluid p-0">

    <!-- ========================================================
         1. Kotak Selamat Datang (Sesuai Asli Tanpa Tombol / Form)
         ======================================================== -->
    <div class="dash-banner mb-4">
        <div class="dash-banner-content">
            <div class="dash-date-pill mb-2">
                <i class="bi bi-calendar3 text-warning"></i>
                <span><?= $tanggalHariIni ?></span>
            </div>
            <h2 class="dash-banner-title mb-1" style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);">
                Selamat datang kembali, <?= e($adminName) ?> 👋
            </h2>
            <p class="dash-banner-desc mb-0" style="color: var(--text-muted); font-size: 0.9rem;">
                Pantau metrik kepegawaian, sebaran divisi personil, dan kelola operasional SDM PT MAKMUR secara terintegrasi dan akurat.
            </p>
        </div>
    </div>

    <!-- ========================================================
         2. Empat Kartu Statistik (Bersih Tanpa Tag Kecil)
         ======================================================== -->
    <div class="row g-3 mb-4">
        <!-- Kartu 1: Total Pegawai (Aksen Oranye SIMAWA) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="<?= BASE_URL ?>pegawai/index.php" class="dash-stat-card-highlight text-decoration-none">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-highlight-val" id="val-total-pegawai"><?= number_format($totalPegawai) ?></div>
                        <div class="stat-highlight-label">Total Pegawai</div>
                    </div>
                    <div class="stat-highlight-icon-box">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Kartu 2: Kelola Departemen -->
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="<?= BASE_URL ?>departemen/index.php" class="dash-stat-card-dark text-decoration-none">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-dark-val" id="val-total-dept"><?= number_format($totalDept) ?></div>
                        <div class="stat-dark-label">Kelola Departemen</div>
                    </div>
                    <div class="stat-dark-icon-box icon-amber">
                        <i class="bi bi-buildings-fill"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Kartu 3: Pegawai Baru -->
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="<?= BASE_URL ?>pegawai/index.php" class="dash-stat-card-dark text-decoration-none">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-dark-val" id="val-pegawai-baru"><?= number_format($pegawaiBaru) ?></div>
                        <div class="stat-dark-label">Pegawai Baru</div>
                    </div>
                    <div class="stat-dark-icon-box icon-emerald">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Kartu 4: Kelola Admin -->
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="<?= BASE_URL ?>users/index.php" class="dash-stat-card-dark text-decoration-none">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-dark-val" id="val-total-admin"><?= number_format($totalAdmin) ?></div>
                        <div class="stat-dark-label">Kelola Admin</div>
                    </div>
                    <div class="stat-dark-icon-box icon-purple">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- ========================================================
         POIN 1: Tag Jumlah Pegawai per Departemen
         ======================================================== -->
    <div class="dash-dept-tags-box mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <span class="fw-bold small" style="color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.5px;">Jumlah Pegawai di Departemen</span>
            </div>
            <span class="small" style="color: var(--text-hint);">Total <?= count($deptList) ?> Departemen</span>
        </div>

        <!-- Render Langsung via PHP: Bersih, Rapi, Tanpa Kotak Kosong Mengambang -->
        <div id="dept-tags-container" class="d-flex flex-wrap gap-2">
            <?php 
            $palette = ['#3b82f6', '#10b981', '#a855f7', '#f59e0b', '#06b6d4', '#ec4899', '#6366f1'];
            foreach ($deptList as $idx => $dept): 
                $dotColor = $palette[$idx % count($palette)];
            ?>
                <a href="<?= BASE_URL ?>pegawai/index.php?departemen=<?= urlencode($dept['nama']) ?>" class="dash-dept-pill text-decoration-none">
                    <span class="dept-pill-dot" style="background-color: <?= $dotColor ?>;"></span>
                    <span class="dept-pill-name"><?= e($dept['nama']) ?></span>
                    <span class="dept-pill-count" style="color: <?= $dotColor ?>; background-color: <?= $dotColor ?>20;"><?= (int)$dept['total_pegawai'] ?></span>
                </a>
            <?php endforeach; ?>
            <?php if (empty($deptList)): ?>
                <span class="text-muted small">Belum ada data departemen.</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================
         3. Grafik Batang Sebaran Pegawai (Penuh Tanpa Aksi Cepat)
         ======================================================== -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="dash-chart-card mb-0">
                <div class="dash-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="dash-title-text mb-1">Sebaran Pegawai per Departemen</h5>
                        <p class="small mb-0" style="color: var(--text-hint);">Distribusi personil aktif di seluruh unit operasional</p>
                    </div>
                </div>
                <div class="dash-card-body p-3">
                    <div class="dash-chart-canvas-wrap dash-bar-canvas-wrap" style="height: 320px;">
                        <canvas id="chartDeptBar"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         4. Tabel Data Pegawai (3 Kolom: Nama, Jabatan, Departemen)
         ======================================================== -->
    <div class="dash-chart-card mb-4">
        <div class="dash-card-header d-flex justify-content-between align-items-center px-4 py-3">
            <div>
                <h5 class="dash-title-text mb-1">Data Pegawai</h5>
                <p class="small mb-0" style="color: var(--text-hint);">Daftar personil yang terdaftar di SIMAWA</p>
            </div>
            <a href="<?= BASE_URL ?>pegawai/index.php" class="btn btn-sm text-white d-inline-flex align-items-center gap-1.5 px-3 py-1.5" style="background: var(--accent-gradient, #f97316); border-radius: 8px; font-size: 0.82rem; font-weight: 600; border: none; box-shadow: 0 2px 8px rgba(249, 115, 22, 0.35);">
                <span>Lihat Semua</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="p-0">
            <div class="table-responsive">
                <table class="dash-table" style="table-layout: fixed; width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 44%; padding-left: 24px;">Nama Pegawai</th>
                            <th style="width: 31%;">Jabatan</th>
                            <th class="text-end" style="width: 25%; padding-right: 24px;">Departemen</th>
                        </tr>
                    </thead>
                    <tbody id="recent-employees-tbody">
                        <?php foreach ($recentEmployees as $emp): 
                            $empInitial = getEmpInitials($emp['nama']);
                        ?>
                            <tr>
                                <td style="width: 44%; padding-left: 24px;">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($emp['foto']) && file_exists(__DIR__ . '/../assets/uploads/employees/' . $emp['foto'])): ?>
                                            <img src="<?= BASE_URL ?>assets/uploads/employees/<?= e($emp['foto']) ?>" alt="<?= e($emp['nama']) ?>" class="rounded-circle object-fit-cover flex-shrink-0" style="width: 38px; height: 38px; border: 1px solid var(--border-color);">
                                        <?php else: ?>
                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0" style="width: 38px; height: 38px; background: linear-gradient(135deg, #f97316, #ea580c); font-size: 0.82rem; letter-spacing: 0.5px;">
                                                <?= e($empInitial) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="fw-semibold ps-2" style="color: var(--text-primary); font-size: 0.88rem;">
                                            <?= e($emp['nama']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="width: 31%; color: var(--text-secondary); font-size: 0.86rem;">
                                    <?= e($emp['jabatan'] ?? '-') ?>
                                </td>
                                <td class="text-end" style="width: 25%; padding-right: 24px;">
                                    <span class="badge rounded-pill" style="background: transparent; color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.35); padding: 5px 14px; font-size: 0.78rem; font-weight: 500;">
                                        <?= e($emp['departemen'] ?? 'Umum') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentEmployees)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4 small">Belum ada data pegawai.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Library Chart.js (Local Vendor Asset) -->
<script src="<?= BASE_URL ?>assets/vendor/chartjs/chart.umd.min.js"></script>

<!-- Inisialisasi Grafik Chart.js Langsung dari Data PHP (Tanpa API & Tanpa AJAX) -->
<script>
(function() {
    function initDeptChart() {
        const canvas = document.getElementById('chartDeptBar');
        if (!canvas) return;
        if (typeof Chart === 'undefined') {
            setTimeout(initDeptChart, 60);
            return;
        }

        const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
        const textColor = isDark ? '#cbd5e1' : '#334155';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.07)' : 'rgba(0, 0, 0, 0.06)';

        // Data PHP langsung di-encode ke format JSON murni tanpa API eksternal
        const labels = <?= json_encode(array_column($deptList, 'nama')) ?>;
        const values = <?= json_encode(array_map('intval', array_column($deptList, 'total_pegawai'))) ?>;

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Pegawai',
                    data: values,
                    backgroundColor: 'rgba(249, 115, 22, 0.85)',
                    borderColor: '#ea580c',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false,
                    hoverBackgroundColor: '#ea580c',
                    maxBarThickness: 52
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#0f172a',
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(249, 115, 22, 0.4)',
                        borderWidth: 1,
                        padding: 10,
                        callbacks: {
                            label: function (context) {
                                const val = context.parsed.y !== undefined ? context.parsed.y : context.raw;
                                return ' ' + context.label + ': ' + val + ' Pegawai';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: textColor,
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 13,
                                weight: '500'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor,
                            precision: 0,
                            stepSize: 1,
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 12
                            }
                        },
                        grid: {
                            color: gridColor
                        }
                    }
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDeptChart);
    } else {
        initDeptChart();
    }
})();
</script>
