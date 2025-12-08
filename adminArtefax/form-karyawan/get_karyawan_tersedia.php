<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . "/../../config/koneksi.php";

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

error_log("========================================");
error_log("GET KARYAWAN TERSEDIA (SIMPLE MODE)");
error_log("Filter: TIDAK sedang di event status Menunggu/Berjalan");
error_log("========================================");

// STRATEGI SEDERHANA: 
// Ambil semua karyawan KECUALI yang sedang bertugas di event berstatus Menunggu/Berjalan
$sql = "
    SELECT u.IDUser, u.UserNama
    FROM users u
    WHERE u.UserRole = 'Karyawan'
    AND u.IDUser NOT IN (
        SELECT DISTINCT ek.IDKaryawan
        FROM event_karyawan ek
        INNER JOIN event e ON ek.IDEvent = e.IDEvent
        WHERE e.EventStatus IN ('Menunggu', 'Berjalan')
    )
    ORDER BY u.UserNama
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("QUERY PREPARE FAILED: " . $conn->error);
    echo json_encode(['error' => 'Query prepare failed']);
    exit;
}

if (!$stmt->execute()) {
    error_log("QUERY EXECUTE FAILED: " . $stmt->error);
    echo json_encode(['error' => 'Query execute failed']);
    exit;
}

$result = $stmt->get_result();
$available = [];

error_log("KARYAWAN TERSEDIA:");
while ($row = $result->fetch_assoc()) {
    $idKaryawan = trim($row['IDUser']);
    $available[] = [
        'id' => $idKaryawan,
        'nama' => $row['UserNama']
    ];
    error_log("  ✓ ID: $idKaryawan - {$row['UserNama']}");
}

// Query untuk logging: Siapa yang sibuk
$sqlBusy = "
    SELECT DISTINCT ek.IDKaryawan, u.UserNama, e.EventNama, e.EventStatus, e.EventTanggal
    FROM event_karyawan ek
    INNER JOIN event e ON ek.IDEvent = e.IDEvent
    INNER JOIN users u ON ek.IDKaryawan = u.IDUser
    WHERE e.EventStatus IN ('Menunggu', 'Berjalan')
    ORDER BY u.UserNama
";

$stmtBusy = $conn->query($sqlBusy);
if ($stmtBusy) {
    error_log("KARYAWAN SIBUK (Event Menunggu/Berjalan):");
    while ($row = $stmtBusy->fetch_assoc()) {
        error_log("  ✗ ID: {$row['IDKaryawan']} - {$row['UserNama']}");
        error_log("    Event: {$row['EventNama']} [{$row['EventStatus']}] - {$row['EventTanggal']}");
    }
}

$stmt->close();
$conn->close();

error_log("TOTAL TERSEDIA: " . count($available));
error_log("========================================");

// Return array of available employees (yang TIDAK sedang bertugas)
echo json_encode($available);
?>