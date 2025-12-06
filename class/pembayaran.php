<?php
class Pembayaran
{
    private $conn;
    private $table = "pembayaran";

    // --- Kolom Properti (dipertahankan dari kode Anda) ---
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

    // ... (Kolom tabel lain, dipertahankan) ...
    public $IDUser;
    public $UserNama;
    public $RefundJumlah;
    
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Mengubah array status (mis. ['Lunas', 'Pending']) menjadi string SQL yang aman.
     * @param string|array $statusFilter Status yang dicari.
     * @return string Klausa SQL (mis. " IN ('Lunas', 'Pending')")
     */
    private function getStatusCondition($statusFilter) {
        if (is_array($statusFilter)) {
            $quotedStatuses = array_map(function($s) {
                return "'" . $this->conn->real_escape_string($s) . "'";
            }, $statusFilter);
            return " IN (" . implode(',', $quotedStatuses) . ")";
        } else {
            return " = '" . $this->conn->real_escape_string($statusFilter) . "'";
        }
    }


    // =============================
    // Total DATA (Halaman Daftar Pembayaran)
    // =============================
    public function TotalBooking($startDate = null, $endDate = null, $statusFilter = null)
    {
        $query = "SELECT COUNT(*) AS total FROM {$this->table} p";
        $conditions = [];
        $bindTypes = '';
        $bindParams = [];
        $statusWhere = '';

        // Filter Status Pembayaran (Mencegah Array to string conversion)
        if ($statusFilter) {
            $statusWhere = $this->getStatusCondition($statusFilter);
            $conditions[] = "p.PbrStatus {$statusWhere}";
        }

        // Filter Tanggal
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

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return 0;

        if (!empty($bindParams)) {
            $stmt->bind_param($bindTypes, ...$bindParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? (int)$row['total'] : 0;
    }
    // =============================
    // Total DATA (Halaman Konfirmasi)
    // =============================
    public function TotalBookingPending($startDate = null, $endDate = null, $statusFilter = null)
    {
        $query = "SELECT COUNT(*) AS total FROM {$this->table} p WHERE PbrStatus= 'Pending'";
        $conditions = [];
        $bindTypes = '';
        $bindParams = [];
        $statusWhere = '';

        // Filter Status Pembayaran (Mencegah Array to string conversion)
        if ($statusFilter) {
            $statusWhere = $this->getStatusCondition($statusFilter);
            $conditions[] = "p.PbrStatus {$statusWhere}";
        }

        // Filter Tanggal
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

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return 0;

        if (!empty($bindParams)) {
            $stmt->bind_param($bindTypes, ...$bindParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? (int)$row['total'] : 0;
    }

    // =============================
    // READ DATA (Laporan Keuangan)
    // =============================
    public function readJoin($limit = null, $offset = null, $startDate = null, $endDate = null, $statusFilter = null)
    {
        // 1. Tentukan Kondisi Status (Mencegah Array to string conversion)
        $statusCondition = $statusFilter ? "AND p.PbrStatus {$this->getStatusCondition($statusFilter)}" : '';

        // 2. Tentukan Kondisi Tanggal & Parameter Binding
        $dateConditions = [];
        $bindTypes = '';
        $bindParams = [];

        if ($startDate) {
            $dateConditions[] = "p.CreatedAt >= ?";
            $bindTypes .= 's';
            $bindParams[] = $startDate;
        }
        if ($endDate) {
            $dateConditions[] = "p.CreatedAt < ?";
            $bindTypes .= 's';
            $bindParams[] = $endDate;
        }
        $dateWhereClause = !empty($dateConditions) ? " AND " . implode(" AND ", $dateConditions) : '';
        
        // 3. Tentukan Limit/Offset
        $limitClause = '';
        if ($limit !== null && $offset !== null) {
            $limitClause = " LIMIT ? OFFSET ?";
            $bindTypes .= 'ii';
            $bindParams[] = $limit;
            $bindParams[] = $offset;
        }

        // 4. Query Utama (dengan LEFT JOIN refund)
        $query = "
            SELECT 
                p.IDPembayaran,
                p.IDBooking,
                p.PbrJumlah, /* PbrJumlah sudah di-update menjadi pendapatan bersih */
                p.PbrMetode,
                p.PbrStatus,
                p.PbrConfirmed,
                p.PbrBukti,
                p.CreatedAt,
                b.IDUser,
                b.BkgTotalHarga, /* BkgTotalHarga setelah di-update (Sama dengan PbrJumlah) */
                r.RefundJumlah, /* Jumlah Refund yang diajukan */
                
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
                
                GROUP_CONCAT(DISTINCT g.BkgDetailJenis SEPARATOR ', ') AS JenisBooking

            FROM pembayaran p
                LEFT JOIN booking b ON p.IDBooking = b.IDBooking
                LEFT JOIN users u ON b.IDUser = u.IDUser
                LEFT JOIN booking_detail g ON b.IDBooking = g.IDBooking
                LEFT JOIN alat a ON g.IDAlat = a.IDAlat
                LEFT JOIN paketjasa j ON g.IDPaket = j.IDPaket
                LEFT JOIN refund r ON p.IDBooking = r.IDBooking /* JOIN ke refund untuk ambil jumlah potongan */
            WHERE p.IDPembayaran IS NOT NULL
            {$statusCondition}
            {$dateWhereClause}
            
            GROUP BY p.IDPembayaran, p.PbrJumlah, p.PbrMetode, p.PbrStatus, p.PbrConfirmed, p.PbrBukti, p.CreatedAt, u.UserNama, b.IDUser, b.BkgTotalHarga, r.RefundJumlah 
            ORDER BY p.CreatedAt DESC
            {$limitClause}
            ";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return [];

        if (!empty($bindParams)) {
            $stmt->bind_param($bindTypes, ...$bindParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    // =============================
    // READ Status Pending (Konfirmasi Pembayaran)
    // =============================
    public function readPending($limit = null, $offset = null, $startDate = null, $endDate = null, $statusFilter = null)
{
    $query = "
        SELECT
            p.IDPembayaran, 
            p.IDBooking,     
            p.PbrJumlah,
            p.PbrMetode,
            p.PbrKeterangan,
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

        WHERE p.PbrStatus = 'Pending'
    ";

    // === Tambahkan filter tanggal & status (sama seperti di TotalBookingPending) ===
    $conditions = [];
    $bindTypes = '';
    $bindParams = [];

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
    if ($statusFilter) {
        $statusWhere = $this->getStatusCondition($statusFilter);
        $conditions[] = "p.PbrStatus {$statusWhere}";
    }

    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }

    // === ORDER BY dan LIMIT ===
    $query .= " GROUP BY p.IDPembayaran
                ORDER BY p.CreatedAt DESC";

    // Tambahkan LIMIT hanya jika parameter diberikan
    if ($limit !== null && $offset !== null) {
        $query .= " LIMIT ? OFFSET ?";
        $bindTypes .= 'ii';
        $bindParams[] = (int)$limit;
        $bindParams[] = (int)$offset;
    }

    $stmt = $this->conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $this->conn->error);
        return [];
    }

    if (!empty($bindParams)) {
        $stmt->bind_param($bindTypes, ...$bindParams);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}
    // =============================
    // READ Status (Lunas DP)
    // =============================
    public function readLunasDP()
    {
        $query = "
            SELECT
                p.IDPembayaran, 
                p.IDBooking,     
                p.PbrJumlah,
                p.PbrMetode,
                p.PbrKeterangan,
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
                p.PbrStatus = 'Lunas DP' 

            GROUP BY 
                p.IDPembayaran, p.IDBooking, p.PbrJumlah, p.PbrMetode, p.PbrStatus, p.PbrConfirmed, p.PbrBukti, p.CreatedAt,
                u.UserNama, b.IDUser, b.BkgTotalHarga, b.BkgTglMulai
            ORDER BY 
                p.CreatedAt ASC
        ";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            error_log("SQL Error in readLunasDP: " . $this->conn->error);
            return [];
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }
    
    // =============================
    // READ Status Pending Refund
    // =============================
    public function readPendingRefund()
    {
        $query = "
            SELECT 
                r.IDRefund,
                r.IDBooking,
                r.RefundJumlah,
                r.RefundWaktu,
                r.RefundAlasan,
                r.RefundStatus,
                r.RefundBukti, /* Asumsi kolom ini ada di tabel refund */

                b.BkgTotalHarga,
                b.BkgTglMulai,
                b.BkgStatus,
                
                u.UserNama, 
                u.IDUser,

                -- Ambil pembayaran asli
                (SELECT p.PbrJumlah
                 FROM pembayaran p 
                 WHERE p.IDBooking = r.IDBooking 
                 ORDER BY p.IDPembayaran DESC LIMIT 1) AS PbrJumlahAwal,

                -- Ambil metode pembayaran terakhir
                (SELECT p3.PbrMetode 
                 FROM pembayaran p3 
                 WHERE p3.IDBooking = r.IDBooking 
                 ORDER BY p3.PbrJumlah DESC LIMIT 1) AS PbrMetode,

                -- Daftar pesanan
                GROUP_CONCAT(
                    DISTINCT
                    CASE 
                        WHEN bd.BkgDetailJenis = 'Paket Jasa' THEN COALESCE(pj.PaketNama, 'Paket Dihapus')
                        WHEN bd.BkgDetailJenis = 'Alat' THEN COALESCE(a.AlatNama, 'Alat Dihapus')
                        ELSE bd.BkgDetailJenis 
                    END
                    SEPARATOR ', '
                ) AS DaftarPesanan

            FROM refund r
            LEFT JOIN booking b ON r.IDBooking = b.IDBooking
            LEFT JOIN users u ON b.IDUser = u.IDUser
            LEFT JOIN booking_detail bd ON b.IDBooking = bd.IDBooking
            LEFT JOIN alat a ON bd.IDAlat = a.IDAlat
            LEFT JOIN paketjasa pj ON bd.IDPaket = pj.IDPaket

            WHERE r.RefundStatus = 'Pending'

            GROUP BY 
                r.IDRefund, r.RefundJumlah, r.RefundWaktu, r.RefundAlasan, r.RefundStatus, r.RefundBukti,
                b.BkgTotalHarga, b.BkgTglMulai, b.BkgStatus,
                u.UserNama, u.IDUser
            ORDER BY r.RefundWaktu DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }
    
    // =============================
    // READ DETAIL DATA (Informasi Detail)
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
            ORDER BY p.CreatedAt ASC
        ";

        if ($limit !== null && $offset !== null) {
            $query .= " LIMIT ? OFFSET ?";
        }
        $stmt = $this->conn->prepare($query);

        if ($limit !== null && $offset !== null) {
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result(); 

        $detailPembayaran = [];
    
        while ($row = $result->fetch_assoc()) { 
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
 
        $IDBooking = htmlspecialchars(strip_tags($this->IDBooking));
        $PbrMetode = htmlspecialchars(strip_tags($this->PbrMetode));
        $PbrJumlah = htmlspecialchars(strip_tags($this->PbrJumlah));
        $PbrBukti  = htmlspecialchars(strip_tags($this->PbrBukti));

        // Bind dengan mysqli
        $stmt->bind_param("ssds", $IDBooking, $PbrMetode, $PbrJumlah, $PbrBukti);

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // =============================
    // UPDATE DATA (Konfirmasi Pembayaran)
    // =============================
    public function updateStatus($id, $aksi) {
        try {
            
            $bkgStatus = $aksi === 'setuju' ? 'Diterima' : 'Batal';

            $stmtCheck = $this->conn->prepare("SELECT PbrKeterangan FROM pembayaran WHERE IDPembayaran = ?");
            $stmtCheck->bind_param("i", $id);
            $stmtCheck->execute();
            $result = $stmtCheck->get_result();
            $row = $result->fetch_assoc();
            $stmtCheck->close();

            if (!$row) {
                throw new Exception("Pembayaran tidak ditemukan");
            }

            $keterangan = $row['PbrKeterangan'] ?? '';

            if ($aksi === 'setuju') {
                $pbrStatus = ($keterangan === 'DP') ? 'Lunas DP' : 'Lunas';
            } else {
                $pbrStatus = 'Gagal';
            }

            $pbrConfirmed = $aksi === 'setuju' ? 1 : 0;

            $this->conn->autocommit(FALSE);

            // Update tabel pembayaran
            $stmt1 = $this->conn->prepare("UPDATE pembayaran SET PbrStatus = ?, PbrConfirmed = ?, UpdatedAt = NOW() WHERE IDPembayaran = ?");
            $stmt1->bind_param("sii", $pbrStatus, $pbrConfirmed, $id);
            $stmt1->execute();
            $stmt1->close();


            $stmt2 = $this->conn->prepare("SELECT IDBooking FROM pembayaran WHERE IDPembayaran = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $row2 = $result2->fetch_assoc();
            $stmt2->close();

            $idBooking = $row2['IDBooking'] ?? null;

            if (!$idBooking) {
                throw new Exception("Booking tidak ditemukan");
            }

            // Update tabel booking
            $stmt3 = $this->conn->prepare("UPDATE booking SET BkgStatus = ?, UpdatedAt = NOW() WHERE IDBooking = ?");
            $stmt3->bind_param("si", $bkgStatus, $idBooking);
            $stmt3->execute();
            $stmt3->close();

            $this->conn->commit();
            $this->conn->autocommit(TRUE);

            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            $this->conn->autocommit(TRUE);
            error_log("Error updateStatus: " . $e->getMessage());
            return false;
        }
    }
    
    // =============================
    // UPDATE DATA (Pelunasan Pembayaran)
    // =============================
    public function updatePelunasan($id, $aksi) {
        try {
            $pbrStatus = $aksi === 'setuju' ? 'Lunas' : 'Gagal';
            $pbrConfirmed = $aksi === 'setuju' ? 1 : 0;
            $bkgStatus = $aksi === 'setuju' ? 'Diterima' : 'Batal';

            $this->conn->autocommit(FALSE);
            
            // Update pembayaran  
            $stmt1 = $this->conn->prepare("UPDATE pembayaran SET PbrStatus=?, PbrConfirmed=?, UpdatedAt=NOW() WHERE IDPembayaran=?");
            $stmt1->bind_param("sii", $pbrStatus, $pbrConfirmed, $id);
            $stmt1->execute();

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
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log($e->getMessage());
            return false;
        } finally {
             $this->conn->autocommit(TRUE);
        }
    }
    
    // =============================
    // UPDATE DATA (Pengajuan Refund)
    // =============================
    public function terimaRefund($idRefund)
    {
        try {
            $this->conn->autocommit(FALSE);

            // 1. Update refund jadi Disetujui
            $stmt = $this->conn->prepare("UPDATE refund SET RefundStatus = 'Disetujui' WHERE IDRefund = ? AND RefundStatus = 'Pending'");
            $stmt->bind_param("i", $idRefund);
            $stmt->execute();

            if ($stmt->affected_rows == 0) {
                throw new Exception("Refund sudah diproses sebelumnya atau tidak ditemukan.");
            }

            // 2. Ambil IDBooking
            $stmt = $this->conn->prepare("SELECT IDBooking FROM refund WHERE IDRefund = ?");
            $stmt->bind_param("i", $idRefund);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $idBooking = $row['IDBooking'] ?? null;

            if (!$idBooking) {
                throw new Exception("ID Booking tidak ditemukan di tabel refund.");
            }

            // 3. Update booking jadi Batal
            $stmt = $this->conn->prepare("UPDATE booking SET BkgStatus = 'Batal', UpdatedAt = NOW() WHERE IDBooking = ?");
            $stmt->bind_param("i", $idBooking);
            $stmt->execute();

            // 4. Update semua pembayaran jadi Gagal
            $stmt = $this->conn->prepare("UPDATE pembayaran SET PbrStatus = 'Gagal', PbrConfirmed = 0, UpdatedAt = NOW() WHERE IDBooking = ?");
            $stmt->bind_param("i", $idBooking);
            $stmt->execute();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            $_SESSION['debug_error'] = $e->getMessage();
            error_log("Error terimaRefund ID $idRefund: " . $e->getMessage());
            return false;
        } finally {
            $this->conn->autocommit(TRUE);
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
        $stmt->bind_param('i', $IDPembayaran); 

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