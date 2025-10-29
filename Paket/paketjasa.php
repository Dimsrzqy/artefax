<?php
session_start();

// Tangani logout jika diperlukan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../index.php"); // redirect setelah logout
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Layanan - Artefax</title>
    <link href="../assets/img/logo Artefax1.png" rel="icon" />
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="../assets/css/main.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        .header {
            background-color: transparent; /* Menghilangkan background putih */
        }
        .service-details {
            padding: 60px 0;
        }
        .product {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s;
            margin-bottom: 30px;
        }
        .product:hover {
            transform: scale(1.05);
        }
        .product img {
            width: 100%;
            height: auto;
        }
        .product-wrap {
            padding: 20px;
            text-align: center;
        }
        .product h3 {
            font-size: 1.5rem;
            margin: 10px 0;
        }
        .product .price {
            font-size: 1.25rem;
            color: #ff5722;
            margin: 10px 0;
        }
        .add-to-cart {
            background-color: #ff5722;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .add-to-cart:hover {
            background-color: #e64a19;
        }
        .menu-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        .active {
            font-weight: bold; /* Menandai menu aktif */
            color: #ff5722; /* Warna untuk menu aktif */
        }
    </style>
</head>

<body class="service-details-page">
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="../index.php" class="logo d-flex align-items-center">
                <img src="../assets/img/logo Artefax.png" alt="Logo Artefax" style="max-height: 70px" />
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#services" class="active">Layanan</a></li> <!-- Menandai Layanan sebagai aktif -->
                    <li><a href="#portfolio">Portfolio</a></li>
                    <li><a href="#team">Team</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="view/profil.php">Profile</a></li> <!-- Tambahkan menu Profile -->
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="page-title light-background">
            <div class="container">
                <h1>Paket Layanan Kami</h1>
                <nav class="breadcrumbs">
                    <ol>
                        <li><a href="../index.php">Home</a></li>
                        <li class="current">Paket Layanan Event Organizer</li>
                    </ol>
                </nav>
            </div>
        </div>

        <section id="service-details" class="service-details section">
            <div class="container">
                <div class="menu-list">
                    <!-- Paket Layanan sebagai Produk -->
                    <div class="product">
                        <div class="product-wrap">
                            <div class="img">
                                <img src="../assets/img/services/EO.jpg" alt="Event Organizer - Basic" />
                            </div>
                            <h3>Event Organizer - Basic</h3>
                            <p class="category">Event Organizer</p>
                            <div class="price">Rp 5.000.000</div>
                            <button class="add-to-cart" data-name="Event Organizer - Basic" data-price="5000000" data-category="Event Organizer">Tambah ke Keranjang</button>
                        </div>
                    </div>

                    <div class="product">
                        <div class="product-wrap">
                            <div class="img">
                                <img src="../assets/img/services/EO.jpg" alt="Event Organizer - Premium" />
                            </div>
                            <h3>Event Organizer - Premium</h3>
                            <p class="category">Event Organizer</p>
                            <div class="price">Rp 10.000.000</div>
                            <button class="add-to-cart" data-name="Event Organizer - Premium" data-price="10000000" data-category="Event Organizer">Tambah ke Keranjang</button>
                        </div>
                    </div>

                    <div class="product">
                        <div class="product-wrap">
                            <div class="img">
                                <img src="../assets/img/services/EO.jpg" alt="Event Organizer - Full Production" />
                            </div>
                            <h3>Event Organizer - Full Production</h3>
                            <p class="category">Event Organizer</p>
                            <div class="price">Rp 15.000.000</div>
                            <button class="add-to-cart" data-name="Event Organizer - Full Production" data-price="15000000" data-category="Event Organizer">Tambah ke Keranjang</button>
                        </div>
                    </div>

                    <div class="product">
                        <div class="product-wrap">
                            <div class="img">
                                <img src="../assets/img/services/EO.jpg" alt="Wedding Organizer - Foto & Video" />
                            </div>
                            <h3>Wedding Organizer - Foto & Video</h3>
                            <p class="category">Wedding Organizer</p>
                            <div class="price">Rp 8.000.000</div>
                            <button class="add-to-cart" data-name="Wedding Organizer - Foto & Video" data-price="8000000" data-category="Wedding Organizer">Tambah ke Keranjang</button>
                        </div>
                    </div>

                    <div class="product">
                        <div class="product-wrap">
                            <div class="img">
                                <img src="../assets/img/services/EO.jpg" alt="Wedding Organizer - Dekorasi Lengkap" />
                            </div>
                            <h3>Wedding Organizer - Dekorasi Lengkap</h3>
                            <p class="category">Wedding Organizer</p>
                            <div class="price">Rp 12.000.000</div>
                            <button class="add-to-cart" data-name="Wedding Organizer - Dekorasi Lengkap" data-price="12000000" data-category="Wedding Organizer">Tambah ke Keranjang</button>
                        </div>
                    </div>

                    <div class="product">
                        <div class="product-wrap">
                            <div class="img">
                                <img src="../assets/img/services/EO.jpg" alt="Graduation - Paket 1" />
                            </div>
                            <h3>Graduation - Paket 1</h3>
                            <p class="category">Graduation</p>
                            <div class="price">Rp 3.000.000</div>
                            <button class="add-to-cart" data-name="Graduation - Paket 1" data-price="3000000" data-category="Graduation">Tambah ke Keranjang</button>
                        </div>
                    </div>

                    <div class="product">
                        <div class="product-wrap">
                            <div class="img">
                                <img src="../assets/img/services/EO.jpg" alt="Live Streaming - Paket 2" />
                            </div>
                            <h3>Live Streaming - Paket 2</h3>
                            <p class="category">Live Streaming</p>
                            <div class="price">Rp 4.000.000</div>
                            <button class="add-to-cart" data-name="Live Streaming - Paket 2" data-price="4000000" data-category="Live Streaming">Tambah ke Keranjang</button>
                        </div>
                    </div>

                    <div class="product">
                        <div class="product-wrap">
                            <div class="img">
                                <img src="../assets/img/services/EO.jpg" alt="Special Effect - Sparkular" />
                            </div>
                            <h3>Special Effect - Sparkular</h3>
                            <p class="category">Special Effect</p>
                            <div class="price">Rp 2.000.000</div>
                            <button class="add-to-cart" data-name="Special Effect - Sparkular" data-price="2000000" data-category="Special Effect">Tambah ke Keranjang</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer id="footer" class="footer position-relative light-background">
        <div class="container">
            <div class="row gy-5">
                <div class="col-lg-4">
                    <div class="footer-content">
                        <a href="index.html" class="logo d-flex align-items-center mb-4">
                            <span class="sitename">Artefax</span>
                        </a>
                        <p class="mb-4">Mewujudkan acara impian Anda dengan konsep kreatif, profesional, dan penuh kesan.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
