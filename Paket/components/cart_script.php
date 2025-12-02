<?php
include 'cart_modal.php';
?>
<script>
document.querySelectorAll('.qtyUpdate').forEach(el => {
    el.addEventListener('change', () => {
        let index = el.dataset.index;
        let qty = el.value;

        fetch('root/cart_update.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'index=' + index + '&qty=' + qty
        }).then(() => location.reload());
    });
});

document.querySelectorAll('.deleteItem').forEach(btn => {
    btn.addEventListener('click', () => {
        let index = btn.dataset.index;

        fetch('root/cart_delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'index=' + index
        }).then(() => location.reload());
    });
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('add-to-cart-popup')) {

        const id = e.target.getAttribute('data-id');
        const type = e.target.getAttribute('data-type');
        const name = e.target.getAttribute('data-name');
        const price = e.target.getAttribute('data-price');

        fetch('root/cart_add.php', {   // 🔥 FIXED PATH
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id=${id}&type=${type}&name=${name}&price=${price}&qty=1`
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                alert("Berhasil ditambahkan ke keranjang!");
                location.reload();
            } else {
                alert("Gagal menambah ke keranjang!");
            }
        });
    }
});
</script>