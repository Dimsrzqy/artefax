<?php
if (!isset($_SESSION)) session_start();
$cart = $_SESSION['cart'] ?? [];
?>

<!-- MODAL KERANJANG - SHOPEE STYLE -->
<div class="modal fade" id="cartModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-shopping-cart"></i> Keranjang Belanja
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body" id="cartModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Memuat keranjang...</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <div class="w-100 d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Total:</strong>
                        <span id="cartGrandTotal" class="text-primary fs-5">Rp 0</span>
                    </div>
                    <div>
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <a href="checkout.php" id="checkoutBtn" class="btn btn-success">
                            <i class="fa fa-credit-card"></i> Checkout
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// ============================================
// SHOPEE-STYLE CART - SILENT MODE
// No popup, no notification, auto refresh
// ============================================

let updateTimeout = null;

// ✅ LOAD CART
function loadCartModal() {
    const cartBody = document.getElementById('cartModalBody');
    const grandTotalEl = document.getElementById('cartGrandTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    cartBody.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Memuat keranjang...</p>
        </div>
    `;
    
    fetch('components/get_cart.php')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const cart = data.cart;
                const grandTotal = data.grand_total;
                
                // Update counter navbar
                const cartCounter = document.querySelector('.position-absolute.bg-secondary');
                if (cartCounter) {
                    cartCounter.textContent = data.cart_count;
                }
                
                // Cart kosong
                if (cart.length === 0) {
                    cartBody.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fa fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5>Keranjang Kosong</h5>
                            <p class="text-muted">Belum ada produk di keranjang</p>
                            <a href="shop.php" class="btn btn-primary mt-2">
                                <i class="fa fa-shopping-bag"></i> Belanja Sekarang
                            </a>
                        </div>
                    `;
                    grandTotalEl.textContent = 'Rp 0';
                    checkoutBtn.style.display = 'none';
                    return;
                }
                
                // Build table
                let html = `
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th width="120">Harga</th>
                                <th width="140">Qty</th>
                                <th width="120">Total</th>
                                <th width="60">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                cart.forEach((item, index) => {
                    const subtotal = item.qty * item.price;
                    html += `
                        <tr data-row-index="${index}">
                            <td>
                                <strong>${escapeHtml(item.name)}</strong><br>
                                <small class="text-muted">${escapeHtml(item.type)}</small>
                            </td>
                            <td>Rp ${formatNumber(item.price)}</td>
                            <td>
                                <div class="input-group input-group-sm" style="max-width: 130px;">
                                    <button class="btn btn-outline-secondary qtyMinus" data-index="${index}" type="button">
                                        <i class="fa fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           class="form-control text-center qtyInput" 
                                           value="${item.qty}" 
                                           min="1"
                                           data-index="${index}"
                                           style="max-width: 50px;">
                                    <button class="btn btn-outline-secondary qtyPlus" data-index="${index}" type="button">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </td>
                            <td><strong>Rp ${formatNumber(subtotal)}</strong></td>
                            <td>
                                <button class="btn btn-danger btn-sm deleteItem" 
                                        data-index="${index}"
                                        title="Hapus">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                html += `</tbody></table>`;
                
                cartBody.innerHTML = html;
                grandTotalEl.textContent = 'Rp ' + formatNumber(grandTotal);
                checkoutBtn.style.display = 'inline-block';
                
                attachCartEventListeners();
                
            } else {
                cartBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i> 
                        ${data.message || 'Gagal memuat keranjang'}
                    </div>
                `;
            }
        })
        .catch(err => {
            console.error('Error:', err);
            cartBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-triangle"></i> 
                    Gagal memuat keranjang.
                </div>
            `;
        });
}

// ✅ ATTACH EVENT LISTENERS
function attachCartEventListeners() {
    
    // ========================================
    // UPDATE QTY - SILENT MODE
    // ========================================
    
    // Button Minus
    document.querySelectorAll('.qtyMinus').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = this.dataset.index;
            const input = document.querySelector(`.qtyInput[data-index="${index}"]`);
            let qty = parseInt(input.value);
            
            if (qty > 1) {
                qty--;
                input.value = qty;
                updateQuantity(index, qty);
            }
        });
    });
    
    // Button Plus
    document.querySelectorAll('.qtyPlus').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = this.dataset.index;
            const input = document.querySelector(`.qtyInput[data-index="${index}"]`);
            let qty = parseInt(input.value);
            
            qty++;
            input.value = qty;
            updateQuantity(index, qty);
        });
    });
    
    // Input manual
    document.querySelectorAll('.qtyInput').forEach(input => {
        input.addEventListener('change', function() {
            const index = this.dataset.index;
            let qty = parseInt(this.value);
            
            if (qty < 1 || isNaN(qty)) {
                qty = 1;
                this.value = 1;
            }
            
            updateQuantity(index, qty);
        });
    });
    
    // ========================================
    // DELETE - SILENT MODE (No Confirm)
    // ========================================
    
    document.querySelectorAll('.deleteItem').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = this.dataset.index;
            const row = this.closest('tr');
            
            // Animasi fade out
            row.style.transition = 'opacity 0.2s ease';
            row.style.opacity = '0.5';
            
            // Show loading
            this.disabled = true;
            this.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            
            // Delete langsung
            fetch('root/cart_delete.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'index=' + index
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update counter navbar
                    const cartCounter = document.querySelector('.position-absolute.bg-secondary');
                    if (cartCounter) {
                        cartCounter.textContent = data.cart_count;
                    }
                    
                    // Reload cart (smooth)
                    setTimeout(() => {
                        loadCartModal();
                    }, 200);
                } else {
                    // Kembalikan jika gagal
                    row.style.opacity = '1';
                    this.disabled = false;
                    this.innerHTML = '<i class="fa fa-trash"></i>';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                row.style.opacity = '1';
                this.disabled = false;
                this.innerHTML = '<i class="fa fa-trash"></i>';
            });
        });
    });
}

// ✅ UPDATE QUANTITY FUNCTION (Debounced)
function updateQuantity(index, qty) {
    // Clear previous timeout
    if (updateTimeout) {
        clearTimeout(updateTimeout);
    }
    
    // Debounce 500ms
    updateTimeout = setTimeout(() => {
        fetch('root/cart_update.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'index=' + index + '&qty=' + qty
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // Reload cart (smooth, no flash)
                loadCartModal();
            }
        })
        .catch(err => {
            console.error('Error:', err);
        });
    }, 500); // Wait 500ms after last change
}

// ✅ LOAD CART saat modal dibuka
document.getElementById('cartModal').addEventListener('show.bs.modal', function() {
    loadCartModal();
});

// Helper functions
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
</script>