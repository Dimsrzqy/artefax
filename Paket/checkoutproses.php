<?php
// process_checkout.php
session_start();
include __DIR__ . '/../config/koneksi.php'; // sesuaikan path

$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    $_SESSION['error_checkout'] = "Koneksi database gagal.";
    header('Location: checkout.php');
    exit;
}

// ambil cart & user
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    $_SESSION['error_checkout'] = "Keranjang kosong.";
    header('Location: checkout.php');
    exit;
}

$userId = $_POST['user_id'] ?? ($_SESSION['user']['IDUser'] ?? null);
$alamat = trim($_POST['alamat'] ?? '');
$jaminan = $_POST['jaminan'] ?? null;
$phone = trim($_POST['phone'] ?? '');
$tgl_mulai = $_POST['tgl_mulai'] ?? null;
$tgl_selesai = $_POST['tgl_selesai'] ?? null;
$payment = $_POST['payment'] ?? 'lunas';
$deskripsi = trim($_POST['deskripsi'] ?? '');

// validasi minimal
if (!$userId || !$phone || !$tgl_mulai || !$tgl_selesai) {
    $_SESSION['error_checkout'] = "Lengkapi data wajib: user, no HP, tanggal mulai & selesai.";
    header('Location: checkout.php');
    exit;
}

// hitung total
$totalFull = 0.0;
foreach ($cart as $it) {
    $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
    $price = (float)($it['price'] ?? 0);
    $totalFull += $price * $qty;
}

// penetapan jenis booking -> sesuai schema enum('Jasa','Alat')
// jika ada paket set jadi 'Jasa', jika tidak ada paket jadi 'Alat'
$hasPaket = false;
foreach($cart as $it){
    $jenis = strtolower($it['jenis'] ?? ($it['tipe'] ?? ''));
    if($jenis === 'paket'){ $hasPaket = true; break; }
}
$bkgJenis = $hasPaket ? 'Jasa' : 'Alat';

// perhitungan DP: simpan total harga penuh di kolom BkgTotalHarga.
// (DP akan ditagih; aplikasi bisa mencatat pembayaran di tabel terpisah nanti)
$storedTotal = $totalFull;

// insert 1 row per item (IDPaket atau IDAlat sesuai jenis item)
try {
    $conn->begin_transaction();

    $stmt = $conn->prepare(
        "INSERT INTO booking (IDUser, BkgJenis, IDPaket, IDAlat, BkgAlamat, BkgTglMulai, BkgTglSelesai, BkgTotalHarga, BkgStatus, CreatedAt)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())"
    );
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    // loop items
    foreach ($cart as $it) {
        $jenis = strtolower($it['jenis'] ?? ($it['tipe'] ?? ''));
        $idpaket = null;
        $idalat = null;
        if ($jenis === 'paket') {
            $idpaket = (int)($it['id'] ?? $it['IDPaket'] ?? 0) ?: null;
        } else {
            $idalat = (int)($it['id'] ?? $it['IDAlat'] ?? 0) ?: null;
        }

        // Untuk SQL bind, kita need proper types: i s i i s s s d
        // We'll bind as: IDUser (i), BkgJenis (s), IDPaket (i or null), IDAlat (i or null), alamat (s), tgl_mulai (s), tgl_selesai (s), total (d)
        $nullInt = null;
        // prepare values
        $bkgAlamat = $alamat ?: '';
        $bkgTglMulai = $tgl_mulai;
        $bkgTglSelesai = $tgl_selesai;
        $bkgTotalHarga = $storedTotal;

        // bind params: use mysqli_stmt::bind_param with types
        // But bind_param does not accept null for integer directly; use variable that is null or int and pass variable
        $bindIDPaket = $idpaket !== null ? $idpaket : null;
        $bindIDAlat = $idalat !== null ? $idalat : null;

        // For null integers, use NULL via 'i' and pass null (mysqli converts to 0) — to ensure DB NULL, we'll use explicit query when null.
        if ($bindIDPaket === null && $bindIDAlat === null) {
            // insert with NULL for both idpaket and idalat
            $q = $conn->prepare(
                "INSERT INTO booking (IDUser, BkgJenis, IDPaket, IDAlat, BkgAlamat, BkgTglMulai, BkgTglSelesai, BkgTotalHarga, BkgStatus, CreatedAt)
                 VALUES (?, ?, NULL, NULL, ?, ?, ?, ?, 'Pending', NOW())"
            );
            if (!$q) throw new Exception("Prepare2 failed: " . $conn->error);
            $q->bind_param("isssds", $userId, $bkgJenis, $bkgAlamat, $bkgTglMulai, $bkgTglSelesai, $bkgTotalHarga);
            $q->execute();
            $q->close();
        } elseif ($bindIDPaket === null) {
            // alat only
            $q = $conn->prepare(
                "INSERT INTO booking (IDUser, BkgJenis, IDPaket, IDAlat, BkgAlamat, BkgTglMulai, BkgTglSelesai, BkgTotalHarga, BkgStatus, CreatedAt)
                 VALUES (?, ?, NULL, ?, ?, ?, ?, ?, 'Pending', NOW())"
            );
            if (!$q) throw new Exception("Prepare3 failed: " . $conn->error);
            $q->bind_param("isissds", $userId, $bkgJenis, $bindIDAlat, $bkgAlamat, $bkgTglMulai, $bkgTglSelesai, $bkgTotalHarga);
            $q->execute();
            $q->close();
        } elseif ($bindIDAlat === null) {
            // paket only
            $q = $conn->prepare(
                "INSERT INTO booking (IDUser, BkgJenis, IDPaket, IDAlat, BkgAlamat, BkgTglMulai, BkgTglSelesai, BkgTotalHarga, BkgStatus, CreatedAt)
                 VALUES (?, ?, ?, NULL, ?, ?, ?, ?, 'Pending', NOW())"
            );
            if (!$q) throw new Exception("Prepare4 failed: " . $conn->error);
            $q->bind_param("iisssds", $userId, $bkgJenis, $bindIDPaket, $bkgAlamat, $bkgTglMulai, $bkgTglSelesai, $bkgTotalHarga);
            $q->execute();
            $q->close();
        } else {
            // both provided (unlikely) - insert both IDs
            $q = $conn->prepare(
                "INSERT INTO booking (IDUser, BkgJenis, IDPaket, IDAlat, BkgAlamat, BkgTglMulai, BkgTglSelesai, BkgTotalHarga, BkgStatus, CreatedAt)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())"
            );
            if (!$q) throw new Exception("Prepare5 failed: " . $conn->error);
            $q->bind_param("iisssdsd", $userId, $bkgJenis, $bindIDPaket, $bindIDAlat, $bkgAlamat, $bkgTglMulai, $bkgTglSelesai, $bkgTotalHarga);
            $q->execute();
            $q->close();
        }
    }

    $conn->commit();

    // kosongkan cart
    unset($_SESSION['cart']);
    $_SESSION['success_checkout'] = "Booking berhasil dibuat. Menunggu konfirmasi admin.";

    header('Location: checkout.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_checkout'] = "Terjadi kesalahan: " . $e->getMessage();
    header('Location: checkout.php');
    exit;
}