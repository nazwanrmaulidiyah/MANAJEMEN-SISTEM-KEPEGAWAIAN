<?php
/**
 * SIMAWA — Konfigurasi Database & Helper Sistem
 * Menggunakan PDO (PHP Data Objects) untuk Keamanan Query
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------------
// Penentuan BASE_URL Dinamis otomatis mengikuti nama folder proyek (ems-native / SIMAWA)
// -------------------------------------------------------------
if (!defined('BASE_URL')) {
    $folderName = basename(dirname(__DIR__));
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    
    if (!empty($folderName) && strpos($scriptName, '/' . $folderName) !== false) {
        define('BASE_URL', '/' . $folderName . '/');
    } elseif (strpos($scriptName, '/SIMAWA') !== false || strpos($scriptName, '/simawa') !== false) {
        define('BASE_URL', '/SIMAWA/');
    } elseif (strpos($scriptName, '/ems-native') !== false) {
        define('BASE_URL', '/ems-native/');
    } else {
        // Gunakan nama folder fisik sebagai basis URL default
        define('BASE_URL', '/' . $folderName . '/');
    }
}
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/employees/');

$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'ems_db';
$db_user = 'root';
$db_pass = '123456';

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("Koneksi database PDO gagal: " . $e->getMessage());
}

/**
 * Helper: Cek apakah user sudah login
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Helper: Proteksi halaman, redirect ke login jika belum login
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        $_SESSION['info'] = 'Silakan login terlebih dahulu untuk mengakses sistem.';
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

/**
 * Helper: Redirect ke dashboard jika sudah login
 */
function redirectIfAuthenticated(): void {
    if (isLoggedIn()) {
        header('Location: ' . BASE_URL . 'dashboard/index.php');
        exit;
    }
}

/**
 * Helper: Sanitasi input HTML untuk mencegah XSS
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Helper: Mendapatkan URL foto pegawai atau Avatar bawaan
 */
function getEmployeePhotoUrl(?string $foto, string $nama): string {
    if (!empty($foto) && file_exists(UPLOAD_DIR . $foto)) {
        return BASE_URL . 'assets/uploads/employees/' . $foto;
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($nama) . '&background=f97316&color=ffffff&size=200';
}

/**
 * Helper: Format tanggal Indonesia (contoh: 15 Januari 2024)
 */
function formatDateIndo(?string $dateStr): string {
    if (empty($dateStr)) return '-';
    $timestamp = strtotime($dateStr);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('d', $timestamp);
    $m = (int)date('m', $timestamp);
    $y = date('Y', $timestamp);
    return "{$d} {$bulan[$m]} {$y}";
}

/**
 * Helper: Menghitung masa kerja pegawai
 */
function calculateTenure(?string $tanggalMasukStr): string {
    if (empty($tanggalMasukStr)) return '-';
    $masuk = new DateTime($tanggalMasukStr);
    $sekarang = new DateTime();
    $diff = $masuk->diff($sekarang);

    $parts = [];
    if ($diff->y > 0) $parts[] = $diff->y . ' tahun';
    if ($diff->m > 0) $parts[] = $diff->m . ' bulan';
    if (empty($parts)) $parts[] = $diff->d . ' hari';

    return implode(' ', $parts);
}

/**
 * Helper: Identifikasi tema dot departemen untuk tampilan visual yang bersih
 */
function getDeptBadgeClass(?string $dept): string {
    $dept = strtolower($dept ?? '');
    if (strpos($dept, 'teknologi') !== false || strpos($dept, 'it') !== false) return 'dot-it';
    if (strpos($dept, 'human') !== false || strpos($dept, 'hr') !== false) return 'dot-hr';
    if (strpos($dept, 'keuangan') !== false || strpos($dept, 'finance') !== false) return 'dot-finance';
    if (strpos($dept, 'pemasaran') !== false || strpos($dept, 'marketing') !== false) return 'dot-marketing';
    if (strpos($dept, 'operasional') !== false || strpos($dept, 'ops') !== false) return 'dot-ops';
    if (strpos($dept, 'desain') !== false || strpos($dept, 'kreatif') !== false) return 'dot-design';
    return 'dot-general';
}

/**
 * Helper: Ambil atau buat CSRF token sesi
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Helper: Validasi CSRF token dengan hash_equals
 */
function validateCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Helper: Render hidden input field CSRF untuk form HTML
 */
function csrfField(): string {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

