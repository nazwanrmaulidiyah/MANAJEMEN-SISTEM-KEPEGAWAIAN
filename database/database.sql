-- ========================================================
-- Database Schema: ems_db
-- Skema Sertifikasi: Junior Web Developer (SKK-32-10/2024)
-- Aplikasi: SIMAWA PT MAKMUR
-- ========================================================

CREATE DATABASE IF NOT EXISTS `ems_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ems_db`;

-- 1. Tabel: users (Admin Login)
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `role` VARCHAR(50) NOT NULL DEFAULT 'Administrator',
  `phone` VARCHAR(30) NULL DEFAULT '081234567890',
  `instansi` VARCHAR(100) NOT NULL DEFAULT 'PT MAKMUR',
  `unit` VARCHAR(100) NOT NULL DEFAULT 'Kantor Pusat',
  `password` VARCHAR(255) NOT NULL,
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel: departments (Master Departemen / Divisi)
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL UNIQUE,
  `kode` VARCHAR(20) NULL,
  `keterangan` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel: employees (Data Kepegawaian)
CREATE TABLE IF NOT EXISTS `employees` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nip` VARCHAR(20) NOT NULL UNIQUE,
  `nama` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `jabatan` VARCHAR(100) NOT NULL,
  `departemen` VARCHAR(100) NOT NULL,
  `tanggal_masuk` DATE NOT NULL,
  `alamat` TEXT NOT NULL,
  `foto` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_employees_departemen` FOREIGN KEY (`departemen`) REFERENCES `departments` (`nama`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Data Awal (Seed Data)
-- --------------------------------------------------------

-- Default Admin: admin@ems.test / password123
INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `updated_at`)
VALUES (1, 'Administrator SIMAWA', 'admin@ems.test', '$2y$12$0tO/6F/aPzXwDk6qWc8eRe.d1zVdJc09m2m6T6VzIuOa0l9Q9dKCW', NOW(), NOW())
ON DUPLICATE KEY UPDATE `email` = `email`;

-- Data Awal Departemen
INSERT INTO `departments` (`id`, `nama`, `kode`, `keterangan`) VALUES
(1, 'Teknologi Informasi', 'TI', 'Pengembangan sistem informasi dan infrastruktur IT'),
(2, 'Human Resources', 'HR', 'Pengelolaan sumber daya manusia dan personalia'),
(3, 'Keuangan', 'KEU', 'Pengelolaan anggaran, pembukuan, dan keuangan perusahaan'),
(4, 'Pemasaran', 'MKT', 'Strategi pemasaran, kemitraan, dan promosi'),
(5, 'Desain & Kreatif', 'DSN', 'Desain grafis, multimedia, dan materi kreatif'),
(6, 'Operasional', 'OPS', 'Operasional harian dan logistik perusahaan')
ON DUPLICATE KEY UPDATE `nama` = `nama`;

-- Data Awal Pegawai
INSERT INTO `employees` (`nip`, `nama`, `email`, `no_hp`, `jabatan`, `departemen`, `tanggal_masuk`, `alamat`, `foto`, `created_at`, `updated_at`)
VALUES
('EMP-2024-001', 'Budi Santoso', 'budi.santoso@makmur.co.id', '081234567890', 'Lead Software Engineer', 'Teknologi Informasi', '2022-01-15', 'Jl. Sudirman No. 45, Jakarta Selatan', NULL, NOW(), NOW()),
('EMP-2024-002', 'Siti Nurhaliza', 'siti.nurhaliza@makmur.co.id', '081298765432', 'HR Manager', 'Human Resources', '2021-06-10', 'Jl. Kebon Jeruk No. 12, Jakarta Barat', NULL, NOW(), NOW()),
('EMP-2024-003', 'Ahmad Fauzi', 'ahmad.fauzi@makmur.co.id', '081377889900', 'Finance Analyst', 'Keuangan', '2023-03-01', 'Jl. Gatot Subroto No. 88, Jakarta Pusat', NULL, NOW(), NOW()),
('EMP-2024-004', 'Dewi Lestari', 'dewi.lestari@makmur.co.id', '085611223344', 'Marketing Specialist', 'Pemasaran', '2023-08-20', 'Jl. Diponegoro No. 17, Bandung', NULL, NOW(), NOW()),
('EMP-2024-005', 'Rian Hidayat', 'rian.hidayat@makmur.co.id', '087844556677', 'DevOps Engineer', 'Teknologi Informasi', '2024-01-08', 'Jl. Dago Asri No. 3, Bandung', NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE `nip` = `nip`;

