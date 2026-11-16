<?php
require_once __DIR__ . '/../config/database.php';

redirectIfAuthenticated();

$errors = [];
$email = $_POST['email'] ?? 'admin@ems.test';

// Proses Submit Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi Keamanan CSRF Token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['login'] = 'Sesi form telah kedaluwarsa atau token tidak valid. Silakan coba kembali.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $captcha = trim($_POST['captcha'] ?? '');

        // 1. Validasi Input Server-Side
        if (empty($email)) {
            $errors['email'] = 'Email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        }

        if (empty($password)) {
            $errors['password'] = 'Password wajib diisi.';
        }

        if ($captcha === '') {
            $errors['captcha'] = 'Jawaban captcha wajib diisi.';
        } elseif ((int)$captcha !== (int)($_SESSION['captcha_expected'] ?? 0)) {
            $errors['captcha'] = 'Jawaban captcha salah. Silakan hitung kembali.';
        }

        // 2. Jika lolos validasi, lakukan pengecekan ke database via PDO Prepared Statement
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login Berhasil
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                ];
                unset($_SESSION['captcha_expected']);

                $_SESSION['success'] = "Selamat datang kembali, " . $user['name'] . "!";
                header('Location: ' . BASE_URL . 'dashboard/index.php');
                exit;
            } else {
                $errors['login'] = 'Email atau password yang Anda masukkan salah.';
            }
        }
    }
}

// Generate Soal Captcha Penjumlahan (Hasil Maksimal 20)
$num1 = rand(1, 10);
$num2 = rand(1, 20 - $num1);
$_SESSION['captcha_expected'] = $num1 + $num2;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMAWA — PT MAKMUR</title>

    <!-- Favicon Resmi SIMAWA -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/img/logo-simawa.png">

    <!-- Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Library Pre-Existing (Local Vendor Assets) -->
    <link href="<?= BASE_URL ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Stylesheet Khusus Login -->
    <link href="<?= BASE_URL ?>assets/css/login.css?v=<?= filemtime(__DIR__ . '/../assets/css/login.css') ?>" rel="stylesheet">
