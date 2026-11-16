/**
 * SIMAWA — Employee Management System PT MAKMUR
 * Script Utama Interaksi Antarmuka & Validasi
 */

$(document).ready(function () {

    // -------------------------------------------------------------
    // 0. Konfigurasi Global Header CSRF untuk Permintaan AJAX
    // -------------------------------------------------------------
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    if (csrfToken) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
    }

    // -------------------------------------------------------------
    // 1. Mobile Sidebar Toggle
    // -------------------------------------------------------------
    $('#sidebarToggle, #sidebarOverlay').on('click', function () {
        $('#sidebar').toggleClass('show');
        $('#sidebarOverlay').toggleClass('show');
    });

    // -------------------------------------------------------------
    // 2. Counter Animation untuk Statistik Dashboard
    // -------------------------------------------------------------
    $('.counter-value').each(function () {
        const $this = $(this);
        const countTo = parseInt($this.attr('data-count'), 10) || 0;

        $({ countNum: 0 }).animate(
            { countNum: countTo },
            {
                duration: 900,
                easing: 'swing',
                step: function () {
                    $this.text(Math.floor(this.countNum));
                },
                complete: function () {
                    $this.text(this.countNum);
                }
            }
        );
    });

    // -------------------------------------------------------------
    // 3. Live Image Preview saat Upload Foto Pegawai
    // -------------------------------------------------------------
    $('#foto').on('change', function () {
        const file = this.files[0];
        const $preview = $('#preview-foto');

        if (file) {
            // Validasi ukuran file (maksimal 2MB = 2097152 bytes)
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ukuran Terlalu Besar',
                    text: 'Ukuran foto maksimal adalah 2MB. Silakan pilih foto lain.',
                    background: '#111827',
                    color: '#f8fafc',
                    confirmButtonColor: '#f97316'
                });
                $(this).val('');
                return;
            }

            // Validasi tipe file
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Format Tidak Valid',
                    text: 'Format foto harus berupa JPG, JPEG, PNG, atau WEBP.',
                    background: '#111827',
                    color: '#f8fafc',
                    confirmButtonColor: '#f97316'
                });
                $(this).val('');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                if ($preview.length) {
                    $preview.attr('src', e.target.result);
                }
            };
            reader.readAsDataURL(file);
        }
    });

    // -------------------------------------------------------------
    // 4. Pembatasan Input NIP Real-Time (Maksimal 18 Digit Angka Murni)
    // -------------------------------------------------------------
    $(document).on('input', '#nip', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 18);
    });

    // -------------------------------------------------------------
    // 4.1 Validasi Form Tambah / Edit Pegawai (Client-Side)
    // -------------------------------------------------------------
    $('#form-employee').on('submit', function (e) {
        let isValid = true;
        $('.is-invalid').removeClass('is-invalid');
        $('.client-error').remove();

        function showError($input, message) {
            $input.addClass('is-invalid');
            $input.after(`<div class="invalid-feedback d-block client-error">${message}</div>`);
            isValid = false;
        }

        // Validasi NIP (wajib tepat 18 digit angka)
        const $nip = $('#nip');
        if ($nip.length) {
            const nipVal = $nip.val().trim();
            if (nipVal === '') {
                showError($nip, 'NIP wajib diisi.');
            } else if (!/^[0-9]{18}$/.test(nipVal)) {
                showError($nip, 'NIP harus terdiri dari tepat 18 digit angka.');
            }
        }

        // Validasi Nama
        const $nama = $('#nama');
        if ($nama.length) {
            const namaVal = $nama.val().trim();
            if (namaVal === '') {
                showError($nama, 'Nama lengkap wajib diisi.');
            } else if (namaVal.length < 3) {
                showError($nama, 'Nama minimal terdiri dari 3 karakter.');
            }
        }

        // Validasi Email
        const $email = $('#email');
        if ($email.length) {
            const emailVal = $email.val().trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailVal === '') {
                showError($email, 'Email wajib diisi.');
            } else if (!emailRegex.test(emailVal)) {
                showError($email, 'Format email tidak valid (contoh: nama@perusahaan.com).');
            }
        }

        // Validasi No HP
        const $noHp = $('#no_hp');
        if ($noHp.length) {
            const hpVal = $noHp.val().trim();
            const phoneRegex = /^[0-9+-\s]{9,20}$/;
            if (hpVal === '') {
                showError($noHp, 'Nomor HP wajib diisi.');
            } else if (!phoneRegex.test(hpVal)) {
                showError($noHp, 'Nomor HP harus berupa angka dan minimal 9 digit.');
            }
        }

        // Validasi Jabatan
        const $jabatan = $('#jabatan');
        if ($jabatan.length && $jabatan.val().trim() === '') {
            showError($jabatan, 'Jabatan wajib diisi.');
        }

        // Validasi Departemen
        const $departemen = $('#departemen');
        if ($departemen.length && $departemen.val().trim() === '') {
            showError($departemen, 'Departemen wajib dipilih.');
        }

        // Validasi Tanggal Masuk
        const $tanggalMasuk = $('#tanggal_masuk');
        if ($tanggalMasuk.length && $tanggalMasuk.val().trim() === '') {
            showError($tanggalMasuk, 'Tanggal masuk wajib diisi.');
        }

        // Validasi Alamat
        const $alamat = $('#alamat');
        if ($alamat.length && $alamat.val().trim() === '') {
            showError($alamat, 'Alamat lengkap wajib diisi.');
        }

        if (!isValid) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('.is-invalid:first').offset().top - 120
            }, 300);
        }
    });

    // -------------------------------------------------------------
    // 5. Validasi Form Login (Client-Side)
    // -------------------------------------------------------------
    $('#form-login').on('submit', function (e) {
        let isValid = true;
        $('.is-invalid').removeClass('is-invalid');
        $('.client-error').remove();

        const $email = $('#login-email');
        const $password = $('#login-password');
        const $captcha = $('#login-captcha');

        if ($email.val().trim() === '') {
            $email.addClass('is-invalid');
            $email.after('<div class="invalid-feedback d-block client-error">Email wajib diisi.</div>');
            isValid = false;
        }

        if ($password.val().trim() === '') {
            $password.addClass('is-invalid');
            $password.after('<div class="invalid-feedback d-block client-error">Password wajib diisi.</div>');
            isValid = false;
        }

        if ($captcha.length && $captcha.val().trim() === '') {
            $captcha.addClass('is-invalid');
            $captcha.after('<div class="invalid-feedback d-block client-error">Jawaban captcha wajib diisi.</div>');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });

    // -------------------------------------------------------------
    // 6. Konfirmasi dan Eksekusi Hapus Pegawai via AJAX (SweetAlert2)
    // -------------------------------------------------------------
    $(document).on('click', '.btn-delete-ajax', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const url = $btn.data('url');
        const id = $btn.data('id');
        const name = $btn.data('name') || 'pegawai ini';
        const rowId = $btn.data('row-id');

        Swal.fire({
            title: 'Hapus Pegawai?',
            text: `Apakah Anda yakin ingin menghapus data "${name}"? Tindakan ini tidak dapat dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#f97316',
            confirmButtonText: '<i class="bi bi-trash me-1"></i> Ya, Hapus!',
            cancelButtonText: 'Batal',
            background: (document.documentElement.getAttribute('data-theme') === 'light' || document.body.getAttribute('data-theme') === 'light') ? '#ffffff' : '#111827',
            color: (document.documentElement.getAttribute('data-theme') === 'light' || document.body.getAttribute('data-theme') === 'light') ? '#1e293b' : '#f8fafc',
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn-danger-custom',
                cancelButton: 'btn-orange-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { 
                        id: id,
                        csrf_token: csrfToken || $('meta[name="csrf-token"]').attr('content')
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            if (rowId && $(rowId).length) {
                                $(rowId).fadeOut(350, function () {
                                    $(this).remove();
                                    const $totalEl = $('#total-pegawai-display');
                                    if ($totalEl.length) {
                                        let current = parseInt($totalEl.text(), 10) || 0;
                                        if (current > 0) $totalEl.text(current - 1);
                                    }
                                });
                            }

                            const th = getSwalTheme();
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message || 'Data pegawai berhasil dihapus.',
                                timer: 2000,
                                showConfirmButton: false,
                                background: th.background,
                                color: th.color
                            });

                            if ($btn.hasClass('redirect-after-delete')) {
                                setTimeout(function () {
                                    window.location.href = 'index.php';
                                }, 1200);
                            }
                        } else {
                            $btn.prop('disabled', false).html('<i class="bi bi-trash text-danger"></i>');
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: response.message || 'Gagal menghapus data.',
                                background: '#111827',
                                color: '#f8fafc'
                            });
                        }
                    },
                    error: function () {
                        $btn.prop('disabled', false).html('<i class="bi bi-trash text-danger"></i>');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error Server',
                            text: 'Terjadi kesalahan pada server saat menghapus data.',
                            background: '#111827',
                            color: '#f8fafc'
                        });
                    }
                });
            }
        });
    });

    // Helper tema SweetAlert2 adaptif
    function getSwalTheme() {
        const isLight = document.documentElement.getAttribute('data-theme') === 'light' || document.body.getAttribute('data-theme') === 'light';
        return {
            background: isLight ? '#ffffff' : '#111827',
            color: isLight ? '#1e293b' : '#f8fafc'
        };
    }

    // -------------------------------------------------------------
    // 6a. Konfirmasi Hapus Form Umum (Departemen & Pengguna)
    // -------------------------------------------------------------
    $(document).on('submit', '.form-delete-confirm', function (e) {
        if ($(this).data('confirmed')) {
            return true;
        }
        e.preventDefault();
        const form = this;
        const name = $(form).data('name') || 'data ini';
        const title = $(form).data('title') || 'Hapus Data?';
        const th = getSwalTheme();

        Swal.fire({
            title: title,
            text: `Apakah Anda yakin ingin menghapus ${name}? Tindakan ini tidak dapat dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#f97316',
            confirmButtonText: '<i class="bi bi-trash me-1"></i> Ya, Hapus!',
            cancelButtonText: 'Batal',
            background: th.background,
            color: th.color,
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn-danger-custom',
                cancelButton: 'btn-orange-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $(form).data('confirmed', true);
                form.submit();
            }
        });
    });

    // Peringatan Hapus Akun Sendiri (Aktif)
    $(document).on('click', '.btn-self-delete-alert', function (e) {
        e.preventDefault();
        const th = getSwalTheme();
        Swal.fire({
            title: 'Tidak Dapat Dihapus',
            text: 'Akun ini sedang Anda gunakan saat ini sehingga tidak dapat dihapus.',
            icon: 'info',
            confirmButtonColor: '#f97316',
            confirmButtonText: 'Mengerti',
            background: th.background,
            color: th.color,
            customClass: {
                confirmButton: 'btn-orange px-4'
            }
        });
    });

    // -------------------------------------------------------------
    // 6b. Konfirmasi Update / Simpan Data Pegawai
    // -------------------------------------------------------------
    $('#form-employee-edit').on('submit', function (e) {
        if (!this.checkValidity()) {
            return;
        }
        if ($(this).data('confirmed')) {
            return;
        }
        e.preventDefault();
        const form = this;
        const th = getSwalTheme();

        Swal.fire({
            title: 'Simpan Perubahan Data?',
            text: 'Apakah Anda yakin ingin memperbarui data pegawai ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f97316',
            cancelButtonColor: '#f97316',
            confirmButtonText: '<i class="bi bi-check2-circle me-1"></i> Ya, Simpan!',
            cancelButtonText: 'Batal',
            background: th.background,
            color: th.color,
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn-orange px-4',
                cancelButton: 'btn-orange-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $(form).data('confirmed', true);
                form.submit();
            }
        });
    });

    // -------------------------------------------------------------
    // 6c. Konfirmasi Update Data Pengguna (Modal Edit User)
    // -------------------------------------------------------------
    $('#form-edit-user').on('submit', function (e) {
        if (!this.checkValidity()) {
            return;
        }
        if ($(this).data('confirmed')) {
            return;
        }
        e.preventDefault();
        const form = this;
        const th = getSwalTheme();

        Swal.fire({
            title: 'Simpan Perubahan User?',
            text: 'Apakah Anda yakin ingin memperbarui data akun pengguna ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f97316',
            cancelButtonColor: '#f97316',
            confirmButtonText: '<i class="bi bi-check2-circle me-1"></i> Ya, Simpan!',
            cancelButtonText: 'Batal',
            background: th.background,
            color: th.color,
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn-orange px-4',
                cancelButton: 'btn-orange-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $(form).data('confirmed', true);
                form.submit();
            }
        });
    });

    // -------------------------------------------------------------
    // 6d. Konfirmasi Reset Password Pengguna
    // -------------------------------------------------------------
    $('#form-reset-password').on('submit', function (e) {
        if (!this.checkValidity()) {
            return;
        }
        if ($(this).data('confirmed')) {
            return;
        }
        e.preventDefault();
        const form = this;
        const name = $('#reset_user_name').text() || 'pengguna ini';
        const th = getSwalTheme();

        Swal.fire({
            title: 'Reset Password Pengguna?',
            text: `Apakah Anda yakin ingin mengubah password akun "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0284c7',
            cancelButtonColor: '#f97316',
            confirmButtonText: '<i class="bi bi-key-fill me-1"></i> Ya, Reset Password!',
            cancelButtonText: 'Batal',
            background: th.background,
            color: th.color,
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn btn-primary px-4',
                cancelButton: 'btn-orange-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $(form).data('confirmed', true);
                form.submit();
            }
        });
    });

    // -------------------------------------------------------------
    // 6e. Konfirmasi Keluar / Logout
    // -------------------------------------------------------------
    $(document).on('click', '.btn-logout-confirm', function (e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const th = getSwalTheme();

        Swal.fire({
            title: 'Konfirmasi Keluar?',
            text: 'Apakah Anda yakin ingin keluar dari sistem SIMAWA?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#f97316',
            confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i> Ya, Keluar!',
            cancelButtonText: 'Batal',
            background: th.background,
            color: th.color,
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn-danger-custom',
                cancelButton: 'btn-orange-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });

    // -------------------------------------------------------------
    // 7. Auto-dismiss Alert Messages
    // -------------------------------------------------------------
    setTimeout(function () {
        $('.alert-dismissible').fadeOut(400);
    }, 4500);

    // -------------------------------------------------------------
    // 8. Toggle Visibility Password
    // -------------------------------------------------------------
    $(document).on('click', '#togglePasswordBtn', function () {
        const $pwd = $('#login-password');
        const $icon = $('#togglePasswordIcon');
        if ($pwd.attr('type') === 'password') {
            $pwd.attr('type', 'text');
            $icon.removeClass('bi-eye-slash').addClass('bi-eye');
            $(this).attr('title', 'Sembunyikan password');
        } else {
            $pwd.attr('type', 'password');
            $icon.removeClass('bi-eye').addClass('bi-eye-slash');
            $(this).attr('title', 'Tampilkan password');
        }
    });

    // -------------------------------------------------------------
    // 9. Theme Switcher (Dark Mode / Light Mode)
    // -------------------------------------------------------------
    function updateThemeIcon(theme) {
        const $icon = $('#themeToggleIcon');
        if (theme === 'light') {
            $icon.removeClass('bi-moon-stars-fill').addClass('bi-sun-fill text-warning');
            $('#themeToggleBtn').attr('title', 'Beralih ke Mode Gelap');
        } else {
            $icon.removeClass('bi-sun-fill text-warning').addClass('bi-moon-stars-fill');
            $('#themeToggleBtn').attr('title', 'Beralih ke Mode Terang');
        }
    }

    const currentTheme = localStorage.getItem('ems_theme') || 'dark';
    updateThemeIcon(currentTheme);

    $(document).on('click', '#themeToggleBtn', function () {
        const activeTheme = $('html').attr('data-theme') === 'light' ? 'dark' : 'light';
        $('html').attr('data-theme', activeTheme);
        localStorage.setItem('ems_theme', activeTheme);
        updateThemeIcon(activeTheme);
    });

    // -------------------------------------------------------------
    // 10. Inisialisasi Bootstrap Tooltip
    // -------------------------------------------------------------
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // -------------------------------------------------------------
    // 11. Bersihkan Nilai Form & Cegah Autofill Browser Liar pada Modal
    // -------------------------------------------------------------
    $('#modalTambahUser').on('show.bs.modal shown.bs.modal', function () {
        const $m = $(this);
        $m.find('input[type="email"], input[type="password"], input[name="phone"], input[name="name"]').val('');
    });

    $('#modalResetPassword').on('show.bs.modal shown.bs.modal', function () {
        $(this).find('input[type="password"]').val('');
    });

    $('#modalTambahDepartemen').on('show.bs.modal shown.bs.modal', function () {
        $(this).find('input[name="nama"], textarea[name="keterangan"]').val('');
    });
});

