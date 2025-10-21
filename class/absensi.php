<?php
class Absensi
{
    private $conn;
    private $table = "absensi";

    public $IDAbsensi;
    public $IDUser;
    public $IDBooking;
    public $Waktu;
    public $Lokasi;
    public $Foto;
    public $Status;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ✅ Tambah absensi baru
    public function tambah()
    {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (IDUser, IDBooking, Waktu, Lokasi, Foto, Status)
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal menyiapkan query: " . $this->conn->error);
            }

            $stmt->bind_param("iissss",
                $this->IDUser,
                $this->IDBooking,
                $this->Waktu,
                $this->Lokasi,
                $this->Foto,
                $this->Status
            );

            $result = $stmt->execute();
            if ($result === false) {
                throw new Exception("Gagal mengeksekusi query: " . $stmt->error);
            }

            return $result;
        } catch (Exception $e) {
            // Anda bisa log error di sini atau kembalikan false
            return false;
        }
    }

    // ✅ Tampilkan semua absensi
    public function tampilSemua()
    {
        try {
            $sql = "SELECT a.*, u.NamaUser, b.IDBooking
                    FROM {$this->table} a
                    JOIN Users u ON a.IDUser = u.IDUser
                    JOIN Booking b ON a.IDBooking = b.IDBooking
                    ORDER BY a.Waktu DESC";
            $result = $this->conn->query($sql);
            if ($result === false) {
                throw new Exception("Gagal menjalankan query: " . $this->conn->error);
            }
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    // ✅ Tampilkan absensi berdasarkan user
    public function tampilByUser($idUser)
    {
        try {
            $sql = "SELECT a.*, b.IDBooking
                    FROM {$this->table} a
                    JOIN Booking b ON a.IDBooking = b.IDBooking
                    WHERE a.IDUser = ?
                    ORDER BY a.Waktu DESC";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal menyiapkan query: " . $this->conn->error);
            }

            $stmt->bind_param("i", $idUser);
            $stmt->execute();
            return $stmt->get_result();
        } catch (Exception $e) {
            return false;
        }
    }

    // ✅ Update status absensi (Hadir, Izin, Alpha)
    public function updateStatus($idAbsensi, $status)
    {
        try {
            $sql = "UPDATE {$this->table} SET Status = ? WHERE IDAbsensi = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal menyiapkan query: " . $this->conn->error);
            }

            $stmt->bind_param("si", $status, $idAbsensi);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    // ✅ Hapus absensi
    public function hapus($idAbsensi)
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE IDAbsensi = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Gagal menyiapkan query: " . $this->conn->error);
            }

            $stmt->bind_param("i", $idAbsensi);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
}
?>