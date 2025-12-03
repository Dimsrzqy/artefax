<?php

?>
<div class="popup-overlay" id="detailPopupRefund">
    <div class="popup-content" id="draggablePopupRefund">
        <div class="popup-header" id="dragHandleRefund">
            <span id="popupTitleRefund">Detail Pengajuan Refund</span>
            <span class="close-popup" onclick="closeRefundPopup()">&times;</span>
        </div>
        <div class="popup-body" id="popupBodyRefund">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.popup-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.8);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
    .popup-content {
        background: #fff;
        width: 80%;
        max-width: 900px;
        max-height: 92vh;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 20px 70px rgba(0,0,0,0.4);
        position: relative;
    }
    .popup-header {
        background: linear-gradient(135deg, #4361ee, #3f37c9);
        color: white;
        padding: 18px 25px;
        font-size: 1.5em;
        font-weight: bold;
        cursor: grab;
        user-select: none;
        position: relative;
    }
    .popup-header:active { cursor: grabbing; }
    .close-popup {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 34px;
        cursor: pointer;
        opacity: 0.9;
    }
    .close-popup:hover { opacity: 1; }
    .popup-body {
        padding: 25px;
        max-height: 70vh;
        overflow-y: auto;
    }
    .section {
        margin-bottom: 25px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 12px;
        border-left: 6px solid #4361ee;
    }
    .section h3 {
        margin: 0 0 18px 0;
        color: #2d3436;
        font-size: 1.35em;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    table.info-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 1.02em;
    }
    table.info-table td {
        padding: 11px 0;
        border-bottom: 1px dashed #ddd;
    }
    table.info-table td:first-child {
        width: 38%;
        font-weight: 600;
        color: #444;
    }
    .badge {
        padding: 6px 16px;
        border-radius: 50px;
        font-weight: bold;
        font-size: 0.9em;
    }
    .badge-lunas { background: #d4edda; color: #155724; }
    .badge-dp { background: #fff3cd; color: #856404; }
    .badge-pending { background: #fff3cd; color: #b8860b; }
    .item-list {
        margin: 15px 0;
        padding-left: 5px;
    }
    .item-list li {
        padding: 8px 0;
        color: #2d3436;
        font-size: 1.05em;
    }
    .btn-bukti {
        background: #4361ee;
        color: white;
        border: none;
        padding: 11px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-bukti:hover {
        background: #3f37c9;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(67,97,238,0.4);
    }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
</style>
<script>
let isDraggingRefund = false, startX_r, startY_r, offsetX_r = 0, offsetY_r = 0;
const popupRefundEl = document.getElementById('draggablePopupRefund');
const headerRefundEl = document.getElementById('dragHandleRefund');

headerRefundEl.addEventListener('mousedown', e => {
    if (e.target.classList.contains('close-popup')) return;
    isDraggingRefund = true;
    startX_r = e.clientX - offsetX_r;
    startY_r = e.clientY - offsetY_r;
    headerRefundEl.style.cursor = 'grabbing';
});
document.addEventListener('mousemove', e => {
    if (!isDraggingRefund) return;
    e.preventDefault();
    offsetX_r = e.clientX - startX_r;
    offsetY_r = e.clientY - startY_r;
    popupRefundEl.style.transform = `translate(${offsetX_r}px, ${offsetY_r}px)`;
});
document.addEventListener('mouseup', () => {
    isDraggingRefund = false;
    headerRefundEl.style.cursor = 'grab';
});

function openRefundPopup(data) {
    // Reset posisi drag
    popupRefundEl.style.transform = 'translate(0,0)';
    offsetX_r = offsetY_r = 0;

    // Status badge
    const status = (data.RefundStatus || 'Pending').trim();
    let badgeClass = 'badge-pending';
    let statusText = status;
    if (status === 'Disetujui') { badgeClass = 'badge-lunas'; statusText = 'Disetujui'; }
    if (status === 'Ditolak') { badgeClass = 'badge'; badgeClass += ' bg-danger text-white'; statusText = 'Ditolak'; }

    // Format tanggal
    const waktuRefund = new Date(data.RefundWaktu).toLocaleString('id-ID', { 
        dateStyle: 'full', 
        timeStyle: 'short' 
    });

    const tglMulai = data.BkgTglMulai ? new Date(data.BkgTglMulai).toLocaleDateString('id-ID') : '-';

    // Daftar pesanan
    const items = data.DaftarPesanan 
        ? data.DaftarPesanan.split(', ').map(i => `<li>${i.trim()}</li>`).join('')
        : '<li style="color:#888"><em>Tidak ada item</em></li>';

    const html = `
        <div class="section">
            <h3><span class="badge badge-warning">Menunggu Konfirmasi Refund</span> #REFUND${String(data.IDRefund).padStart(6, '0')}</h3>
            
            <div style="margin: 20px 0; padding: 18px; background: #fff3cd; border-radius: 10px; border-left: 5px solid #ffc107;">
                <strong>Alasan Pengajuan Refund:</strong><br>
                <p style="margin: 10px 0 0; font-style: italic; color: #555;">"${data.RefundAlasan || '-'}"</p>
            </div>

            <table class="info-table">
                <tr><td>Jumlah Refund Diajukan</td><td><strong style="font-size:1.3em; color:#e74c3c;">Rp ${Number(data.RefundJumlah).toLocaleString('id-ID')}</strong></td></tr>
                <tr><td>Waktu Pengajuan</td><td>${waktuRefund}</td></tr>
                <tr><td>Status Refund</td><td><span class="badge ${badgeClass}">${statusText}</span></td></tr>
            </table>
        </div>

        <div class="section">
            <h3>Informasi Booking Terkait</h3>
            <table class="info-table">
                <tr><td>Booking ID</td><td><strong>#BOOK${String(data.IDBooking).padStart(6, '0')}</strong></td></tr>
                <tr><td>Nama Pelanggan</td><td>${data.UserNama || '-'}</td></tr>
                <tr><td>Tanggal Mulai Sewa</td><td>${tglMulai}</td></tr>
                <tr><td>Total Harga Booking</td><td>Rp ${Number(data.BkgTotalHarga || 0).toLocaleString('id-ID')}</td></tr>
                <tr><td>Sudah Dibayar</td><td>Rp ${Number(data.JumlahBayar || 0).toLocaleString('id-ID')} <small>(${data.PbrMetode || 'Tidak diketahui'})</small></td></tr>
                <tr><td>Status Booking</td><td><span class="badge ${data.BkgStatus === 'Selesai' ? 'badge-lunas' : 'badge-pending'}">${data.BkgStatus || '-'}</span></td></tr>
            </table>
        </div>

        <div class="section">
            <h3>Daftar Pesanan</h3>
            <ul class="item-list">${items}</ul>
        </div>

        <div style="margin-top: 30px; text-align: center; padding: 20px; background: #f8f9fa; border-radius: 12px;">
            <button class="btn-bukti" style="background:#27ae60; margin:0 10px;" onclick="konfirmasiRefund(${data.IDRefund}, 'Disetujui')">
                Setujui Refund
            </button>
            <button class="btn-bukti" style="background:#e74c3c; margin:0 10px;" onclick="konfirmasiRefund(${data.IDRefund}, 'Ditolak')">
                Tolak Refund
            </button>
        </div>
    `;

    document.getElementById('popupTitleRefund').textContent = `Detail Refund #REFUND${String(data.IDRefund).padStart(6, '0')}`;
    document.getElementById('popupBodyRefund').innerHTML = html;
    document.getElementById('detailPopupRefund').style.display = 'flex';
}

function closeRefundPopup() {
    document.getElementById('detailPopupRefund').style.display = 'none';
}

// Tutup dengan klik luar atau ESC
document.getElementById('detailPopupRefund')?.addEventListener('click', e => {
    if (e.target === document.getElementById('detailPopupRefund')) closeRefundPopup();
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeRefundPopup();
});

// Fungsi konfirmasi (kamu bisa sesuaikan dengan AJAX ke backend)
function konfirmasiRefund(idRefund, status) {
    if (!confirm(`Apakah Anda yakin ingin ${status === 'Disetujui' ? 'menyetujui' : 'menolak'} refund ini?`)) return;

    fetch('proses_refund.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_status&id=${idRefund}&status=${status}`
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(`Refund telah ${status.toLowerCase()}!`);
            closeRefundPopup();
            location.reload(); // atau refresh tabel saja
        } else {
            alert('Gagal: ' + res.message);
        }
    })
    .catch(() => alert('Terjadi kesalahan jaringan'));
}
</script>
