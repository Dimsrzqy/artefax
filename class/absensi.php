<?php
class Absensi
{
    private $conn;
    private $table = "presensi";

    // === Properti sesuai tabel ===
    public $IDPresensi;
    public $IDUser;
    public $IDEvent;
    public $PsnWaktu;
    public $PsnLokasi;
    public $PsnFoto;
    public $PsnStatus;

    public function __construct($db)
    {
        if (!$db || !($db instanceof mysqli)) {
            throw new Exception("Koneksi database tidak valid di Absensi class.");
        }
        $this->conn = $db;
    }

    // === Menampilkan semua data absensi ===
    public function tampilSemua()
    {
        $sql = "SELECT 
                    p.IDPresensi,
                    p.PsnWaktu,
                    p.PsnLokasi,
                    p.PsnFoto,
                    p.PsnStatus,
                    u.UserNama,
                    e.EventNama
                FROM {$this->table} p
                LEFT JOIN users u ON p.IDUser = u.IDUser
                LEFT JOIN event e ON p.IDEvent = e.IDEvent
                ORDER BY p.PsnWaktu DESC";

        $result = $this->conn->query($sql);
        if (!$result) {
            error_log('Absensi Query Error: ' . $this->conn->error);
            return false;
        }

        return $result;
    }

    // === Menambahkan data absensi ===
    public function tambah()
    {
        // Validasi wajib
        if (empty($this->IDUser) || empty($this->IDEvent) || empty($this->PsnWaktu) || empty($this->PsnFoto)) {
            error_log("Gagal tambah absensi: data wajib tidak lengkap.");
            return false;
        }

        // Default status ke 'Hadir' jika belum diisi
        $this->PsnStatus = $this->PsnStatus ?: 'Hadir';

        $sql = "INSERT INTO {$this->table} 
                (IDUser, IDEvent, PsnWaktu, PsnLokasi, PsnFoto, PsnStatus)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed (tambah): " . $this->conn->error);
            return false;
        }

        $stmt->bind_param(
            "iissss",
            $this->IDUser,
            $this->IDEvent,
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

    // === Update status absensi ===
    public function updateStatus($idPresensi, $status)
    {
        if (empty($idPresensi) || empty($status)) {
            error_log("updateStatus() gagal: parameter kosong.");
            return false;
        }

        $sql = "UPDATE {$this->table} SET PsnStatus = ? WHERE IDPresensi = ?";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log("Prepare failed (updateStatus): " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("si", $status, $idPresensi);
        $result = $stmt->execute();

        if (!$result) {
            error_log("Execute failed (updateStatus): " . $stmt->error);
        }

        $stmt->close();
        return $result;
    }

    // === Hapus data absensi ===
    public function hapus($idPresensi)
    {
        if (empty($idPresensi)) {
            error_log("hapus() gagal: IDPresensi kosong.");
            return false;
        }

        $sql = "DELETE FROM {$this->table} WHERE IDPresensi = ?";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log("Prepare failed (hapus): " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $idPresensi);
        $result = $stmt->execute();

        if (!$result) {
            error_log("Execute failed (hapus): " . $stmt->error);
        }

        $stmt->close();
        return $result;
    }
}
