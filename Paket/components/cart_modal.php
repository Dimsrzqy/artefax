<?php
if (!isset($_SESSION)) session_start();
$cart = $_SESSION['cart'] ?? [];
?>

<!-- MODAL KERANJANG -->
<div class="modal fade" id="cartModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title">Keranjang Belanja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <?php if (empty($cart)) : ?>
                    <p class="text-center text-muted">Keranjang masih kosong.</p>

                <?php else : ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php
                        $grand = 0;
                        foreach ($cart as $i => $c) :
                            $subtotal = $c['qty'] * $c['price'];
                            $grand += $subtotal;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($c['name']) ?></td>
                                <td>Rp <?= number_format($c['price'], 0, ',', '.') ?></td>

                                <td width="100">
                                    <input type="number"
                                           min="1"
                                           class="form-control qtyUpdate"
                                           value="<?= $c['qty'] ?>"
                                           data-index="<?= $i ?>">
                                </td>

                                <td>Rp <?= number_format($subtotal, 0, ',', '.') ?></td>

                                <td>
                                    <button class="btn btn-danger btn-sm deleteItem"
                                            data-index="<?= $i ?>">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        </tbody>
                    </table>

                    <h5 class="text-end">Total: <b>Rp <?= number_format($grand, 0, ',', '.') ?></b></h5>

                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>

                <?php if (!empty($cart)) : ?>
                    <a href="checkout.php" class="btn btn-success">Checkout</a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
