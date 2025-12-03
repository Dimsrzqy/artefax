<?php
// Tidak perlu include cart_modal.php lagi karena sudah di file utama
?>
<script>
// ✅ HANDLER untuk tombol add to cart dari popup/detail
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('add-to-cart-popup') || 
        e.target.closest('.add-to-cart-popup')) {
        
        const btn = e.target.classList.contains('add-to-cart-popup') 
                    ? e.target 
                    : e.target.closest('.add-to-cart-popup');
        
        const id = btn.getAttribute('data-id');
        const type = btn.getAttribute('data-type');
        const name = btn.getAttribute('data-name');
        const price = btn.getAttribute('data-price');
        
        // Disable button saat proses
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Loading...';
        
        fetch('root/cart_add.php', {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id=${id}&type=${type}&name=${encodeURIComponent(name)}&price=${price}&qty=1`
        })
        .then(res => res.json())
        .then(data => {
            // Enable button kembali
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            
            if (data.status === "success") {
                // ✅ Update cart counter di navbar (TANPA RELOAD)
                const cartCounter = document.querySelector('.position-absolute.bg-secondary');
                if (cartCounter) {
                    cartCounter.textContent = data.cart_count;
                }
                
                // ✅ Show success message
                alert("✅ Produk berhasil ditambahkan ke keranjang!");
                
                // ✅ Jika modal cart sedang terbuka, reload isinya
                const cartModal = document.getElementById('cartModal');
                if (cartModal && cartModal.classList.contains('show')) {
                    if (typeof loadCartModal === 'function') {
                        loadCartModal();
                    }
                }
                
                // ✅ Tutup modal produk jika ada
                const productModal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
                if (productModal) {
                    productModal.hide();
                }
                
            } else {
                alert("❌ Gagal menambah ke keranjang: " + (data.message || ''));
            }
        })
        .catch(err => {
            console.error('Error:', err);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            alert("❌ Gagal menghubungi server!");
        });
    }
});

// ✅ OPTIONAL: Auto reload cart jika user klik icon cart
document.addEventListener('DOMContentLoaded', function() {
    const cartIcon = document.querySelector('[data-bs-target="#cartModal"]');
    if (cartIcon) {
        cartIcon.addEventListener('click', function() {
            // Delay sedikit agar modal sudah terbuka
            setTimeout(() => {
                if (typeof loadCartModal === 'function') {
                    loadCartModal();
                }
            }, 100);
        });
    }
});
</script>