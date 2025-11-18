<?php
require_once __DIR__ . "/../../config/koneksi.php";

$db = new Database();
$conn = $db->getConnection();

// HINDARI OUTPUT APAPUN SEBELUM HEADER !!!
ob_clean();

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

// Nama file
$filename = "Laporan_Penugasan_" . date("Y-m-d") . ".xls";

// Header Excel supaya browser tidak error
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// TABEL EXCEL
echo "<table border='1'>
        <thead>
            <tr style='font-weight:bold; background:#d9e1f2;'>
                <th>No</th>
                <th>Nama Event</th>
                <th>Lokasi</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Karyawan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
";

$no = 1;

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {

        $tanggal = (new DateTime($row['EventTanggal']))->format("d/m/Y");
        $waktu   = $row['EventMulai'] . " - " . $row['EventSelesai'];

        echo "
        <tr>
            <td>{$no}</td>
            <td>{$row['EventNama']}</td>
            <td>{$row['EventLokasi']}</td>
            <td>{$tanggal}</td>
            <td>{$waktu}</td>
            <td>{$row['Karyawan']}</td>
            <td>{$row['EventStatus']}</td>
        </tr>";
        $no++;
    }
} else {
    echo "<tr><td colspan='7'>Tidak ada data</td></tr>";
}

echo "</tbody></table>";
exit;
