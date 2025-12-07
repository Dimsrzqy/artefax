<?php
// File: export_penugasan_excel.php (Diubah menjadi CSV/Excel-friendly dengan FILTER)
require_once __DIR__ . "/../../config/koneksi.php";

// Pastikan tidak ada output sebelum header
ob_clean();

$db = new Database();
$conn = $db->getConnection();

/* ============== TERIMA PARAMETER FILTER DARI URL ============== */
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Bangun klausa WHERE berdasarkan filter
$where_clauses = [];
$params_filter = [];
$types_filter = '';

if (!empty($start_date)) {
    $where_clauses[] = "e.EventTanggal >= ?";
    $params_filter[] = $start_date;
    $types_filter .= 's';
}

if (!empty($end_date)) {
    $where_clauses[] = "e.EventTanggal <= ?";
    $params_filter[] = $end_date;
    $types_filter .= 's';
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

/* ============== QUERY DENGAN FILTER ============== */
// Query event + karyawan dengan filter tanggal
$sql = "
    SELECT e.*,
        GROUP_CONCAT(u.UserNama ORDER BY u.UserNama ASC SEPARATOR ', ') AS Karyawan
    FROM event e
    LEFT JOIN event_karyawan ek ON e.IDEvent = ek.IDEvent
    LEFT JOIN users u ON ek.IDKaryawan = u.IDUser
    " . $where_sql . "
    GROUP BY e.IDEvent
    ORDER BY e.EventTanggal DESC, e.EventMulai DESC
";

// Eksekusi query dengan prepared statement
$stmt = $conn->prepare($sql);

if ($types_filter) {
    $stmt->bind_param($types_filter, ...$params_filter);
}

$stmt->execute();
$res = $stmt->get_result();

/* ========================================================== */
/* PENGATURAN HEADER DAN OUTPUT CSV                          */
/* ========================================================== */

// Buat nama file yang informatif
$filename = "Laporan_Penugasan";
if (!empty($start_date) || !empty($end_date)) {
    $filename .= "_Filtered";
    if (!empty($start_date)) {
        $filename .= "_From_" . date('Ymd', strtotime($start_date));
    }
    if (!empty($end_date)) {
        $filename .= "_To_" . date('Ymd', strtotime($end_date));
    }
}
$filename .= "_" . date("Ymd_His") . ".csv";

// Header untuk file CSV
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

// Tampilkan info filter jika ada
if (!empty($start_date) || !empty($end_date)) {
    $filter_info = "Filter: ";
    if (!empty($start_date)) {
        $filter_info .= "Dari " . date('d/m/Y', strtotime($start_date)) . " ";
    }
    if (!empty($end_date)) {
        $filter_info .= "Sampai " . date('d/m/Y', strtotime($end_date));
    }
    fputcsv($output, [$filter_info], ';');
}

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
        // Ambil hanya HH:MM dari waktu
        $waktu = substr($row['EventMulai'], 0, 5) . " - " . substr($row['EventSelesai'], 0, 5);
        
        $data_row = [
            $no++,
            $row['EventNama'],
            $row['EventLokasi'],
            $tanggal,
            $waktu,
            $row['Karyawan'] ?? '-', // Handle jika tidak ada karyawan
            $row['EventStatus']
        ];

        // Tulis baris data
        fputcsv($output, $data_row, ';');
    }
} else {
    // Jika tidak ada data
    $message = "Tidak ada data penugasan yang ditemukan";
    if (!empty($start_date) || !empty($end_date)) {
        $message .= " pada rentang tanggal yang dipilih";
    }
    $message .= ".";
    fputcsv($output, [$message], ';');
}

// Footer - Total data
fputcsv($output, [''], ';'); // Baris kosong
fputcsv($output, ["Total Event: " . ($no - 1)], ';');

// Menutup stream output
fclose($output);
$stmt->close();
exit;
?>