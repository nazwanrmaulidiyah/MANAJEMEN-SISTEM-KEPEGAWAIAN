<?php
/**
 * SIMAWA — Employee Management System (PHP Native)
 * Entry Point Utama Aplikasi
 */
require_once __DIR__ . '/config/database.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

header('Location: ' . BASE_URL . 'dashboard/index.php');
exit;
