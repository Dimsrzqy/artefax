<?php
class EventAssignment
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
        if ($this->conn instanceof mysqli) {
            $this->conn->set_charset("utf8mb4");
        }
    }

    public function createEvent($idBooking, $eventNama, $eventLokasi, $eventTanggal, $eventMulai, $eventDurasi, $karyawanIds)
    {
        $idBooking    = (int)$idBooking;
        $eventNama    = trim($eventNama);
        $eventLokasi  = trim($eventLokasi);
        $eventTanggal = trim($eventTanggal);
        $eventMulai   = trim($eventMulai);
        $eventDurasi  = (int)$eventDurasi;

        if ($idBooking <= 0 || empty($eventNama) || empty($eventLokasi) || empty($eventTanggal) || empty($eventMulai) || $eventDurasi < 1 || empty($karyawanIds) || !is_array($karyawanIds)) {
            return false;
        }

        $karyawanClean = array_filter(array_map('intval', $karyawanIds));
        if (empty($karyawanClean)) return false;

        try {
            $this->conn->autocommit(false);
            $this->conn->begin_transaction();

            // Gabung tanggal + jam
            $start = new DateTime("$eventTanggal $eventMulai");
            $end   = clone $start;
            $end->modify("+$eventDurasi hours");

            $waktuMulai   = $start->format('H:i:s');
            $waktuSelesai = $end->format('H:i:s');
            $tanggalSql   = $start->format('Y-m-d');

            // Insert event → status Menunggu
            $sql = "INSERT INTO `event` 
                    (`EventNama`, `EventLokasi`, `IDBooking`, `EventTanggal`, `EventDurasi`, `EventMulai`, `EventSelesai`, `EventStatus`, `CreatedAt`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Menunggu', NOW())";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssisiss", $eventNama, $eventLokasi, $idBooking, $tanggalSql, $eventDurasi, $waktuMulai, $waktuSelesai);
            $stmt->execute();
            $idEvent = $this->conn->insert_id;
            $stmt->close();

            // Insert karyawan ke event_karyawan
            $stmtKary = $this->conn->prepare("INSERT INTO event_karyawan (IDEvent, IDKaryawan) VALUES (?, ?)");
            foreach ($karyawanClean as $idKary) {
                $stmtKary->bind_param("ii", $idEvent, $idKary);
                $stmtKary->execute();
            }
            $stmtKary->close();

            // DIBATALKAN: TIDAK LAGI UBAH STATUS BOOKING JADI "Selesai"
            // Status tetap "Diterima" sesuai permintaan Anda

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

    // UPDATE STATUS OTOMATIS EVENT (Menunggu → Berjalan → Selesai)
// UPDATE STATUS OTOMATIS EVENT (Menunggu → Berjalan → Selesai) — FINAL FIX
public function updateStatusOtomatis()
{
    // 1. MENUNGGU → BERJALAN (jika NOW masuk ke interval event)
    $this->conn->query("
        UPDATE event
        SET EventStatus = 'Berjalan'
        WHERE EventStatus = 'Menunggu'
          AND NOW() >= CONCAT(EventTanggal, ' ', EventMulai)
          AND NOW() < ADDTIME(
                CONCAT(EventTanggal, ' ', EventMulai),
                SEC_TO_TIME(EventDurasi * 3600)
          )
    ");

    // 2. MENUNGGU/BERJALAN → SELESAI (jika NOW lewat waktu selesai)
    $this->conn->query("
        UPDATE event
        SET EventStatus = 'Selesai'
        WHERE EventStatus IN ('Menunggu', 'Berjalan')
          AND NOW() >= ADDTIME(
                CONCAT(EventTanggal, ' ', EventMulai),
                SEC_TO_TIME(EventDurasi * 3600)
          )
    ");
}


    public function getAssignmentsByKaryawan($idKaryawan)
    {
        $this->updateStatusOtomatis();

        $stmt = $this->conn->prepare("
            SELECT e.*, u.UserNama AS CustomerNama 
            FROM `event_karyawan` ek
            JOIN `event` e ON ek.IDEvent = e.IDEvent
            LEFT JOIN booking b ON e.IDBooking = b.IDBooking
            LEFT JOIN users u ON b.IDUser = u.IDUser
            WHERE ek.IDKaryawan = ?
              AND e.EventStatus != 'Selesai'
            ORDER BY e.EventTanggal, e.EventMulai
        ");

        $stmt->bind_param("i", $idKaryawan);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    public function getKaryawanByEvent($idEvent)
    {
        $stmt = $this->conn->prepare("
            SELECT u.IDUser, u.UserNama
            FROM `event_karyawan` ek
            JOIN users u ON ek.IDKaryawan = u.IDUser
            WHERE ek.IDEvent = ?
        ");

        $stmt->bind_param("i", $idEvent);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    public function getStats($idKaryawan)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) AS total,
                SUM(e.EventStatus = 'Menunggu') AS menunggu,
                SUM(e.EventStatus = 'Berjalan') AS berjalan,
                SUM(e.EventStatus = 'Selesai') AS selesai
            FROM `event_karyawan` ek
            JOIN `event` e ON ek.IDEvent = e.IDEvent
            WHERE ek.IDKaryawan = ?
        ");

        $stmt->bind_param("i", $idKaryawan);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $data ?: ['total' => 0, 'menunggu' => 0, 'berjalan' => 0, 'selesai' => 0];
    }
}