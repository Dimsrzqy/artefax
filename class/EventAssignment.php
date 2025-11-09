<?php
class EventAssignment {
    private $conn;
    private $table = "event"; // tabel utama

    public function __construct($db) {
        $this->conn = $db;
    }

    // ==========================================================
    // AMBIL PENUGASAN BERDASARKAN KARYAWAN
    // ==========================================================
public function getAssignmentsByKaryawan($idKaryawan) {
    $query = "SELECT 
                e.IDEvent,
                e.EventNama,
                e.EventLokasi,
                e.IDBooking,
                e.IDKaryawan,
                e.EventMulai,
                e.EventSelesai,
                e.EventStatus,
                e.CreatedAt,
                e.UpdatedAt,
                u.UserNama AS CustomerNama
              FROM {$this->table} e
              LEFT JOIN booking b ON e.IDBooking = b.IDBooking
              LEFT JOIN users u ON b.IDUser = u.IDUser
              WHERE e.IDKaryawan = ?
              ORDER BY e.CreatedAt DESC";

    $stmt = $this->conn->prepare($query);
    if (!$stmt) return [];
    $stmt->bind_param("i", $idKaryawan);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

    // ==========================================================
    // STATISTIK EVENT PER KARYAWAN
    // ==========================================================
    public function getStats($idKaryawan) {
        $query = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN EventStatus = 'Selesai' THEN 1 ELSE 0 END) AS selesai,
                    SUM(CASE WHEN EventStatus = 'Berjalan' THEN 1 ELSE 0 END) AS berjalan,
                    SUM(CASE WHEN EventStatus = 'Menunggu' THEN 1 ELSE 0 END) AS menunggu
                  FROM {$this->table}
                  WHERE IDKaryawan = ?";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return ['total' => 0, 'selesai' => 0, 'berjalan' => 0, 'menunggu' => 0];
        }

        $stmt->bind_param("i", $idKaryawan);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row ?: ['total' => 0, 'selesai' => 0, 'berjalan' => 0, 'menunggu' => 0];
    }
}
?>