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
</script>

