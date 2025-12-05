<?php
// File: class/Booking.php
// FINAL VERSION — Dengan fitur otomatis ubah status booking → Selesai

class Booking
{
    private $conn;
    private $table_booking          = 'booking';
    private $table_booking_detail   = 'booking_detail';
    private $table_event            = 'event';
    private $table_users            = 'users';

    public function __construct($db)
    {
        if (!$db instanceof mysqli) {
            throw new Exception("Koneksi database tidak valid.");
        }

        $this->conn = $db;
        $this->conn->set_charset("utf8mb4");

        // --- FITUR BARU: Auto-update status booking jadi Selesai ---
        $this->updateStatusSelesaiOtomatis();
    }

    /**
     * OTOMATIS UBAH STATUS BOOKING JADI "Selesai"
     * Booking berubah menjadi selesai jika waktu NOW sudah melewati BkgTglSelesai
     */
    public function updateStatusSelesaiOtomatis()
    {
        // Logika gabungan untuk menutupi berbagai format kolom BkgTglSelesai (DATE/DATETIME)
        $query = "
            UPDATE {$this->table_booking}
            SET BkgStatus = 'Selesai'
            WHERE BkgStatus = 'Diterima'
              AND BkgTglSelesai IS NOT NULL
              -- Memastikan NOW() sudah melewati BkgTglSelesai, 
              -- Menggunakan DATE_ADD untuk memastikan bahwa jika BkgTglSelesai hanya DATE, 
              -- kita membandingkannya dengan akhir hari (23:59:59)
              AND (
                  STR_TO_DATE(BkgTglSelesai, '%Y-%m-%d %H:%i:%s') < NOW() OR 
                  DATE_ADD(BkgTglSelesai, INTERVAL '23:59:59' HOUR_SECOND) < NOW()
              )
        ";
        $this->conn->query($query);
    }


    /**
     * Menyimpan penugasan karyawan untuk booking ke tabel event
     */
    public function assignKaryawan($IDBooking, $IDKaryawan)
    {
        if (!is_numeric($IDBooking) || !is_numeric($IDKaryawan) || $IDBooking <= 0 || $IDKaryawan <= 0) {
            return false;
        }

        // 1. Ambil detail booking
        $stmt_bkg = $this->conn->prepare("
            SELECT BkgTglMulai, BkgTglSelesai, BkgAlamat
            FROM {$this->table_booking}
            WHERE IDBooking = ? AND BkgStatus = 'Diterima'
        ");
        $stmt_bkg->bind_param("i", $IDBooking);
        $stmt_bkg->execute();
        $result_bkg = $stmt_bkg->get_result();
        $booking_data = $result_bkg->fetch_assoc();
        $stmt_bkg->close();

        if (!$booking_data) {
            return false;
        }

        // 2. Cek apakah sudah ada event
        $stmt_check = $this->conn->prepare("SELECT IDEvent FROM {$this->table_event} WHERE IDBooking = ?");
        $stmt_check->bind_param("i", $IDBooking);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $stmt_check->close();
            return false;
        }
        $stmt_check->close();

        // 3. Insert ke tabel event
        $EventJudul = "Booking #{$IDBooking} - Penugasan";
        $EventDeskripsi = "Lokasi: " . $booking_data['BkgAlamat'];
        $EventTglMulai = $booking_data['BkgTglMulai'];
        $EventTglSelesai = $booking_data['BkgTglSelesai'];

        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table_event}
            (IDUser, IDBooking, EventJudul, EventDeskripsi, EventTglMulai, EventTglSelesai)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param("iissss",
            $IDKaryawan,
            $IDBooking,
            $EventJudul,
            $EventDeskripsi,
            $EventTglMulai,
            $EventTglSelesai
        );

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }


    // ————— ORIGINAL FUNCTIONS (TIDAK DIUBAH) ————— //

    public function getTotalBooking($status = 'Diterima')
    {
        $status = $this->conn->real_escape_string($status);
        $query  = "SELECT COUNT(*) AS total FROM {$this->table_booking} WHERE BkgStatus = '$status'";
        $result = $this->conn->query($query);
        return $result ? (int)$result->fetch_assoc()['total'] : 0;
    }


    public function getBookingList($limit, $offset, $status = 'Diterima')
    {
        $status = $this->conn->real_escape_string($status);
        $limit  = (int)$limit;
        $offset = (int)$offset;

        $query = "SELECT
                      b.*,
                      u.UserNama,
                      pj.PaketNama,
                      a.AlatNama,
                      bd.BkgDetailJenis
                    FROM {$this->table_booking} b
                    LEFT JOIN users u ON b.IDUser = u.IDUser
                    LEFT JOIN {$this->table_booking_detail} bd ON b.IDBooking = bd.IDBooking
                    LEFT JOIN paketjasa pj ON bd.IDPaket = pj.IDPaket
                    LEFT JOIN alat a ON bd.IDAlat = a.IDAlat
                    WHERE b.BkgStatus = '$status'
                    ORDER BY b.BkgTglMulai DESC
                    LIMIT $limit OFFSET $offset";

        $result = $this->conn->query($query);
        if (!$result) return [];

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
}