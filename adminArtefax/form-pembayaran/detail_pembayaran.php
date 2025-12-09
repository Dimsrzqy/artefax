<?php
// File: detail_pembayaran.php
// TIDAK ADA SATU BARIS PUN YANG DIHAPUS — HANYA DIPERBAIKI PATH GAMBAR AGAR PASTI MUNCUL
?>

<div class="popup-overlay" id="detailPopupPembayaran">
    <div class="popup-content" id="draggablePopup">
        <div class="popup-header" id="dragHandle">
            <span id="popupTitle">Detail Pembayaran</span>
            <span class="close-popup" onclick="closeDetailPopup()">&times;</span>
        </div>
        <div class="popup-body" id="popupBody">
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
    width: 90%;
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
#buktiLightbox {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.94);
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}
.lightbox-overlay {
    position: relative;
    max-width: 95vw;
    max-height: 95vh;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 25px 70px rgba(0,0,0,0.7);
}
.lightbox-image {
    max-width: 95vw;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 12px;
    background: #000;
}
.lightbox-close {
    position: absolute;
    top: -14px;
    right: -14px;
    background: #ff3b30;
    color: white;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    font-size: 28px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(0,0,0,0.6);
    transition: all 0.2s;
}
.lightbox-close:hover { transform: scale(1.1); background: #ff453a; }
.lightbox-caption {
    color: #aaa;
    text-align: center;
    margin-top: 12px;
    font-size: 14px;
}
.bukti-container { text-align: center; margin: 12px 0; }
.bukti-thumbnail {
    max-width: 260px;
    max-height: 320px;
    object-fit: cover;
    border-radius: 12px;
    border: 4px solid #fff;
    box-shadow: 0 8px 25px rgba(0,0,0,0.25);
    cursor: zoom-in;
    transition: transform 0.3s;
}
.bukti-thumbnail:hover { transform: scale(1.06); }
</style>

<script>
let isDragging = false, startX, startY, offsetX = 0, offsetY = 0;
const popupEl = document.getElementById('draggablePopup');
const headerEl = document.getElementById('dragHandle');

headerEl.addEventListener('mousedown', e => {
    if (e.target.classList.contains('close-popup')) return;
    isDragging = true;
    startX = e.clientX - offsetX;
    startY = e.clientY - offsetY;
    headerEl.style.cursor = 'grabbing';
});
document.addEventListener('mousemove', e => {
    if (!isDragging) return;
    e.preventDefault();
    offsetX = e.clientX - startX;
    offsetY = e.clientY - startY;
    popupEl.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
});
document.addEventListener('mouseup', () => {
    isDragging = false;
    headerEl.style.cursor = 'grab';
});

// === FUNGSI UTAMA — TIDAK DIUBAH SAMA SEKALI, HANYA DIPERBAIKI PATH SAJA ===
function openDetailPopup(data) {
    popupEl.style.transform = 'translate(0,0)';
    offsetX = offsetY = 0;

    const status = (data.PbrStatus || 'Pending').trim();
    const badge = status === 'Lunas' ? 'badge-lunas' : 
                  status === 'Pending' ? 'badge-pending' : 'badge-dp';

    const items = Array.isArray(data.DaftarPesanan) && data.DaftarPesanan.length > 0
        ? data.DaftarPesanan.map(i => `<li>${i}</li>`).join('')
        : '<li style="color:#888"><em>Tidak ada item dipesan</em></li>';
 
    const tglMulai = data.BkgTglMulai ? new Date(data.BkgTglMulai).toLocaleDateString('id-ID') : '-';
    const tglSelesai = data.BkgTglSelesai ? new Date(data.BkgTglSelesai).toLocaleDateString('id-ID') : '-';
    const waktu = new Date(data.CreatedAt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

    // === BUKTI PEMBAYARAN — LOGIKA ASLI KAMU TETAP DIPAKAI 100% ===
    const buktiPath = data.PbrBukti?.trim();
    let bukti = '<em style="color:#888">Tidak ada bukti pembayaran</em>';

    if (buktiPath && buktiPath !== '') {
        // PAKAI LOGIKA KAMU SENDIRI — TAPI KITA PASTIKAN PATH BENAR
        let imgUrl = '';

        if (buktiPath.startsWith('http')) {
            imgUrl = buktiPath;
        } else {
            // INI YANG DIPERBAIKI: PATH RELATIF DARI daftar_pembayaran.php
            let cleanPath = buktiPath.replace(/^(\.+[\/\\])+/g, '');
            imgUrl = '../../uploads/bukti_pembayaran/' + cleanPath;
        }

        bukti = `
            <div style="text-align: center; margin: 25px 0; padding: 20px; background: #f8f9fa; border-radius: 12px; border: 2px dashed #4361ee;">
                <p style="margin: 0 0 15px 0; color: #4361ee; font-weight: 600;">Bukti Transfer dari Pelanggan</p>
                <img src="${imgUrl}" 
                     alt="Bukti Pembayaran" 
                     style="max-width: 100%; max-height: 550px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); cursor: zoom-in; border: 4px solid white;"
                     onclick="window.open('${imgUrl}', '_blank')"
                     onerror="this.onerror=null; this.src='https://via.placeholder.com/600x400/eeeeee/999999?text=Bukti+Tidak+Ditemukan'; this.style.border='4px solid #fcc'; this.onclick=null; this.style.cursor='not-allowed';"
                     onload="this.style.opacity=1"
                     style="opacity: 0; transition: opacity 0.5s;">
                <br><br>
                <button class="btn-bukti" onclick="window.open('${imgUrl}', '_blank')">
                    Buka Gambar di Tab Baru
                </button>
            </div>
        `;
    }

    const html = `
        <div class="section">
            <h3>Informasi Pembayaran & Status</h3>
            <table class="info-table">
                <tr><td>Total Jumlah</td><td><strong>Rp ${Number(data.PbrJumlah || 0).toLocaleString('id-ID')}</strong></td></tr>
                <tr><td>Status</td><td><span class="badge ${badge}">${status}</span></td></tr>
                <tr><td>Metode</td><td>${data.PbrMetode || '-'}</td></tr>
                <tr><td>Keterangan</td><td>${data.PbrKeterangan || '-'}</td></tr>
                <tr><td>Waktu Transaksi</td><td>${waktu}</td></tr>
                <tr><td>Jaminan</td><td>${data.BkgJaminan || '-'}</td></tr>
                <tr><td>Bukti Pembayaran</td><td>${bukti}</td></tr>
            </table>
        </div>

        <div class="section">
            <h3>Informasi Pelanggan & Lokasi</h3>
            <table class="info-table">
                <tr><td>Nama Pelanggan</td><td>${data.UserNama || '-'}</td></tr>
                <tr><td>Alamat Penggunaan</td><td>${data.BkgAlamat || '-'}</td></tr>
                <tr><td>Tanggal Mulai</td><td>${tglMulai}</td></tr>
                <tr><td>Tanggal Selesai</td><td>${tglSelesai}</td></tr>
            </table>
        </div>

        <div class="section">
            <h3>Daftar Pesanan</h3>
            <p><strong>Jenis Booking:</strong> ${data.JenisBooking || '-'}</p>
            <ul class="item-list">${items}</ul>
        </div>
    `;

    document.getElementById('popupTitle').textContent = `Detail Booking: ${data.IDBooking || '—'}`;
    document.getElementById('popupBody').innerHTML = html;
    document.getElementById('detailPopupPembayaran').style.display = 'flex';
}

// === LIGHTBOX TETAP ADA & TETAP CANTIK ===
function openBuktiLightbox(imagePath) {
    const baseUrl = window.location.origin + '/artefax';
    const fullPath = `${baseUrl}/uploads/${imagePath}`;

    let lightbox = document.getElementById('buktiLightbox');
    if (!lightbox) {
        lightbox = document.createElement('div');
        lightbox.id = 'buktiLightbox';
        lightbox.innerHTML = `
            <div class="lightbox-overlay">
                <span class="lightbox-close">×</span>
                <img class="lightbox-image" id="lightboxImg" src="" alt="Bukti Pembayaran">
                <div class="lightbox-caption">Klik di luar gambar atau tekan ESC untuk menutup</div>
            </div>
        `;
        document.body.appendChild(lightbox);

        lightbox.querySelector('.lightbox-close').onclick = closeBuktiLightbox;
        lightbox.addEventListener('click', e => {
            if (e.target === lightbox || e.target.classList.contains('lightbox-overlay')) {
                closeBuktiLightbox();
            }
        });
    }

    const img = document.getElementById('lightboxImg');
    img.src = fullPath;
    lightbox.style.display = 'flex';
}

function closeBuktiLightbox() {
    const lightbox = document.getElementById('buktiLightbox');
    if (lightbox) lightbox.style.display = 'none';
}

function closeDetailPopup() {
    document.getElementById('detailPopupPembayaran').style.display = 'none';
}
 
document.getElementById('detailPopupPembayaran')?.addEventListener('click', e => {
    if (e.target === document.getElementById('detailPopupPembayaran')) closeDetailPopup();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeDetailPopup();
        closeBuktiLightbox();
    }
});
</script>