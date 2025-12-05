<?php
// File: export_absensi_excel.php (Diubah menjadi CSV/Excel-friendly)
session_start();

// --- VERIFIKASI LOGIN (Opsional, asumsikan sudah ada di file LaporanAbsensiKaryawan.php)
if (!isset($_SESSION['IDUser']) || empty($_SESSION['IDUser'])) {
    // Sesuaikan path jika diperlukan
    // header("Location: ../../view/login.php"); 
    // exit;
}
// --- END VERIFIKASI LOGIN

require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/absensi.php";

$db = new Database();
$conn = $db->getConnection();
$absensi = new Absensi($conn);

// 🛑 Asumsi: Jika fungsi tampilSemua() tidak menerima parameter filter, 
// data yang diekspor adalah semua data yang ada.
// Jika ingin memfilter, Anda harus menambahkan logika filter tanggal di file ini.
$result = $absensi->tampilSemua(); 


/* ========================================================== */
/* PENGATURAN HEADER DAN OUTPUT CSV                 */
/* ========================================================== */

// Header untuk file CSV (Lebih modern dan kompatibel)
$filename = "Laporan_Absensi_Karyawan_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen('php://output', 'w');

// Menulis BOM (Byte Order Mark) untuk memastikan Excel membaca UTF-8 dengan benar
// Ini penting untuk karakter khusus dan mencegah korupsi data.
fwrite($output, "\xEF\xBB\xBF");

// --- BARIS HEADER LAPORAN ---
fputcsv($output, ["LAPORAN ABSENSI KARYAWAN ARTEFAX"], ';');
fputcsv($output, ["Dicetak pada: " . date('d F Y - H:i')], ';');
fputcsv($output, [''], ';'); // Baris kosong

// --- HEADER TABEL (Kolom) ---
$header = [
    'No',
    'Nama Karyawan',
    'Tanggal',
    'Jam',
    'Lokasi',
    'Status'
];
// Menggunakan ';' sebagai delimiter (Pemisah) agar lebih kompatibel dengan Excel Indonesia/Eropa
fputcsv($output, $header, ';'); 


// --- DATA ABSENSI ---
$no = 1;
if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        $waktu = $r['PsnWaktu'] ? new DateTime($r['PsnWaktu']) : null;
        $tanggal = $waktu ? $waktu->format('d/m/Y') : '-';
        $jam     = $waktu ? $waktu->format('H:i')     : '-';
        $nama    = $r['UserNama'] ?? 'Tidak Diketahui';
        $lokasi  = $r['PsnLokasi'] ?? '-';
        $status  = ucfirst(strtolower($r['PsnStatus'] ?? 'Alpha'));
        
        $row = [
            $no,
            $nama,
            $tanggal,
            $jam,
            $lokasi,
            $status
        ];

        fputcsv($output, $row, ';');
        $no++;
    }
} else {
    // Jika tidak ada data
    fputcsv($output, ['Tidak ada data absensi yang ditemukan.'], ';');
}

// Menutup stream output
fclose($output);
exit;
?>