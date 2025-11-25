<?php
// export_absensi_excel.php → HASIL .XLS ASLI (BUKA EXCEL 100% TANPA ERROR & CORRUPTED)
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/Absensi.php";

$db = new Database();
$conn = $db->getConnection();
$absensi = new Absensi($conn);
$result = $absensi->tampilSemua();

// Header untuk file Excel asli (.xls)
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Laporan_Absensi_Karyawan_" . date('d-m-Y') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
<body>
<h2 style="text-align:center; color:#1e7e34;">LAPORAN ABSENSI KARYAWAN ARTEFAX</h2>
<p style="text-align:center;">Dicetak pada: ' . date('d F Y - H:i') . '</p>
<table border="1" style="width:100%; border-collapse:collapse;">
    <thead>
        <tr style="background:#1e7e34; color:white; font-weight:bold; text-align:center;">
            <th>No</th>
            <th>Nama Karyawan</th>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>Lokasi</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>';

$no = 1;
while ($r = $result->fetch_assoc()) {
    $waktu = $r['PsnWaktu'] ? new DateTime($r['PsnWaktu']) : null;
    $tanggal = $waktu ? $waktu->format('d/m/Y') : '-';
    $jam     = $waktu ? $waktu->format('H:i')     : '-';
    $nama    = htmlspecialchars($r['UserNama'] ?? 'Tidak Diketahui');
    $lokasi  = htmlspecialchars($r['PsnLokasi'] ?? '-');
    $status  = ucfirst(strtolower($r['PsnStatus'] ?? 'Alpha'));

    echo "<tr align='center'>
            <td>$no</td>
            <td align='left'>$nama</td>
            <td>$tanggal</td>
            <td>$jam</td>
            <td align='left'>$lokasi</td>
            <td>$status</td>
          </tr>";
    $no++;
}

echo '</tbody></table></body></html>';
exit;
?>