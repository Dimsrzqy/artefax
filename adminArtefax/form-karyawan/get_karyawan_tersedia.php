<?php
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

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) ||
    !preg_match('/^\d{2}:\d{2}$/', $jam_mulai)) {
    echo json_encode([]);
    exit;
}

// Hitung interval event baru
$start = new DateTime("$tanggal $jam_mulai:00");
$end   = clone $start;
$end->modify("+{$durasi} hours");

$newStart = $start->format("Y-m-d H:i:s");
$newEnd   = $end->format("Y-m-d H:i:s");

// Query bentrok waktu
$sql = "
    SELECT DISTINCT ek.IDKaryawan AS IDUser
    FROM event_karyawan ek
    JOIN event e ON ek.IDEvent = e.IDEvent
    WHERE e.EventStatus != 'Selesai'
      AND CONCAT(e.EventTanggal, ' ', e.EventMulai) < ?
      AND ADDTIME(CONCAT(e.EventTanggal, ' ', e.EventMulai), SEC_TO_TIME(e.EventDurasi*3600)) > ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $newEnd, $newStart);
$stmt->execute();
$res = $stmt->get_result();

$busy = [];
while ($r = $res->fetch_assoc()) {
    $busy[] = (int)$r["IDUser"];
}

echo json_encode($busy);
?>
