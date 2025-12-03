<?php
// get_karyawan_tersedia.php → FINAL & PASTI JALAN
header('Content-Type: application/json');
require_once __DIR__ . "/../../config/koneksi.php";

$db = new Database();
$conn = $db->getConnection();

$tanggal   = $_GET['tanggal'] ?? '';
$jam_mulai = $_GET['jam_mulai'] ?? '';
$durasi    = max(1, (int)($_GET['durasi'] ?? 8));

if (!$tanggal || !$jam_mulai) {
    echo json_encode([]);
    exit;
}

$start = new DateTime("$tanggal $jam_mulai");
$end   = clone $start;
$end->modify("+{$durasi} hours");

$jam_mulai_new = $start->format('H:i:s');
$jam_selesai_new = $end->format('H:i:s');

$sql = "
    SELECT DISTINCT ek.IDKaryawan AS IDUser
    FROM event_karyawan ek
    JOIN event e ON ek.IDEvent = e.IDEvent
    WHERE e.EventTanggal = ?
      AND e.EventMulai < ?
      AND ADDTIME(e.EventMulai, SEC_TO_TIME(e.EventDurasi * 3600)) > ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $tanggal, $jam_selesai_new, $jam_mulai_new);
$stmt->execute();
$result = $stmt->get_result();

$busy = [];
while ($row = $result->fetch_assoc()) {
    $busy[] = (int)$row['IDUser'];
}

echo json_encode($busy);
$stmt->close();
$conn->close();
?>