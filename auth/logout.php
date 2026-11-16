<?php
require_once __DIR__ . '/../config/database.php';

// Hapus semua data sesi
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Mulai sesi baru untuk pesan flash notifikasi
session_start();
$_SESSION['info'] = 'Anda telah berhasil logout dari sistem.';
header('Location: ' . BASE_URL . 'auth/login.php');
exit;

