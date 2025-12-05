<?php
// File: export_penugasan_csv.php (Diubah menjadi CSV/Excel-friendly)
require_once __DIR__ . "/../../config/koneksi.php";

// Pastikan tidak ada output sebelum header
ob_clean();

$db = new Database();
$conn = $db->getConnection();

// Query event + karyawan
$sql = "
    SELECT e.*,
        GROUP_CONCAT(u.UserNama SEPARATOR ', ') AS Karyawan
    FROM event e
    LEFT JOIN event_karyawan ek ON e.IDEvent = ek.IDEvent
    LEFT JOIN users u ON ek.IDKaryawan = u.IDUser
    GROUP BY e.IDEvent
    ORDER BY e.EventTanggal DESC, e.EventMulai DESC
";

$res = $conn->query($sql);

/* ========================================================== */
/* PENGATURAN HEADER DAN OUTPUT CSV                 */
/* ========================================================== */

// Header untuk file CSV
$filename = "Laporan_Penugasan_" . date("Ymd_His") . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen('php://output', 'w');

// Menulis BOM (Byte Order Mark) untuk memastikan Excel membaca UTF-8 dengan benar
fwrite($output, "\xEF\xBB\xBF");

// --- BARIS HEADER LAPORAN ---
fputcsv($output, ["LAPORAN PENUGASAN EVENT"], ';');
fputcsv($output, ["Dicetak pada: " . date('d F Y - H:i')], ';');
fputcsv($output, [''], ';'); // Baris kosong

// --- HEADER TABEL (Kolom) ---
$header = [
    'No',
    'Nama Event',
    'Lokasi',
    'Tanggal',
    'Waktu',
    'Karyawan Ditugaskan',
    'Status'
];
// Menggunakan ';' sebagai delimiter (Pemisah)
fputcsv($output, $header, ';'); 

// --- DATA PENUGASAN ---
$no = 1;

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $tanggal = (new DateTime($row['EventTanggal']))->format("d/m/Y");
        $waktu   = $row['EventMulai'] . " - " . $row['EventSelesai'];
        
        $data_row = [
            $no++,
            $row['EventNama'],
            $row['EventLokasi'],
            $tanggal,
            $waktu,
            $row['Karyawan'],
            $row['EventStatus']
        ];

        // Tulis baris data
        fputcsv($output, $data_row, ';');
    }
} else {
    // Jika tidak ada data
    fputcsv($output, ['Tidak ada data penugasan yang ditemukan.'], ';');
}

// Menutup stream output
fclose($output);
exit;