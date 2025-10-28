<?php
class Absensi
{
    private $conn;
    private $table = "presensi";

    // Properti sesuai kolom tabel (SESUAI KAMU)
    public $IDPresensi;
    public $IDUser;
    public $IDBooking;        // ← BUKAN IDBookingPsn
    public $PsnWaktu;         // ← BUKAN WaktuPsn
    public $PsnLokasi;        // ← BUKAN LokasiPsn
    public $PsnFoto;          // ← BUKAN FotoPsn
    public $PsnStatus;        // ← BUKAN Status

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // TAMPILKAN SEMUA ABSENSI (HANYA UNTUK LIHAT)
    public function tampilSemua()
    {
        $sql = "SELECT 
                    p.PsnWaktu,
                    p.PsnLokasi,
                    p.PsnFoto,
                    p.PsnStatus,
                    u.UserNama
                FROM {$this->table} p
                LEFT JOIN users u ON p.IDUser = u.IDUser
                ORDER BY p.PsnWaktu DESC";

        $result = $this->conn->query($sql);

        if (!$result) {
            error_log("Absensi Query Error: " . $this->conn->error);
            return false;
        }

        return $result;
    }

    // TAMBAH ABSENSI (JIKA DIPERLUKAN)
    public function tambah()
    {
        $sql = "INSERT INTO {$this->table} 
                (IDUser, IDBooking, PsnWaktu, PsnLokasi, PsnFoto, PsnStatus)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed (tambah): " . $this->conn->error);
            return false;
        }

        $stmt->bind_param(
            "iissss",
            $this->IDUser,
            $this->IDBooking,
            $this->PsnWaktu,
            $this->PsnLokasi,
            $this->PsnFoto,
            $this->PsnStatus
        );

        $result = $stmt->execute();
        if (!$result) {
            error_log("Execute failed (tambah): " . $stmt->error);
        }
        $stmt->close();
        return $result;
    }

    // UPDATE STATUS
    public function updateStatus($idPresensi, $status)
    {
        $sql = "UPDATE {$this->table} SET PsnStatus = ? WHERE IDPresensi = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("si", $status, $idPresensi);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // HAPUS
    public function hapus($idPresensi)
    {
        $sql = "DELETE FROM {$this->table} WHERE IDPresensi = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("i", $idPresensi);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>