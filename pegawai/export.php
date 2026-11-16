<?php
/**
 * SIMAWA — Export Data Pegawai ke Format Excel (.xls Format Tabel Rapi)
 * PT MAKMUR
 */
require_once __DIR__ . '/../config/database.php';
requireAuth();

// 1. Tangkap parameter filter (jika ada pencarian atau filter departemen aktif)
$search = trim($_GET['search'] ?? '');
$departemen = trim($_GET['departemen'] ?? '');

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(nip LIKE ? OR nama LIKE ? OR email LIKE ? OR jabatan LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($departemen)) {
    $whereClauses[] = "departemen = ?";
    $params[] = $departemen;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// 2. Ambil seluruh data pegawai yang cocok (tanpa LIMIT agar data terunduh lengkap)
$stmt = $pdo->prepare("SELECT * FROM employees {$whereSql} ORDER BY id ASC");
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Konfigurasi Header Download File Excel (.xls)
$filename = "Data_Pegawai_PT_MAKMUR_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header("Cache-Control: max-age=0");
header("Pragma: public");

// UTF-8 Byte Order Mark (BOM) untuk memastikan karakter terbaca sempurna di semua versi Excel
echo "\xEF\xBB\xBF";
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<!--[if gte mso 9]>
<xml>
 <x:ExcelWorkbook>
  <x:ExcelWorksheets>
   <x:ExcelWorksheet>
    <x:Name>Data Pegawai</x:Name>
    <x:WorksheetOptions>
     <x:DisplayGridlines/>
    </x:WorksheetOptions>
   </x:ExcelWorksheet>
  </x:ExcelWorksheets>
 </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
  body {
    font-family: Calibri, 'Segoe UI', Arial, sans-serif;
    font-size: 11pt;
    color: #1e293b;
  }
  table {
    border-collapse: collapse;
    width: 100%;
  }
  .title-main {
    font-size: 16pt;
    font-weight: bold;
    text-align: center;
    color: #0f172a;
    height: 40px;
    vertical-align: middle;
  }
  .title-sub {
    font-size: 10.5pt;
    color: #475569;
    text-align: center;
    height: 25px;
    vertical-align: middle;
  }
  th {
    background-color: #ea580c;
    color: #ffffff;
    font-weight: bold;
    text-align: center;
    border: 1px solid #c2410c;
    padding: 10px 8px;
    font-size: 11pt;
    vertical-align: middle;
  }
  td {
    border: 1px solid #cbd5e1;
    padding: 8px 10px;
    font-size: 10.5pt;
    vertical-align: middle;
  }
  .col-no {
    text-align: center;
  }
  .col-nip {
    mso-number-format: "\@";
    text-align: center;
  }
  .col-phone {
    mso-number-format: "\@";
    text-align: left;
  }
  .col-date {
    mso-number-format: "\@";
    text-align: center;
  }
  .row-even {
    background-color: #f8fafc;
  }
</style>
</head>
<body>
  <table border="1" style="border-collapse: collapse; border: 1px solid #cbd5e1;">
    <tr>
      <td colspan="9" class="title-main" style="border: none; text-align: center; height: 35px; font-size: 16pt; font-weight: bold; color: #0f172a;">
        DATA PEGAWAI PT MAKMUR
      </td>
    </tr>
    <tr>
      <td colspan="9" class="title-sub" style="border: none; text-align: center; height: 25px; font-size: 10.5pt; color: #475569;">
        Sistem Informasi Manajemen Kepegawaian (SIMAWA) &bull; Tanggal Ekspor: <?= date('d F Y, H:i') ?> WIB
        <?php if (!empty($departemen)): ?>
          &bull; Filter Departemen: <?= htmlspecialchars($departemen) ?>
        <?php endif; ?>
        <?php if (!empty($search)): ?>
          &bull; Pencarian: &quot;<?= htmlspecialchars($search) ?>&quot;
        <?php endif; ?>
      </td>
    </tr>
    <tr>
      <td colspan="9" style="border: none; height: 12px;"></td>
    </tr>
    <thead>
      <tr>
        <th style="background-color: #ea580c; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #c2410c; width: 50px;">NO</th>
        <th style="background-color: #ea580c; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #c2410c; width: 190px;">NIP</th>
        <th style="background-color: #ea580c; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #c2410c; width: 230px;">NAMA PEGAWAI</th>
        <th style="background-color: #ea580c; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #c2410c; width: 240px;">EMAIL</th>
        <th style="background-color: #ea580c; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #c2410c; width: 160px;">NOMOR HANDPHONE</th>
        <th style="background-color: #ea580c; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #c2410c; width: 190px;">JABATAN</th>
        <th style="background-color: #ea580c; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #c2410c; width: 180px;">DEPARTEMEN</th>
        <th style="background-color: #ea580c; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #c2410c; width: 140px;">TANGGAL MASUK</th>
        <th style="background-color: #ea580c; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #c2410c; width: 320px;">ALAMAT LENGKAP</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($employees)): ?>
        <?php $no = 1; foreach ($employees as $idx => $emp): ?>
          <tr class="<?= ($idx % 2 === 1) ? 'row-even' : '' ?>" style="<?= ($idx % 2 === 1) ? 'background-color: #f8fafc;' : '' ?>">
            <td class="col-no" style="text-align: center; border: 1px solid #cbd5e1;"><?= $no++ ?></td>
            <td class="col-nip" style="mso-number-format: '\@'; text-align: center; border: 1px solid #cbd5e1;"><?= htmlspecialchars($emp['nip']) ?></td>
            <td style="border: 1px solid #cbd5e1; font-weight: 500;"><?= htmlspecialchars($emp['nama']) ?></td>
            <td style="border: 1px solid #cbd5e1;"><?= htmlspecialchars($emp['email']) ?></td>
            <td class="col-phone" style="mso-number-format: '\@'; text-align: left; border: 1px solid #cbd5e1;"><?= htmlspecialchars($emp['no_hp']) ?></td>
            <td style="border: 1px solid #cbd5e1;"><?= htmlspecialchars($emp['jabatan']) ?></td>
            <td style="border: 1px solid #cbd5e1;"><?= htmlspecialchars($emp['departemen']) ?></td>
            <td class="col-date" style="mso-number-format: '\@'; text-align: center; border: 1px solid #cbd5e1;"><?= htmlspecialchars(formatDateIndo($emp['tanggal_masuk'])) ?></td>
            <td style="border: 1px solid #cbd5e1;"><?= htmlspecialchars($emp['alamat']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="9" style="text-align: center; padding: 20px; color: #64748b; border: 1px solid #cbd5e1;">
            Tidak ada data pegawai yang ditemukan.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
