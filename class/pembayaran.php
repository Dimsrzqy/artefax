<?php
class Pembayaran
{
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

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // =============================
    // Total DATA (DIPERBAIKI: Menerima Filter)
    // =============================
    public function TotalBooking($startDate = null, $endDate = null, $statusFilter = null)
    {
        $query = "SELECT COUNT(*) AS total FROM {$this->table} p";
        $conditions = [];
        $bindTypes = '';
        $bindParams = [];

        // Filter Status Pembayaran
        if ($statusFilter) {
            $conditions[] = "p.PbrStatus = ?";
            $bindTypes .= 's';
            $bindParams[] = $statusFilter;
        }

        // Filter Tanggal
        if ($startDate) {
            $conditions[] = "p.CreatedAt >= ?";
            $bindTypes .= 's';
            $bindParams[] = $startDate;
        }
        if ($endDate) {
            // Gunakan kurang dari (<) karena endDate dihitung eksklusif
            $conditions[] = "p.CreatedAt < ?";
            $bindTypes .= 's';
            $bindParams[] = $endDate;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return 0;

        if (!empty($bindParams)) {
            // Memanggil bind_param dengan array dinamis
            $stmt->bind_param($bindTypes, ...$bindParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? (int)$row['total'] : 0;
    }

    // =============================
    // READ DATA (DIPERBAIKI: Menerima Filter & Pagination)
    // =============================
    public function readJoin($limit = null, $offset = null, $startDate = null, $endDate = null, $statusFilter = null)
    {
        $query = "
            SELECT 
                p.IDPembayaran,
                p.IDBooking,
                p.PbrJumlah,
                p.PbrMetode,
                p.PbrStatus,
                p.PbrConfirmed,
                p.PbrBukti,
                p.CreatedAt,
                b.IDUser,
                b.BkgTotalHarga,
                
                u.UserNama,
                
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
        ";

        $conditions = [];
        $bindTypes = '';
        $bindParams = [];

        // Filter Status Pembayaran (BARU)
        if ($statusFilter) {
            $conditions[] = "p.PbrStatus = ?";
            $bindTypes .= 's';
            $bindParams[] = $statusFilter;
        }

        // Filter Tanggal (BARU)
        if ($startDate) {
            $conditions[] = "p.CreatedAt >= ?";
            $bindTypes .= 's';
            $bindParams[] = $startDate;
        }
        if ($endDate) {
            $conditions[] = "p.CreatedAt < ?";
            $bindTypes .= 's';
            $bindParams[] = $endDate;
        }

        // Gabungkan kondisi
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " 
            GROUP BY p.IDPembayaran, p.IDBooking, p.PbrJumlah, p.PbrMetode, p.PbrStatus, p.CreatedAt, u.UserNama, u.IDUser, b.BkgTotalHarga
            ORDER BY p.CreatedAt DESC
        ";

        // Pagination
        if ($limit !== null && $offset !== null) {
            $query .= " LIMIT ? OFFSET ?";
            $bindTypes .= 'ii';
            $bindParams[] = $limit;
            $bindParams[] = $offset;
        }

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return [];

        if (!empty($bindParams)) {
            // Memanggil bind_param dengan array dinamis
            $stmt->bind_param($bindTypes, ...$bindParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    // =============================
    // READ Status Pending (DIPERBAIKI: Hapus IDUser & Query Konsisten)
    // =============================
    // =============================
    // READ Status Pending (DIPERBAIKI)
    // =============================
    public function readPending()
    {
        $query = "
            SELECT
                p.IDPembayaran, 
                p.IDBooking,     
                p.PbrJumlah,
                p.PbrMetode,
                p.PbrStatus,
                p.PbrConfirmed,
                p.PbrBukti,
                p.CreatedAt,
                
                COALESCE(u.UserNama, 'Pengguna Tidak Ditemukan') AS UserNama,
                b.IDUser,
                b.BkgTotalHarga,
                b.BkgTglMulai,
                
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
                
                GROUP_CONCAT(DISTINCT g.BkgDetailJenis ORDER BY g.BkgDetailJenis SEPARATOR ', ') AS JenisBooking

            FROM pembayaran p
            LEFT JOIN booking b ON p.IDBooking = b.IDBooking
            LEFT JOIN users u ON b.IDUser = u.IDUser
            LEFT JOIN booking_detail g ON b.IDBooking = g.IDBooking
            LEFT JOIN alat a ON g.IDAlat = a.IDAlat
            LEFT JOIN paketjasa j ON g.IDPaket = j.IDPaket

            WHERE 
                p.PbrStatus = 'Pending' 

            GROUP BY 
                p.IDPembayaran, p.IDBooking, p.PbrJumlah, p.PbrMetode, p.PbrStatus, p.PbrConfirmed, p.PbrBukti, p.CreatedAt,
                u.UserNama, b.IDUser, b.BkgTotalHarga, b.BkgTglMulai
            ORDER BY 
                p.CreatedAt ASC
        ";

        // Menggunakan prepare meskipun tanpa binding, untuk konsistensi.
        // Asumsi koneksi Anda sudah diperbaiki menjadi mysqli seperti saran saya sebelumnya.
        $stmt = $this->conn->prepare($query);

        // Cek jika prepare gagal (kemungkinan besar inilah yang memicu error fatal)
        if (!$stmt) {
            error_log("SQL Error in readPending: " . $this->conn->error);
            return [];
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    // =============================
    // READ DETAIL DATA (DIPERBAIKI: Mengambil data user)
    // =============================
    public function readJoinFull()
    {
        $query = "
            SELECT 
                p.*, 
                b.IDUser, b.BkgJenis, b.IDPaket, b.IDAlat, b.BkgAlamat,
                b.BkgTglMulai, b.BkgTglSelesai, b.BkgTotalHarga, b.BkgStatus,
                u.UserNama
            FROM " . $this->table . " p
            JOIN booking b ON p.IDBooking = b.IDBooking
            LEFT JOIN users u ON b.IDUser = u.IDUser
            ORDER BY p.CreatedAt DESC
        ";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return [];

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    // =============================
    // CREATE DATA (DIPERBAIKI: Konsisten mysqli)
    // =============================
    public function create()
    {
        $query = "
            INSERT INTO " . $this->table . " 
                (IDBooking, PbrMetode, PbrJumlah, PbrStatus, PbrConfirmed, PbrBukti, CreatedAt, UpdatedAt)
            VALUES 
                (?, ?, ?, 'Pending', 0, ?, NOW(), NOW())
        ";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        // Sanitasi (dipertahankan, meskipun mysqli lebih aman)
        $IDBooking = htmlspecialchars(strip_tags($this->IDBooking));
        $PbrMetode = htmlspecialchars(strip_tags($this->PbrMetode));
        $PbrJumlah = htmlspecialchars(strip_tags($this->PbrJumlah));
        $PbrBukti  = htmlspecialchars(strip_tags($this->PbrBukti));

        // Bind dengan mysqli
        $stmt->bind_param("ssds", $IDBooking, $PbrMetode, $PbrJumlah, $PbrBukti); // s: string, d: double/decimal

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // =============================
    // UPDATE DATA (DIPERBAIKI: Parameter Input & Consistency)
    // =============================
    // Method ini seharusnya memiliki input parameter untuk aksi dan ID
    public function updateStatus($idPembayaran, $aksi)
    {
        // Cek koneksi untuk transaction
        if (method_exists($this->conn, 'begin_transaction')) {
            $this->conn->begin_transaction();
        }

        try {
            $pbrStatus = ($aksi === 'setuju') ? 'Sukses' : 'Gagal'; // 'Lunas' diubah jadi 'Sukses' agar konsisten
            $pbrConfirmed = ($aksi === 'setuju') ? 1 : 0;
            $bkgStatus = ($aksi === 'setuju') ? 'Diterima' : 'Dibatalkan'; // Disesuaikan

            // 1. Update tabel pembayaran
            $queryPbr = "
                UPDATE pembayaran 
                SET 
                    PbrStatus = ?,
                    PbrConfirmed = ?,
                    UpdatedAt = NOW()
                WHERE IDPembayaran = ?
            ";

            $stmt1 = $this->conn->prepare($queryPbr);
            if (!$stmt1) throw new Exception("Prepare statement 1 failed: " . $this->conn->error);
            $stmt1->bind_param("sii", $pbrStatus, $pbrConfirmed, $idPembayaran);
            $stmt1->execute();
            $stmt1->close();

            // 2. Ambil ID Booking
            $queryGetBooking = "SELECT IDBooking FROM pembayaran WHERE IDPembayaran = ?";
            $stmtGet = $this->conn->prepare($queryGetBooking);
            if (!$stmtGet) throw new Exception("Prepare statement Get Booking failed: " . $this->conn->error);
            $stmtGet->bind_param("i", $idPembayaran);
            $stmtGet->execute();
            $resultGet = $stmtGet->get_result();

            if ($row = $resultGet->fetch_assoc()) {
                $idBooking = $row['IDBooking'];
            } else {
                throw new Exception("ID Booking tidak ditemukan.");
            }
            $stmtGet->close();


            // 3. Update tabel booking
            $queryBooking = "UPDATE booking 
                             SET BkgStatus = ?, 
                                 UpdatedAt = NOW() 
                             WHERE IDBooking = ?";
            $stmt2 = $this->conn->prepare($queryBooking);
            if (!$stmt2) throw new Exception("Prepare statement 2 failed: " . $this->conn->error);
            $stmt2->bind_param("si", $bkgStatus, $idBooking);
            $stmt2->execute();
            $stmt2->close();

            if (method_exists($this->conn, 'commit')) {
                $this->conn->commit();
            }
            return true;
        } catch (Exception $e) {
            if (method_exists($this->conn, 'rollback')) {
                $this->conn->rollback();
            }
            error_log("Error updateStatus: " . $e->getMessage());
            return false;
        }
    }

    // =============================
    // DELETE DATA (DIPERBAIKI: Konsisten mysqli)
    // =============================
    public function delete()
    {
        $query = "DELETE FROM " . $this->table . " WHERE IDPembayaran = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $IDPembayaran = htmlspecialchars(strip_tags($this->IDPembayaran));
        $stmt->bind_param('i', $IDPembayaran); // i: integer, asumsi IDPembayaran adalah integer

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // =============================
    // SEARCH ID DATA (DIPERBAIKI: Konsisten mysqli)
    // =============================
    public function getSingle()
    {
        $query = "SELECT * FROM " . $this->table . " WHERE IDPembayaran = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $IDPembayaran = htmlspecialchars(strip_tags($this->IDPembayaran));
        $stmt->bind_param('i', $IDPembayaran);
        $stmt->execute();
        $result = $stmt->get_result();

        $row = $result->fetch_assoc();
        $stmt->close();

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
