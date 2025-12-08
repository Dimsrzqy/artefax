<?php
// File: export_absensi_excel.php (Diubah menjadi CSV/Excel-friendly dengan FILTER)
session_start();

// --- VERIFIKASI LOGIN (Opsional, asumsikan sudah ada di file LaporanAbsensiKaryawan.php)
if (!isset($_SESSION['IDUser']) || empty($_SESSION['IDUser'])) {
    // Sesuaikan path jika diperlukan
    header("Location: ../../view/login.php"); 
    exit;
}
// --- END VERIFIKASI LOGIN

require_once __DIR__ . "/../../config/koneksi.php";

$db = new Database();
$conn = $db->getConnection();

// --- AMBIL PARAMETER FILTER DARI URL ---
$start_date = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] . ' 00:00:00' : null;
$end_date = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] . ' 23:59:59' : null;

// --- QUERY SQL DENGAN FILTER ---
$base_sql = "SELECT p.IDPresensi, p.PsnWaktu, p.PsnLokasi, p.PsnFoto, p.PsnStatus, u.UserNama
             FROM presensi p
             LEFT JOIN users u ON p.IDUser = u.IDUser";

$where_clauses = [];
$params = [];
$types = '';

if ($start_date) {
    $where_clauses[] = "p.PsnWaktu >= ?";
    $params[] = $start_date;
    $types .= 's';
}
if ($end_date) {
    $where_clauses[] = "p.PsnWaktu <= ?";
    $params[] = $end_date;
    $types .= 's';
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";
$sql = $base_sql . $where_sql . " ORDER BY p.PsnWaktu DESC";

// --- EKSEKUSI QUERY ---
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();


/* ========================================================== */
/* PENGATURAN HEADER DAN OUTPUT CSV                          */
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

// Tampilkan informasi filter jika ada
if ($start_date || $end_date) {
    $filter_info = "Periode: ";
    if ($start_date && $end_date) {
        $filter_info .= date('d/m/Y', strtotime($start_date)) . " - " . date('d/m/Y', strtotime($end_date));
    } elseif ($start_date) {
        $filter_info .= "Dari " . date('d/m/Y', strtotime($start_date));
    } elseif ($end_date) {
        $filter_info .= "Sampai " . date('d/m/Y', strtotime($end_date));
    }
    fputcsv($output, [$filter_info], ';');
}

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

// --- FOOTER LAPORAN ---
fputcsv($output, [''], ';'); // Baris kosong
fputcsv($output, ["Total Data: " . ($no - 1) . " record"], ';');

// Menutup statement dan stream output
$stmt->close();
fclose($output);
exit;
?>