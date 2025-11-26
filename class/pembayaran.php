<?php
class Pembayaran {
    private $conn;
    private $table = "pembayaran";

    // tabel pembayaran
    public $IDPembayaran;
    public $IDBooking;
    public $PbrMetode;
    public $PbrJumlah;
    public $PbrStatus;
    public $PbrConfirmed;
    public $PbrBukti;
    public $CreatedAt;
    public $UpdatedAt;

    // tabel booking
    public $IDUser;
    public $BkgJenis;
    public $IDPaket;
    public $IDAlat;
    public $BkgAlamat;
    public $BkgTglMulai;
    public $BkgTglSelesai;
    public $BkgTotalHarga;
    public $BkgStatus;

    public $UserNama;
  
    public function __construct($db) {
        $this->conn = $db;
    }
    // =============================
    // Total DATA
    // =============================
    public function TotalBooking() {
        $query = "SELECT COUNT(*) AS total FROM {$this->table}";
        $result = $this->conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['total'];
        }
        return 0;
    }
    // =============================
    // READ DATA
    // =============================
    public function readJoin($limit = null, $offset = null) {
        $query = "
            SELECT 
                p.IDPembayaran,
                p.IDBooking,
                p.PbrJumlah,
                p.PbrMetode,
                p.PbrStatus,
                p.CreatedAt,
                            
                u.UserNama,
                u.IDUser,
          
            GROUP_CONCAT(
            DISTINCT 
            CASE 
            WHEN g.BkgDetailJenis = 'Paket Jasa' THEN j.PaketNama
            WHEN g.BkgDetailJenis = 'Alat' THEN a.AlatNama
            ELSE g.BkgDetailJenis
            END
            SEPARATOR ', '
                ) AS DaftarPesanan,
                        
                
            GROUP_CONCAT(DISTINCT g.BkgDetailJenis 
                SEPARATOR ', ') AS JenisBooking

            FROM pembayaran p
                LEFT JOIN booking b ON p.IDBooking = b.IDBooking
                LEFT JOIN users u ON b.IDUser = u.IDUser
                LEFT JOIN booking_detail g ON b.IDBooking = g.IDBooking
                LEFT JOIN alat a ON g.IDAlat = a.IDAlat
                LEFT JOIN paketjasa j ON g.IDPaket = j.IDPaket
            WHERE p.IDPembayaran IS NOT NULL
            GROUP BY p.IDPembayaran, p.IDBooking, p.PbrJumlah, p.PbrMetode, p.PbrStatus, p.CreatedAt, u.UserNama, u.IDUser
                    ORDER BY p.CreatedAt DESC
            ";

        if ($limit !== null && $offset !== null) {
            $query .= " LIMIT ? OFFSET ?";
        }
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return [];

        if ($limit !== null && $offset !== null) {
            $stmt->bind_param("ii", $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }
    // =============================
    // READ Status Pending
    // =============================
    public function readPending() {
    $query = "
            SELECT
                p.IDPembayaran, 
                p.IDBooking,    
                p.PbrJumlah,
                p.PbrMetode,
                p.CreatedAt,
                
                COALESCE(u.UserNama, 'Pengguna Tidak Ditemukan') AS UserNama,
                u.IDUser,
                
                GROUP_CONCAT(
                    DISTINCT
                    CASE 
                        WHEN g.BkgDetailJenis = 'Paket Jasa' THEN COALESCE(j.PaketNama, 'Paket Dihapus')
                        WHEN g.BkgDetailJenis = 'Alat' THEN COALESCE(a.AlatNama, 'Alat Dihapus')
                        ELSE g.BkgDetailJenis 
                    END
                    ORDER BY g.BkgDetailJenis
                    SEPARATOR ', '
                ) AS DaftarPesanan,
                b.BkgTglMulai,
                b.BkgTotalHarga,
                GROUP_CONCAT(DISTINCT g.BkgDetailJenis ORDER BY g.BkgDetailJenis SEPARATOR ', ') AS JenisBooking

            FROM pembayaran p
            LEFT JOIN booking b ON p.IDBooking = b.IDBooking
            LEFT JOIN users u ON b.IDUser = u.IDUser
            LEFT JOIN booking_detail g ON b.IDBooking = g.IDBooking
            LEFT JOIN alat a ON g.IDAlat = a.IDAlat
            LEFT JOIN paketjasa j ON g.IDPaket = j.IDPaket

            WHERE 
                p.PbrStatus = 'pending' 

            GROUP BY 
                p.IDPembayaran, p.IDBooking, p.PbrJumlah, p.PbrMetode, p.CreatedAt, u.UserNama, u.IDUser
            ORDER BY 
                p.CreatedAt ASC -- (pertama masuk)
        ";

        
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return []; 
    }

    // =============================
    // READ DETAIL DATA
    // =============================
    public function readJoinFull() {
        $query = "
            SELECT 
                p.*, 
                b.IDUser, b.BkgJenis, b.IDPaket, b.IDAlat, b.BkgAlamat,
                b.BkgTglMulai, b.BkgTglSelesai, b.BkgTotalHarga, b.BkgStatus
            FROM " . $this->table . " p
            JOIN booking b ON p.IDBooking = b.IDBooking
            ORDER BY p.CreatedAt DESC
        ";

         $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // =============================
    // CREATE DATA
    // =============================
    public function create() {
        $query = "
            INSERT INTO " . $this->table . " 
                (IDBooking, PbrMetode, PbrJumlah, PbrStatus, PbrConfirmed, PbrBukti, CreatedAt, UpdatedAt)
            VALUES 
                (:IDBooking, :PbrMetode, :PbrJumlah, 'Pending', 0, :PbrBukti, NOW(), NOW())
        ";

        $stmt = $this->conn->prepare($query);

        // Sanitasi
        $this->IDBooking = htmlspecialchars(strip_tags($this->IDBooking));
        $this->PbrMetode = htmlspecialchars(strip_tags($this->PbrMetode));
        $this->PbrJumlah = htmlspecialchars(strip_tags($this->PbrJumlah));
        $this->PbrBukti  = htmlspecialchars(strip_tags($this->PbrBukti));

        // Bind
        $stmt->bindParam(':IDBooking', $this->IDBooking);
        $stmt->bindParam(':PbrMetode', $this->PbrMetode);
        $stmt->bindParam(':PbrJumlah', $this->PbrJumlah);
        $stmt->bindParam(':PbrBukti', $this->PbrBukti);

        return $stmt->execute();
    }

    // =============================
    // UPDATE DATA
    // =============================
    public function updateStatus() {
        try {
        $this->conn->beginTransaction();

        if ($aksi === 'setuju') {
            $pbrStatus     = 'Lunas';
            $pbrConfirmed  = 1;
            $bkgStatus     = 'Diterima';
        } else {
            $pbrStatus     = 'Gagal';
            $pbrConfirmed  = 0;
            $bkgStatus     = 'Batal';
        }
        $queryPbr = "
            UPDATE pembayaran 
            SET 
                PbrStatus = ?,
                PbrConfirmed = ?,
                UpdatedAt = NOW()
            WHERE IDPembayaran = ?
        ";

        $stmt1 = $this->conn->prepare($queryPbr);
        $stmt1->execute([$pbrStatus, $pbrConfirmed, $idPembayaran]);

        $queryGetBooking = "SELECT IDBooking FROM pembayaran WHERE IDPembayaran = ?";
        $stmtGet = $this->conn->prepare($queryGetBooking);
        $stmtGet->execute([$idPembayaran]);
        $idBooking = $stmtGet->fetchColumn();

        if (!$idBooking) {
            throw new Exception("ID Booking tidak ditemukan.");
        }

        // 3. Update tabel booking
        $queryBooking = "UPDATE booking 
                         SET BkgStatus = ?, 
                             UpdatedAt = NOW() 
                         WHERE IDBooking = ?";
        $stmt2 = $this->conn->prepare($queryBooking);
        $stmt2->execute([$bkgStatus, $idBooking]);

        $this->conn->commit();
        return true;

    } catch (Exception $e) {
        $this->conn->rollBack();
        error_log("Error updateStatusDanBooking: " . $e->getMessage());
        return false;
    }
}
    // =============================
    // DELETE DATA
    // =============================
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE IDPembayaran = :IDPembayaran";
        $stmt = $this->conn->prepare($query);

        $this->IDPembayaran = htmlspecialchars(strip_tags($this->IDPembayaran));
        $stmt->bindParam(':IDPembayaran', $this->IDPembayaran);

        return $stmt->execute();
    }

    // =============================
    // SEARCH ID DATA
    // =============================
    public function getSingle() {
        $query = "SELECT * FROM " . $this->table . " WHERE IDPembayaran = :IDPembayaran LIMIT 1";
        $stmt = $this->conn->prepare($query);

        $this->IDPembayaran = htmlspecialchars(strip_tags($this->IDPembayaran));
        $stmt->bindParam(':IDPembayaran', $this->IDPembayaran);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->IDBooking = $row['IDBooking'];
            $this->PbrMetode = $row['PbrMetode'];
            $this->PbrJumlah = $row['PbrJumlah'];
            $this->PbrStatus = $row['PbrStatus'];
            $this->PbrConfirmed = $row['PbrConfirmed'];
            $this->PbrBukti = $row['PbrBukti'];
            $this->CreatedAt = $row['CreatedAt'];
            $this->UpdatedAt = $row['UpdatedAt'];
            return true;
        }
        return false;
    }
}
?>