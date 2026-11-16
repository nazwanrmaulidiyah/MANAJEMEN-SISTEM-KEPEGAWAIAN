<?php
// includes/header.php
if (!isset($page_title)) $page_title = 'SIMAWA';
if (!isset($page_subtitle)) $page_subtitle = 'Sistem Informasi Manajemen Kepegawaian PT MAKMUR';
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= getCsrfToken() ?>">
    <title><?= e($page_title) ?> — SIMAWA PT MAKMUR</title>

    <!-- Favicon Resmi SIMAWA -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/img/logo-simawa.png">

    <!-- Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Library Pre-Existing (Local Vendor Assets) -->
    <link href="<?= BASE_URL ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/vendor/sweetalert2/dark.min.css" rel="stylesheet">
    <!-- Custom CSS Aplikasi -->
    <link href="<?= BASE_URL ?>assets/css/app.css?v=<?= filemtime(__DIR__ . '/../assets/css/app.css') ?>" rel="stylesheet">
    <!-- Inisialisasi Tema (Mencegah kedipan saat load) & Base URL Global -->
    <script>
        const SIMAWA_BASE_URL = '<?= BASE_URL ?>';
        (function() {
            const savedTheme = localStorage.getItem('ems_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <a href="<?= BASE_URL ?>dashboard/index.php" class="sidebar-brand text-decoration-none">
            <img src="<?= BASE_URL ?>assets/img/logo-simawa.png" alt="Logo SIMAWA" class="sidebar-logo-img">
            <span class="sidebar-brand-text">SIMAWA</span>
        </a>

        <ul class="sidebar-menu">
            <li class="menu-header">Menu Utama</li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>dashboard/index.php" class="nav-link <?= ($current_dir === 'dashboard' || $current_page === 'dashboard.php') ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>pegawai/index.php" class="nav-link <?= ($current_dir === 'pegawai' && in_array($current_page, ['index.php', 'detail.php', 'edit.php'])) ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Data Pegawai</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>departemen/index.php" class="nav-link <?= ($current_dir === 'departemen') ? 'active' : '' ?>">
                    <i class="bi bi-buildings-fill"></i>
                    <span>Kelola Departemen</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>users/index.php" class="nav-link <?= ($current_dir === 'users') ? 'active' : '' ?>">
                    <i class="bi bi-person-gear"></i>
                    <span>Kelola Admin</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>pegawai/tambah.php" class="nav-link <?= ($current_dir === 'pegawai' && $current_page === 'tambah.php') ? 'active' : '' ?>">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Tambah Pegawai</span>
                </a>
            </li>
        </ul>

        <!-- Sidebar Footer: Tombol Keluar di Pojok Kiri Bawah (Menyatu Alami dengan Sidebar) -->
        <div class="sidebar-footer mt-auto p-3" style="background: transparent !important; border: none !important;">
            <a href="<?= BASE_URL ?>auth/logout.php" class="btn w-100 btn-sm py-2 btn-logout-confirm text-white text-center fw-bold text-decoration-none" style="background: var(--accent-gradient, #f97316); border-radius: 10px; font-size: 0.85rem; border: none; display: block; box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3);">
                Keluar
            </a>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-dark-subtle d-lg-none me-3" id="sidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="mb-0 fw-bold navbar-title"><?= e($page_title) ?></h5>
                    <?php if (!empty($page_subtitle)): ?>
                        <div class="navbar-subtitle"><?= e($page_subtitle) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex align-items-center" style="gap: 12px;">
                <!-- Tombol Ganti Tema (Terang / Gelap) -->
                <button type="button" class="btn-theme-toggle" id="themeToggleBtn" title="Ganti Tema (Terang / Gelap)">
                    <i class="bi bi-moon-stars-fill" id="themeToggleIcon"></i>
                </button>

                <!-- User Profile Dropdown (Sesuai Referensi Gambar Pengguna) -->
                <?php
                $userName = $_SESSION['user']['name'] ?? 'Administrator EMS';
                $userEmail = $_SESSION['user']['email'] ?? 'admin@ems.test';
                $userInitial = mb_strtoupper(mb_substr(trim($userName), 0, 1));
                ?>
                <div class="dropdown">
                    <button class="btn-user-pill dropdown-toggle" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-initial-avatar">
                            <?= e($userInitial) ?>
                        </div>
                        <span class="user-name-text"><?= e($userName) ?></span>
                        <i class="bi bi-caret-down-fill user-caret-icon ms-1"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu shadow-sm" aria-labelledby="userMenuDropdown">
                        <li class="user-dropdown-header">
                            <div class="user-dropdown-name"><?= e($userName) ?></div>
                            <div class="user-dropdown-email"><?= e($userEmail) ?></div>
                        </li>
                        <li><hr class="dropdown-divider my-0" style="border-color: var(--border-color); opacity: 0.6;"></li>
                        <li>
                            <a class="dropdown-item user-dropdown-item btn-logout-confirm" href="<?= BASE_URL ?>auth/logout.php">
                                <i class="bi bi-box-arrow-right fs-6"></i>
                                <span>Keluar</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Content Body -->
        <main class="content-body">
