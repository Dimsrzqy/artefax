<?php
class EventAssignment {
    private $conn;
    private $table = "event";

    public function __construct($db) {
        $this->conn = $db;
    }

    // ==========================================================
    // 🔄 UPDATE STATUS OTOMATIS JIKA WAKTU SUDAH LEWAT
    // ==========================================================
    public function updateStatusOtomatis() {
        $query = "
            UPDATE {$this->table}
            SET EventStatus = 'Selesai'
            WHERE EventStatus != 'Selesai'
              AND NOW() > DATE_ADD(
                  TIMESTAMP(EventTanggal, EventMulai),
                  INTERVAL EventDurasi HOUR
              )
        ";

        if (!$this->conn->query($query)) {
            error_log('Gagal update status otomatis: ' . $this->conn->error);
        }
    }

    // ==========================================================
    // 📅 AMBIL PENUGASAN BERDASARKAN KARYAWAN (Kecuali yang Selesai)
    // ==========================================================
    public function getAssignmentsByKaryawan($idKaryawan) {
        // Pastikan status terbaru
        $this->updateStatusOtomatis();

        $query = "SELECT 
                    e.IDEvent, e.EventNama, e.EventLokasi, e.IDBooking, e.IDKaryawan,
                    e.EventTanggal, e.EventDurasi, e.EventMulai, e.EventSelesai,
                    e.EventStatus, e.CreatedAt, e.UpdatedAt,
                    u.UserNama AS CustomerNama
                  FROM {$this->table} e
                  LEFT JOIN booking b ON e.IDBooking = b.IDBooking
                  LEFT JOIN users u ON b.IDUser = u.IDUser
                  WHERE e.IDKaryawan = ?
                    AND e.EventStatus != 'Selesai'
                  ORDER BY e.EventTanggal DESC, e.EventMulai ASC";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return [];
        }

        $stmt->bind_param("i", $idKaryawan);
        $stmt->execute();
        $result = $stmt->get_result();

        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            // === STATUS CLEAN UNTUK CSS BADGE ===
            $row['EventStatusClean'] = strtolower(trim($row['EventStatus']));

            // === FORMAT TANGGAL & JAM ===
            $row['TanggalFormatted'] = date('d M Y', strtotime($row['EventTanggal']));
            $row['WaktuMulai']       = $row['EventMulai']   ? date('H:i', strtotime($row['EventMulai']))   : '—';
            $row['WaktuSelesai']     = $row['EventSelesai'] ? date('H:i', strtotime($row['EventSelesai'])) : '—';

            // === FORMAT DURASI (jam) ===
            $durasiJam = (int) $row['EventDurasi'];
            $row['EventDurasiFormatted'] = $durasiJam . " jam";

            $assignments[] = $row;
        }

        $stmt->close();
        return $assignments;
    }

    // ==========================================================
    // 📊 STATISTIK EVENT PER KARYAWAN
    // ==========================================================
    public function getStats($idKaryawan) {
        // Pastikan status terbaru agar data akurat
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
            error_log("Prepare failed: " . $this->conn->error);
            return ['total' => 0, 'selesai' => 0, 'berjalan' => 0, 'menunggu' => 0];
        }

        $stmt->bind_param("i", $idKaryawan);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();
        return $row ?: ['total' => 0, 'selesai' => 0, 'berjalan' => 0, 'menunggu' => 0];
    }

    // ==========================================================
    // ➕ TAMBAH EVENT BARU (AUTO HITUNG SELESAI)
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
