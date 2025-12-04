<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../../config/koneksi.php";

$db = new Database();
$conn = $db->getConnection();

// Ambil parameter waktu event baru
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

// Hitung interval waktu event baru
$start = new DateTime("$tanggal $jam_mulai:00");
$end   = clone $start;
$end->modify("+{$durasi} hours");

$newStart = $start->format("Y-m-d H:i:s");
$newEnd   = $end->format("Y-m-d H:i:s");

// STEP 1: Ambil karyawan yang bentrok (tidak tersedia)
$sqlBusy = "
    SELECT DISTINCT ek.IDKaryawan AS IDUser
    FROM event_karyawan ek
    JOIN event e ON ek.IDEvent = e.IDEvent
    WHERE e.EventStatus != 'Selesai'
      AND CONCAT(e.EventTanggal, ' ', e.EventMulai) < ?
      AND ADDTIME(CONCAT(e.EventTanggal, ' ', e.EventMulai), SEC_TO_TIME(e.EventDurasi*3600)) > ?
";

$stmt = $conn->prepare($sqlBusy);
$stmt->bind_param("ss", $newEnd, $newStart);
$stmt->execute();
$res = $stmt->get_result();

$busy = [];
while ($r = $res->fetch_assoc()) {
    $busy[] = (int)$r["IDUser"];
}

// Jika tidak ada busy, masukkan nilai dummy agar NOT IN () tidak error
$busyList = $busy ? implode(",", $busy) : "0";

// STEP 2: Ambil karyawan yang tersedia (tidak termasuk busy)
$sqlKaryawan = "
    SELECT id, nama
    FROM karyawan
    WHERE id NOT IN ($busyList)
    ORDER BY nama ASC
";

$result = $conn->query($sqlKaryawan);

$available = [];
while ($row = $result->fetch_assoc()) {
    $available[] = $row;
}

echo json_encode($available);
?>
