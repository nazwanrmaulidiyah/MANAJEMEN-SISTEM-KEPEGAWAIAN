<?php
// includes/footer.php
?>
        </main>

        <footer class="py-4 px-3 text-center small" style="background: transparent; border-top: none; color: var(--text-hint);">
            2026 &copy; <span style="color: var(--text-muted); font-weight: 600;">PT MAKMUR</span>. Hak Cipta Dilindungi.
        </footer>
    </div>

    <!-- Library JavaScript Pre-Existing (Local Vendor Assets) -->
    <script src="<?= BASE_URL ?>assets/vendor/jquery/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
    <!-- Script Kustom Aplikasi -->
    <script src="<?= BASE_URL ?>assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>

    <!-- SweetAlert2 Flash Notifications (Selaras Gambar 2) -->
    <?php if (!empty($_SESSION['success'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const isLight = document.documentElement.getAttribute('data-theme') === 'light';
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: <?= json_encode($_SESSION['success']) ?>,
                    timer: 2500,
                    showConfirmButton: false,
                    background: isLight ? '#ffffff' : '#141724',
                    color: isLight ? '#0f172a' : '#f8fafc'
                });
            });
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const isLight = document.documentElement.getAttribute('data-theme') === 'light';
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    text: <?= json_encode($_SESSION['error']) ?>,
                    showConfirmButton: true,
                    confirmButtonColor: '#f97316',
                    background: isLight ? '#ffffff' : '#141724',
                    color: isLight ? '#0f172a' : '#f8fafc'
                });
            });
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['info'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const isLight = document.documentElement.getAttribute('data-theme') === 'light';
                Swal.fire({
                    icon: 'info',
                    title: 'Informasi',
                    text: <?= json_encode($_SESSION['info']) ?>,
                    timer: 2500,
                    showConfirmButton: false,
                    background: isLight ? '#ffffff' : '#141724',
                    color: isLight ? '#0f172a' : '#f8fafc'
                });
            });
        </script>
        <?php unset($_SESSION['info']); ?>
    <?php endif; ?>
</body>
</html>
