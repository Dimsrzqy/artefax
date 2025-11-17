<?php
// cart_add.php
session_start();
include __DIR__ . '/../config/koneksi.php';

// koneksi
$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// input
$id = $_POST['id'] ?? '';
$tipe = $_POST['tipe'] ?? '';
$qty = isset($_POST['qty']) && is_numeric($_POST['qty']) ? (int)$_POST['qty'] : 1;

if (!$id || !$tipe) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// ambil detail produk dari DB (sesuai tipe)
if ($tipe === 'alat') {
    $stmt = $conn->prepare("SELECT IDAlat AS id, AlatNama AS name, AlatHarga AS price, AlatDirGbr AS image FROM alat WHERE IDAlat = ?");
} else {
    $stmt = $conn->prepare("SELECT IDPaket AS id, PaketNama AS name, PaketHarga AS price, PaketDirGbr AS image FROM paketjasa WHERE IDPaket = ?");
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
    exit;
}

// normalisasi data cart item
$item = [
    'id' => $product['id'],
    'tipe' => $tipe,
    'name' => $product['name'],
    'price' => (float)$product['price'],
    'image' => $product['image'],
    'qty' => $qty
];

// inisialisasi session cart jika belum ada
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];

// cek apakah item sudah ada di cart (sama tipe+id) -> tambahkan qty
$found = false;
foreach ($_SESSION['cart'] as &$c) {
    if ($c['id'] == $item['id'] && $c['tipe'] === $item['tipe']) {
        $c['qty'] += $item['qty'];
        $found = true;
        break;
    }
}
if (!$found) {
    $_SESSION['cart'][] = $item;
}

// kirim response sukses + jumlah item di cart
$cart_count = count($_SESSION['cart']);
echo json_encode(['success' => true, 'cart_count' => $cart_count]);
exit;