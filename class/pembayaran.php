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
    public $PbrKeterangan;
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
    public $BkgJaminan;
    public $BkgTglMulai;
    public $BkgTglSelesai;
    public $BkgTotalHarga;
    public $BkgStatus;

    public $UserNama;
    public $UserAlamat;
  
    public function __construct($db) {
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
            WHERE p.IDPembayaran IS NOT NULL
            GROUP BY p.IDPembayaran, p.IDBooking, p.PbrJumlah, p.PbrMetode, p.PbrStatus, p.CreatedAt, u.UserNama, u.IDUser
                    ORDER BY p.CreatedAt ASC
            ";

        // Pagination
        if ($limit !== null && $offset !== null) {
            $query .= " LIMIT ? OFFSET ?";
            $bindTypes = 'ii';
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
    public function readJoinFull($limit = null, $offset = null) {
        $query = "
 SELECT
        p.IDPembayaran,
        p.IDBooking,
        p.PbrKeterangan,
        p.PbrJumlah,
        p.PbrMetode,
        p.PbrStatus,
        p.PbrBukti,
        p.CreatedAt,
        b.BkgJaminan,
        b.BkgAlamat,
        b.BkgTglMulai,
        b.BkgTglSelesai,
        u.UserNama,
        
        GROUP_CONCAT(
            DISTINCT
            CASE
                WHEN g.BkgDetailJenis = 'Paket Jasa' THEN j.PaketNama
                WHEN g.BkgDetailJenis = 'Alat' THEN a.AlatNama
                ELSE CONCAT('Lainnya: ', g.BkgDetailJenis)
            END
            SEPARATOR '||'
        ) AS DaftarPesananRaw,
        
        GROUP_CONCAT(DISTINCT g.BkgDetailJenis SEPARATOR ', ') AS JenisBooking
        
    FROM pembayaran p
        LEFT JOIN booking b ON p.IDBooking = b.IDBooking
        LEFT JOIN users u ON b.IDUser = u.IDUser
        LEFT JOIN booking_detail g ON b.IDBooking = g.IDBooking
        LEFT JOIN alat a ON g.IDAlat = a.IDAlat
        LEFT JOIN paketjasa j ON g.IDPaket = j.IDPaket
    GROUP BY p.IDPembayaran
    ORDER BY p.CreatedAt DESC
        ";

    if ($limit !== null && $offset !== null) {
    $query .= " LIMIT ? OFFSET ?";
    }
    $stmt = $this->conn->prepare($query);

    if ($limit !== null && $offset !== null) {
        $stmt->bind_param("ii", $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result(); // ← INI YANG HILANG!

    $detailPembayaran = []; // ← INI JUGA HARUS DI-INIT DULU!

    while ($row = $result->fetch_assoc()) {
        // Ubah string || jadi array
        $items = $row['DaftarPesananRaw'] ?? '';
        $row['DaftarPesanan'] = $items ? array_filter(explode('||', $items)) : [];
        unset($row['DaftarPesananRaw']);
        
        $detailPembayaran[] = $row;
    }

    $stmt->close();
    return $detailPembayaran;
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
    public function updateStatus($id, $aksi) {
       try {
        $pbrStatus = $aksi === 'setuju' ? 'Lunas' : 'Gagal';
        $pbrConfirmed = $aksi === 'setuju' ? 1 : 0;
        $bkgStatus = $aksi === 'setuju' ? 'Diterima' : 'Batal';

        // Update pembayaran
        $stmt1 = $this->conn->prepare("UPDATE pembayaran SET PbrStatus=?, PbrConfirmed=?, UpdatedAt=NOW() WHERE IDPembayaran=?");
        $stmt1->bind_param("sii", $pbrStatus, $pbrConfirmed, $id);
        $stmt1->execute();

        // Ambil IDBooking
        $stmt2 = $this->conn->prepare("SELECT IDBooking FROM pembayaran WHERE IDPembayaran = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $row = $result->fetch_assoc();
        $idBooking = $row['IDBooking'] ?? null;

        if (!$idBooking) throw new Exception("Booking tidak ditemukan");

        // Update booking
        $stmt3 = $this->conn->prepare("UPDATE booking SET BkgStatus=?, UpdatedAt=NOW() WHERE IDBooking=?");
        $stmt3->bind_param("si", $bkgStatus, $idBooking);
        $stmt3->execute();

        $this->conn->commit();
        $this->conn->autocommit(TRUE);
        return true;

    } catch (Exception $e) {
        $this->conn->rollback();
        $this->conn->autocommit(TRUE);
        error_log($e->getMessage());
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
