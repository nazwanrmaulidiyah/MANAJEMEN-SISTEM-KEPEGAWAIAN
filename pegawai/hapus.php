<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
          || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
          || (isset($_POST['id']));

// Proteksi CSRF & Metode HTTP POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid. Hanya POST yang diizinkan.']);
        exit;
    }
    $_SESSION['error'] = 'Metode permintaan tidak valid.';
    header('Location: index.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!validateCsrfToken($csrfToken)) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid atau sesi telah berakhir.']);
        exit;
    }
    $_SESSION['error'] = 'Token CSRF tidak valid atau sesi telah berakhir.';
    header('Location: index.php');
    exit;
}

$id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));

if ($id <= 0) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID pegawai tidak valid.']);
        exit;
    }
    header('Location: index.php');
    exit;
}

// 1. Ambil data pegawai untuk menghapus file foto fisik
$stmtSelect = $pdo->prepare("SELECT nama, nip, foto FROM employees WHERE id = ? LIMIT 1");
$stmtSelect->execute([$id]);
$emp = $stmtSelect->fetch();

if (!$emp) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Data pegawai tidak ditemukan.']);
        exit;
    }
    $_SESSION['error'] = 'Data pegawai tidak ditemukan.';
    header('Location: index.php');
    exit;
}

// 2. Hapus file foto jika ada
if (!empty($emp['foto'])) {
    $filePath = UPLOAD_DIR . $emp['foto'];
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
}

// 3. Eksekusi DELETE dengan PDO Prepared Statement
$stmtDelete = $pdo->prepare("DELETE FROM employees WHERE id = ?");
$stmtDelete->execute([$id]);

$message = "Pegawai '{$emp['nama']}' ({$emp['nip']}) berhasil dihapus.";

// 4. Respon AJAX JSON atau Redirect Web
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message]);
    exit;
}

$_SESSION['success'] = $message;
header('Location: index.php');
exit;

