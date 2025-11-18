<?php
class EventAssignment {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;

        // Pastikan koneksi aktif + charset aman
        if ($this->conn instanceof mysqli) {
            $this->conn->set_charset("utf8mb4");
        }
    }

    /**
     * ====================================================
     *  BUAT EVENT + MASUKKAN MULTIPLE KARYAWAN
     *  Struktur tabel sudah disesuaikan dengan yang kamu beri
     * ====================================================
     */
    public function createEvent(
        $idBooking,
        $eventNama,
        $eventLokasi,
        $eventTanggal,
        $eventMulai,
        $eventDurasi,
        $karyawanIds
    ) {
        /** ================= VALIDASI ================= */
        if (
            empty($idBooking) ||
            empty($eventNama) ||
            empty($eventLokasi) ||
            empty($eventTanggal) ||
            empty($eventMulai) ||
            $eventDurasi < 1 ||
            empty($karyawanIds) ||
            !is_array($karyawanIds)
        ) {
            return false;
        }

        try {

            /** =============================================== 
             *   MULAI TRANSAKSI
             * =============================================== */
            $this->conn->autocommit(false);
            $this->conn->begin_transaction();

            /** ===============================================
             *  HITUNG WAKTU SELESAI (AUTO)
             * =============================================== */
            $start = new DateTime("$eventTanggal $eventMulai");
            $end   = clone $start;
            $end->modify("+{$eventDurasi} hours");

            $waktuMulai   = $start->format("H:i:s");
            $waktuSelesai = $end->format("H:i:s");

            /** ===============================================
             * 1) INSERT EVENT
             * =============================================== */
            $sqlEvent = "
                INSERT INTO event
                    (EventNama, EventLokasi, IDBooking, EventTanggal, EventDurasi, EventMulai, EventSelesai, EventStatus, CreatedAt)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, 'Menunggu', NOW())
            ";

            $stmtEvent = $this->conn->prepare($sqlEvent);
            if (!$stmtEvent) {
                throw new Exception("Prepare event gagal: " . $this->conn->error);
            }

            $stmtEvent->bind_param(
                "ssissss",
                $eventNama,
                $eventLokasi,
                $idBooking,
                $eventTanggal,
                $eventDurasi,
                $waktuMulai,
                $waktuSelesai
            );

            if (!$stmtEvent->execute()) {
                throw new Exception("Insert event gagal: " . $stmtEvent->error);
            }

            $idEvent = $this->conn->insert_id;
            $stmtEvent->close();

            /** ===============================================
             * 2) INSERT EVENT KARYAWAN (MULTIPLE)
             * =============================================== */
            $sqlKary = "
                INSERT INTO event_karyawan (IDEvent, IDKaryawan)
                VALUES (?, ?)
            ";
            $stmtKary = $this->conn->prepare($sqlKary);

            if (!$stmtKary) {
                throw new Exception("Prepare event_karyawan gagal: " . $this->conn->error);
            }

            foreach ($karyawanIds as $idKary) {
                $idKary = (int)$idKary;
                $stmtKary->bind_param("ii", $idEvent, $idKary);

                if (!$stmtKary->execute()) {
                    throw new Exception("Insert event_karyawan gagal: " . $stmtKary->error);
                }
            }

            $stmtKary->close();

            /** ===============================================
             * COMMIT TRANSAKSI
             * =============================================== */
            $this->conn->commit();
            $this->conn->autocommit(true);

            return $idEvent;

        } catch (Exception $e) {
            $this->conn->rollback();
            $this->conn->autocommit(true);
            error_log("CREATE EVENT ERROR: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ====================================================
     *  UPDATE STATUS OTOMATIS (BERJALAN → SELESAI)
     * ====================================================
     */
    public function updateStatusOtomatis() {
        $this->conn->query("
            UPDATE event 
            SET EventStatus = 'Berjalan'
            WHERE EventStatus = 'Menunggu'
              AND EventTanggal = CURDATE()
              AND EventMulai <= CURTIME()
              AND EventSelesai > CURTIME()
        ");

        $this->conn->query("
            UPDATE event 
            SET EventStatus = 'Selesai'
            WHERE EventStatus IN ('Menunggu','Berjalan')
              AND (EventTanggal < CURDATE()
               OR (EventTanggal = CURDATE() AND EventSelesai <= CURTIME()))
        ");
    }

    /**
     * ====================================================
     *  GET EVENT AKTIF UNTUK KARYAWAN
     * ====================================================
     */
    public function getAssignmentsByKaryawan($idKaryawan) {
        $this->updateStatusOtomatis();

        $stmt = $this->conn->prepare("
            SELECT e.*, u.UserNama AS CustomerNama 
            FROM event_karyawan ek
            JOIN event e ON ek.IDEvent = e.IDEvent
            LEFT JOIN booking b ON e.IDBooking = b.IDBooking
            LEFT JOIN users u ON b.IDUser = u.IDUser
            WHERE ek.IDKaryawan = ?
              AND e.EventStatus != 'Selesai'
            ORDER BY e.EventTanggal, e.EventMulai
        ");

        $stmt->bind_param("i", $idKaryawan);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    /**
     * ====================================================
     *  GET DAFTAR KARYAWAN DALAM EVENT
     * ====================================================
     */
    public function getKaryawanByEvent($idEvent) {
        $stmt = $this->conn->prepare("
            SELECT u.IDUser, u.UserNama
            FROM event_karyawan ek
            JOIN users u ON ek.IDKaryawan = u.IDUser
            WHERE ek.IDEvent = ?
        ");

        $stmt->bind_param("i", $idEvent);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

public function getStats($idKaryawan) {

    $query = "SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN TRIM(LOWER(e.EventStatus)) = 'menunggu' THEN 1 ELSE 0 END) AS menunggu,
                SUM(CASE WHEN TRIM(LOWER(e.EventStatus)) = 'berjalan' THEN 1 ELSE 0 END) AS berjalan,
                SUM(CASE WHEN TRIM(LOWER(e.EventStatus)) = 'selesai' THEN 1 ELSE 0 END) AS selesai
            FROM event_karyawan ek
            JOIN event e ON ek.IDEvent = e.IDEvent
            WHERE ek.IDKaryawan = ?";

    $stmt = $this->conn->prepare($query);
    if (!$stmt) {
        return [
            'total' => 0,
            'menunggu' => 0,
            'berjalan' => 0,
            'selesai' => 0
        ];
    }

    $stmt->bind_param("i", $idKaryawan);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();

    return $data ?: [
        'total' => 0,
        'menunggu' => 0,
        'berjalan' => 0,
        'selesai' => 0
    ];
}

}
?>