</head>
<body class="login-page">

    <!-- Background Polygonal Mesh (Geometric Glow Sesuai Referensi) -->
    <div class="bg-mesh-wrap">
        <svg class="bg-mesh-svg" viewBox="0 0 1440 900" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <defs>
                <radialGradient id="meshGlow" cx="80%" cy="40%" r="55%">
                    <stop offset="0%" stop-color="#ea580c" stop-opacity="0.32" />
                    <stop offset="60%" stop-color="#ea580c" stop-opacity="0.08" />
                    <stop offset="100%" stop-color="#05070c" stop-opacity="0" />
                </radialGradient>
            </defs>
            <rect width="100%" height="100%" fill="url(#meshGlow)" />
            <polygon points="1440,0 1200,100 1350,280" fill="rgba(249,115,22,0.06)" stroke="rgba(249,115,22,0.3)" stroke-width="1.2" />
            <polygon points="1200,100 1000,60 1100,240" fill="rgba(234,88,12,0.08)" stroke="rgba(249,115,22,0.35)" stroke-width="1.2" />
            <polygon points="1200,100 1100,240 1350,280" fill="rgba(249,115,22,0.04)" stroke="rgba(249,115,22,0.3)" stroke-width="1.2" />
            <polygon points="1000,60 820,180 1100,240" fill="rgba(234,88,12,0.05)" stroke="rgba(249,115,22,0.25)" stroke-width="1.2" />
            <polygon points="820,180 720,340 920,380" fill="rgba(249,115,22,0.07)" stroke="rgba(249,115,22,0.3)" stroke-width="1.2" />
            <polygon points="820,180 920,380 1100,240" fill="rgba(234,88,12,0.09)" stroke="rgba(249,115,22,0.35)" stroke-width="1.2" />
            <polygon points="1100,240 920,380 1220,420" fill="rgba(249,115,22,0.06)" stroke="rgba(249,115,22,0.3)" stroke-width="1.2" />
            <polygon points="1100,240 1220,420 1350,280" fill="rgba(234,88,12,0.08)" stroke="rgba(249,115,22,0.35)" stroke-width="1.2" />
            <polygon points="1350,280 1220,420 1440,460" fill="rgba(249,115,22,0.05)" stroke="rgba(249,115,22,0.3)" stroke-width="1.2" />
            <polygon points="720,340 640,520 840,560" fill="rgba(249,115,22,0.05)" stroke="rgba(249,115,22,0.25)" stroke-width="1.2" />
            <polygon points="720,340 840,560 920,380" fill="rgba(234,88,12,0.07)" stroke="rgba(249,115,22,0.3)" stroke-width="1.2" />
            <polygon points="920,380 840,560 1080,600" fill="rgba(249,115,22,0.06)" stroke="rgba(249,115,22,0.3)" stroke-width="1.2" />
            <polygon points="920,380 1080,600 1220,420" fill="rgba(234,88,12,0.09)" stroke="rgba(249,115,22,0.35)" stroke-width="1.2" />
            <polygon points="1220,420 1080,600 1320,640" fill="rgba(249,115,22,0.05)" stroke="rgba(249,115,22,0.3)" stroke-width="1.2" />
            <polygon points="1220,420 1320,640 1440,460" fill="rgba(234,88,12,0.08)" stroke="rgba(249,115,22,0.35)" stroke-width="1.2" />
            <polygon points="840,560 760,740 980,780" fill="rgba(249,115,22,0.06)" stroke="rgba(249,115,22,0.25)" stroke-width="1.2" />
            <polygon points="840,560 980,780 1080,600" fill="rgba(234,88,12,0.07)" stroke="rgba(249,115,22,0.3)" stroke-width="1.2" />
            <polygon points="1080,600 980,780 1240,820" fill="rgba(249,115,22,0.08)" stroke="rgba(249,115,22,0.35)" stroke-width="1.2" />
            <polygon points="1080,600 1240,820 1320,640" fill="rgba(234,88,12,0.06)" stroke="rgba(249,115,22,0.3)" stroke-width="1.2" />
            <polygon points="1320,640 1240,820 1440,850" fill="rgba(249,115,22,0.05)" stroke="rgba(249,115,22,0.3)" stroke-width="1.2" />
        </svg>
    </div>

    <!-- Login Container -->
    <div class="login-container">
        <!-- 1 Kotak Login Glossy Glassmorphism -->
        <div class="glass-login-card">

            <!-- Header SIMAWA Sesuai Permintaan (Center & Elegan) -->
            <div class="auth-header">
                <div class="text-center mb-3">
                    <img src="<?= BASE_URL ?>assets/img/logo-simawa.png" alt="Logo SIMAWA" style="height: 54px; width: auto; filter: drop-shadow(0 4px 12px rgba(249, 115, 22, 0.45));">
                </div>
                <div class="brand-title">SIMAWA</div>
                <div class="brand-desc">Sistem Manajemen Kepegawaian</div>
                <div class="brand-company">PT MAKMUR</div>
            </div>

            <!-- Flash Alert / Error (Notifikasi Inline Seperti Semula) -->
            <?php if (!empty($_SESSION['info'])): ?>
                <div class="alert alert-info alert-dismissible fade show border-0 bg-info bg-opacity-25 text-white small mb-3 py-2">
                    <i class="bi bi-info-circle me-1"></i> <?= e($_SESSION['info']) ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['info']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 bg-danger bg-opacity-25 text-white small mb-3 py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i> <?= e($_SESSION['error']) ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (!empty($errors['login'])): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 bg-danger bg-opacity-25 text-white small mb-3 py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i> <?= e($errors['login']) ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="login.php" method="POST" id="form-login" novalidate>
                <?= csrfField() ?>

                <!-- Input Email -->
                <div class="mb-3">
                    <label for="login-email" class="form-label">Email<span class="required">*</span></label>
                    <input type="email" 
                           name="email" 
                           id="login-email" 
                           class="form-control glass-input <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" 
                           placeholder="admin@ems.test" 
                           value="<?= e($email) ?>" 
                           required 
                           autofocus>
                    <?php if (!empty($errors['email'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Input Password -->
                <div class="mb-3">
                    <label for="login-password" class="form-label">Password<span class="required">*</span></label>
                    <div class="password-wrap">
                        <input type="password" 
                               name="password" 
                               id="login-password" 
                               class="form-control glass-input <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" 
                               placeholder="••••••••" 
                               value="password123" 
                               required>
                        <button type="button" class="btn-toggle-eye" id="togglePasswordBtn" title="Tampilkan sandi" tabindex="-1">
                            <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Input Captcha Penjumlahan (Hasil Maksimal 20) -->
                <div class="mb-3">
                    <label for="login-captcha" class="form-label">
                        Berapa <?= $num1 ?> + <?= $num2 ?> ?<span class="required">*</span>
                    </label>
                    <input type="number" 
                           name="captcha" 
                           id="login-captcha" 
                           class="form-control glass-input <?= !empty($errors['captcha']) ? 'is-invalid' : '' ?>" 
                           placeholder="Masukkan hasil penjumlahan" 
                           required>
                    <?php if (!empty($errors['captcha'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['captcha']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Ingat Saya & Lupa Kata Sandi -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check m-0 d-flex align-items-center gap-2">
                        <input class="form-check-input glass-check" type="checkbox" name="remember" id="remember" checked>
                        <label class="form-check-label check-label" for="remember">
                            Ingat saya
                        </label>
                    </div>
                    
                </div>

                <!-- Tombol Masuk -->
                <button type="submit" class="btn-masuk">
                    Masuk
                </button>
            </form>

            <!-- Footer Sesuai Permintaan User -->
            <div class="auth-footer">
                @2026 EMS PT MAKMUR
            </div>

        </div>
    </div>

    <!-- Library JavaScript Pre-Existing (Local Vendor Assets) -->
    <script src="<?= BASE_URL ?>assets/vendor/jquery/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>

