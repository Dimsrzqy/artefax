<?php
class EventAssignment {
    private $conn;
    private $table = "event";

    public function __construct($db) {
        $this->conn = $db;
    }

    // ==========================================================
    // 🔄 UPDATE STATUS OTOMATIS
    // ==========================================================
    public function updateStatusOtomatis() {
        $query = "
            UPDATE {$this->table}
            SET EventStatus = 'Selesai'
            WHERE TRIM(LOWER(EventStatus)) != 'selesai'
              AND NOW() > DATE_ADD(
                  TIMESTAMP(EventTanggal, EventMulai),
                  INTERVAL EventDurasi HOUR
              )
        ";
        if (!$this->conn->query($query)) {
            error_log('❌ Gagal update status otomatis: ' . $this->conn->error);
        }
    }

    // ==========================================================
    // 📋 AMBIL PENUGASAN KARYAWAN (HANYA YANG BELUM SELESAI)
    // ==========================================================
    public function getAssignmentsByKaryawan($idKaryawan) {
        $this->updateStatusOtomatis();

        $query = "SELECT 
                    e.IDEvent, e.EventNama, e.EventLokasi, e.IDBooking, e.IDKaryawan,
                    e.EventTanggal, e.EventDurasi, e.EventMulai, e.EventSelesai,
                    e.EventStatus, e.CreatedAt, e.UpdatedAt,
                    u.UserNama AS CustomerNama
                  FROM {$this->table} e
                  LEFT JOIN booking b ON b.IDBooking = e.IDBooking
                  LEFT JOIN users u ON u.IDUser = b.IDUser
                  WHERE e.IDKaryawan = ?
                    AND TRIM(LOWER(e.EventStatus)) != 'selesai'
                  ORDER BY e.EventTanggal DESC, e.EventMulai ASC";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed (getAssignmentsByKaryawan): " . $this->conn->error);
            return [];
        }

        $stmt->bind_param("i", $idKaryawan);
        $stmt->execute();
        $result = $stmt->get_result();

        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $row['EventStatusClean'] = strtolower(trim($row['EventStatus'] ?? 'unknown'));
            $row['TanggalFormatted'] = $row['EventTanggal'] ? date('d M Y', strtotime($row['EventTanggal'])) : '—';
            $row['WaktuMulai'] = $row['EventMulai'] ? date('H:i', strtotime($row['EventMulai'])) : '—';
            $row['WaktuSelesai'] = $row['EventSelesai'] ? date('H:i', strtotime($row['EventSelesai'])) : '—';
            $row['EventDurasiFormatted'] = ((int) $row['EventDurasi']) . " jam";
            $assignments[] = $row;
        }

        if (count($assignments) === 0) {
            error_log("⚠️ Tidak ada event aktif untuk IDKaryawan={$idKaryawan}");
        }

        $stmt->close();
        return $assignments;
    }

    // ==========================================================
    // 📊 STATISTIK EVENT
    // ==========================================================
    public function getStats($idKaryawan) {
        $this->updateStatusOtomatis();

        $query = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN TRIM(LOWER(EventStatus)) = 'selesai' THEN 1 ELSE 0 END) AS selesai,
                    SUM(CASE WHEN TRIM(LOWER(EventStatus)) = 'berjalan' THEN 1 ELSE 0 END) AS berjalan,
                    SUM(CASE WHEN TRIM(LOWER(EventStatus)) = 'menunggu' THEN 1 ELSE 0 END) AS menunggu
                  FROM {$this->table}
                  WHERE IDKaryawan = ?";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed (getStats): " . $this->conn->error);
            return ['total' => 0, 'selesai' => 0, 'berjalan' => 0, 'menunggu' => 0];
        }

        $stmt->bind_param("i", $idKaryawan);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc() ?: ['total' => 0, 'selesai' => 0, 'berjalan' => 0, 'menunggu' => 0];
        $stmt->close();

        return $row;
    }

    // ==========================================================
    // ➕ TAMBAH EVENT BARU
    // ==========================================================
    public function tambahEvent($nama, $lokasi, $idBooking, $idKaryawan, $tanggal, $mulai, $durasi) {
        $query = "
            INSERT INTO {$this->table} 
            (EventNama, EventLokasi, IDBooking, IDKaryawan, EventTanggal, EventDurasi, EventMulai, EventSelesai, EventStatus)
            VALUES (?, ?, ?, ?, ?, ?, ?, 
                DATE_ADD(TIMESTAMP(?, ?), INTERVAL ? HOUR),
                'Menunggu'
            )
        ";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed (insert): " . $this->conn->error);
            return false;
        }

        $stmt->bind_param(
            "ssississsi",
            $nama,
            $lokasi,
            $idBooking,
            $idKaryawan,
            $tanggal,
            $durasi,
            $mulai,
            $tanggal,
            $mulai,
            $durasi
        );

        $success = $stmt->execute();
        if (!$success) {
            error_log("Gagal tambah event: " . $stmt->error);
        }

        $stmt->close();
        return $success;
    }
}
?>
