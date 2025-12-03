<?php
// File: class/Booking.php
// Versi FINAL – sudah sesuai nama tabel paketjasa & alat
// + DITAMBAHKAN: updateStatusSelesaiOtomatis() → otomatis jadi "Selesai" jika tanggal lewat

class Booking
{
    private $conn;
    private $table_booking       = 'booking';
    private $table_booking_detail = 'booking_detail';

    public function __construct($db)
    {
        $this->conn = $db; // objek mysqli
        if ($this->conn instanceof mysqli) {
            $this->conn->set_charset("utf8mb4");
        }
    }

    // OTOMATIS UBAH STATUS BOOKING JADI "Selesai" JIKA TANGGAL & JAM SUDAH LEWAT
    public function updateStatusSelesaiOtomatis()
    {
        $query = "
            UPDATE {$this->table_booking} 
            SET BkgStatus = 'Selesai'
            WHERE BkgStatus = 'Diterima'
              AND BkgTglSelesai < NOW()
        ";
        $this->conn->query($query);
    }

    // Total booking berdasarkan status (KODE ASLI ANDA — TIDAK DIUBAH)
    public function getTotalBooking($status = 'Diterima')
    {
        $status = $this->conn->real_escape_string($status);
        $query  = "SELECT COUNT(*) AS total FROM {$this->table_booking} WHERE BkgStatus = '$status'";
        $result = $this->conn->query($query);
        return $result ? (int)$result->fetch_assoc()['total'] : 0;
    }

    // Daftar booking + nama paket & alat yang benar (KODE ASLI ANDA — TIDAK DIUBAH)
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