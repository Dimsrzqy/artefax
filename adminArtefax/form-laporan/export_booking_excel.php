<?php
// File: /adminArtefax/form-laporan/export_booking_excel.php
session_start();

// --- VERIFIKASI LOGIN ---
if (!isset($_SESSION['IDUser']) || empty($_SESSION['IDUser'])) {
    // Path relatif dari /adminArtefax/form-laporan/export_booking_excel.php ke /adminArtefax/view/login.php
    header("Location: ../../view/login.php"); 
    exit;
}

// Memuat koneksi database dan Class Booking
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/booking.php"; // Menggunakan Class Booking

$db = new Database();
$conn = $db->getConnection();
// Class Booking akan otomatis menjalankan update status Selesai di constructor
$bookingCls = new Booking($conn);

// 🛑 Status yang akan ditampilkan (Sama seperti di LaporanBooking.php)
$statusFilterArr = ['Diterima', 'Selesai', 'Gagal', 'Batal'];
// Menggunakan real_escape_string dan implode untuk mengamankan status list
$statusFilterSql = "'" . implode("','", array_map([$conn, 'real_escape_string'], $statusFilterArr)) . "'";

/* ============== FILTER TANGGAL DARI URL QUERY ============== */
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$queryStartDate = null;
$queryEndDate = null;
$displayRange = 'Semua Periode';

try {
    if (!empty($startDate)) {
        $queryStartDate = (new DateTime($startDate))->format('Y-m-d');
    }
    if (!empty($endDate)) {
        $queryEndDate = (new DateTime($endDate))->format('Y-m-d');
    }

    if ($queryStartDate && $queryEndDate) {
        $displayRange = "Periode " . date('d/m/Y', strtotime($queryStartDate)) . " s/d " . date('d/m/Y', strtotime($queryEndDate));
    } elseif ($queryStartDate) {
        $displayRange = "Dari " . date('d/m/Y', strtotime($queryStartDate));
    } elseif ($queryEndDate) {
        $displayRange = "Sampai " . date('d/m/Y', strtotime($queryEndDate));
    }
} catch (Exception $e) {
    // Abaikan format tanggal yang salah, gunakan nilai null
}

/* ============== QUERY DATA LENGKAP ============== */
// Query SQL untuk mengambil semua data tanpa batasan LIMIT/OFFSET (untuk export)
$sql = "SELECT
            b.IDBooking, b.BkgTglMulai, b.BkgTglSelesai, b.BkgTotalHarga, b.BkgStatus,
            u.UserNama,
            bd.BkgDetailJenis, pj.PaketNama, a.AlatNama
        FROM booking b
        LEFT JOIN users u ON b.IDUser = u.IDUser
        LEFT JOIN booking_detail bd ON b.IDBooking = bd.IDBooking
        LEFT JOIN paketjasa pj ON bd.IDPaket = pj.IDPaket
        LEFT JOIN alat a ON bd.IDAlat = a.IDAlat
        WHERE b.BkgStatus IN ($statusFilterSql)";

$params = [];
$types = '';

if ($queryStartDate) {
    $sql .= " AND b.BkgTglSelesai >= ?";
    $params[] = $queryStartDate;
    $types .= 's';
}
if ($queryEndDate) {
    $sql .= " AND b.BkgTglSelesai <= ?";
    $params[] = $queryEndDate;
    $types .= 's';
}

$sql .= " ORDER BY b.BkgTglSelesai ASC";

$stmt = $conn->prepare($sql);
$dataBooking = [];

if ($stmt) {
    if (!empty($params)) {
        // Menggunakan bind_param jika ada parameter
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $dataBooking[] = $row;
        }
    }
    $stmt->close();
}

// Fungsi untuk format tanggal (dibuat lokal untuk file export)
function format_tanggal_export($dateString)
{
    if (empty($dateString) || $dateString === '0000-00-00' || strpos($dateString, '0000') !== false) {
        return '';
    }
    return date('d/m/Y', strtotime($dateString));
}

/* ============== PENGIRIMAN FILE EXCEL (CSV) ============== */
// Atur header untuk download file Excel
$filename = "Laporan_Booking_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen('php://output', 'w');

// Menulis BOM (Byte Order Mark) untuk memastikan Excel membaca UTF-8 dengan benar
fwrite($output, "\xEF\xBB\xBF");

// Header Laporan (Baris 1)
fputcsv($output, ["LAPORAN DATA BOOKING (Diterima/Selesai/Gagal/Batal)"], ';');
// Sub-Header Laporan (Baris 2)
fputcsv($output, ["Tanggal Selesai: $displayRange"], ';');
fputcsv($output, [''], ';'); // Baris kosong

// Header Tabel (Total Harga dihapus)
$header = [
    'No', 
    'ID Booking', 
    'Nama Pelanggan', 
    'Jenis Booking', 
    'Detail Layanan', 
    'Tgl Mulai', 
    'Tgl Selesai', 
    'Status'
];
fputcsv($output, $header, ';');

// Data
$no = 1;

foreach ($dataBooking as $d) {
    // Tentukan nama paket/alat
    $detailLayanan = '';
    if (!empty($d['PaketNama'])) {
        $detailLayanan = $d['PaketNama'];
    } elseif (!empty($d['AlatNama'])) {
        $detailLayanan = $d['AlatNama'];
    } else {
        $detailLayanan = '—';
    }

    // Format data baris (BkgTotalHarga dihilangkan dari array)
    $row = [
        $no++,
        $d['IDBooking'],
        $d['UserNama'] ?? '—',
        $d['BkgDetailJenis'] ?? '—',
        $detailLayanan,
        format_tanggal_export($d['BkgTglMulai']),
        format_tanggal_export($d['BkgTglSelesai']),
        $d['BkgStatus']
    ];
    
    fputcsv($output, $row, ';');
}

// Baris Summary (Dibersihkan dan dikembalikan ke versi yang Anda berikan, tanpa Total Harga)
fputcsv($output, [''], ';'); // Baris kosong
fputcsv($output, ['Diekspor oleh: ' . ($_SESSION['UserNama'] ?? 'Admin Artefax')], ';');
fputcsv($output, ['Waktu Ekspor: ' . date('d/m/Y H:i:s')], ';');


// Tutup file pointer
fclose($output);

exit;
?>