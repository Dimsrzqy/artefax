<?php
session_start();
include "../config/koneksi.php";

// =============================
// Ambil data booking dari session
// =============================
$IDBooking   = $_SESSION['checkout_booking_id'] ?? null;
$namaUser    = $_SESSION['user']['UserNama'] ?? "Pengguna";
$totalBayar  = $_SESSION['checkout_total'] ?? 0;
$jenisBayar  = $_SESSION['checkout_payment'] ?? "LUNAS"; // 'LUNAS' atau 'DP'

// Hitung DP jika perlu
if ($jenisBayar == "DP") {
    $totalBayar = $totalBayar * 0.50;
}

if (!$IDBooking) {
    die("Booking tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Pembayaran | Artefax</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f4f4f4;
}
.payment-card {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
}
#countdown {
    font-size: 1.2rem;
    font-weight: bold;
    color: #d9534f;
}
.payment-box {
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 15px;
    background: #fafafa;
    display: none;
}
.copy-btn {
    background: #007bff;
    color: #fff;
    padding: 5px 12px;
    border-radius: 6px;
}
.preview-img {
    width: 100%;
    max-height: 240px;
    object-fit: contain;
    margin-top: 10px;
    border-radius: 10px;
}
.btn-submit {
    width: 100%;
    background: #28a745;
    color: white;
    padding: 12px;
    border-radius: 12px;
    border: none;
}
.btn-batal {
    width: 100%;
    background: #dc3545;
    color: white;
    padding: 12px;
    border-radius: 12px;
    margin-top: 10px;
}
</style>
</head>
<body>

<div class="container mt-5 mb-5">
<div class="payment-card">

<h3>Pembayaran</h3>
<p class="text-muted">ID Booking: <strong><?= $IDBooking ?></strong></p>
<p class="text-muted">Halo <strong><?= $namaUser ?></strong>, silahkan selesaikan pembayaran.</p>

<hr>

<h4>Total Dibayar: <strong>Rp <?= number_format($totalBayar,0,',','.') ?></strong></h4>

<p class="mt-3">Batas Pembayaran:</p>
<div id="countdown">10:00</div>

<hr>

<!-- ===============================
     FORM KIRIM PEMBAYARAN
================================ -->
<form action="process_pembayaran.php" method="POST" enctype="multipart/form-data">

    <input type="hidden" name="IDBooking" value="<?= $IDBooking ?>">
    <input type="hidden" name="PbrKeterangan" value="<?= $jenisBayar ?>">
    <input type="hidden" name="PbrJumlah" value="<?= $totalBayar ?>">

    <label class="form-label">Pilih Metode Pembayaran</label>
    <select id="metode" name="PbrMetode" class="form-select" required>
        <option value="" disabled selected>-- Pilih --</option>
        <option value="BCA">Transfer Bank BCA</option>
        <option value="BRI">Transfer Bank BRI</option>
        <option value="GOPAY">Gopay</option>
        <option value="DANA">Dana</option>
    </select>

    <!-- Box BCA -->
    <div id="box-BCA" class="payment-box mt-3">
        <h5>Transfer BCA</h5>
        <p>No Rek: <strong id="rekBCA">1234567890</strong>
        <button type="button" class="copy-btn" onclick="copyText('rekBCA')">Copy</button></p>
    </div>

    <!-- Box BRI -->
    <div id="box-BRI" class="payment-box mt-3">
        <h5>Transfer BRI</h5>
        <p>No Rek: <strong id="rekBRI">987654321</strong>
        <button type="button" class="copy-btn" onclick="copyText('rekBRI')">Copy</button></p>
    </div>

    <!-- Box GOPAY -->
    <div id="box-GOPAY" class="payment-box mt-3">
        <h5>Gopay</h5>
        <p>No: <strong id="rekGOPAY">081234567890</strong>
        <button type="button" class="copy-btn" onclick="copyText('rekGOPAY')">Copy</button></p>
    </div>

    <!-- Box DANA -->
    <div id="box-DANA" class="payment-box mt-3">
        <h5>Dana</h5>
        <p>No: <strong id="rekDANA">081234567890</strong>
        <button type="button" class="copy-btn" onclick="copyText('rekDANA')">Copy</button></p>
    </div>


    <hr>

    <label class="form-label">Upload Bukti Pembayaran</label>
    <input type="file" name="PbrBukti" id="bukti" accept="image/*" class="form-control" required>
    <img id="preview" class="preview-img" style="display:none;">

    <button class="btn-submit mt-3" type="submit">Kirim Pembayaran</button>
</form>

<button class="btn-batal" onclick="batalkan()">Batalkan Pemesanan</button>

</div>
</div>

<script>
// =========== Countdown 10 menit ===========
let time = 600;
setInterval(() => {
    let m = Math.floor(time/60);
    let s = time%60;
    if (s < 10) s = '0'+s;
    document.getElementById("countdown").innerHTML = `${m}:${s}`;
    if (time <= 0) { window.location = "cancel.php"; }
    time--;
}, 1000);

// ========= Show metode pembayaran box ==========
document.getElementById("metode").addEventListener("change", function(){
    document.querySelectorAll(".payment-box").forEach(box => box.style.display = "none");
    let id = this.value;
    document.getElementById("box-"+id).style.display = "block";
});

// ========== Preview bukti ==========
document.getElementById("bukti").addEventListener("change", function(){
    const reader = new FileReader();
    reader.onload = e => {
        let img = document.getElementById("preview");
        img.style.display = "block";
        img.src = e.target.result;
    }
    reader.readAsDataURL(this.files[0]);
});

// ========= Copy ===========
function copyText(id){
    navigator.clipboard.writeText(document.getElementById(id).innerText);
    alert("Tersalin!");
}

// ========= Batalkan ==========
function batalkan(){
    if (confirm("Yakin ingin batalkan pesanan?")) {
        window.location = "cancel.php";
    }
}
</script>

</body>
</html>
