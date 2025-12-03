<?php
ob_start();
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once "config/koneksi.php";
require_once "class/users.php";

$database = new Database();
$conn = $database->getConnection();
$user = new User($conn);

$message = "";

// === LOGOUT ===
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: index.php?logout=success");
    exit;
}

// === PROSES LOGIN DARI MODAL ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login_submit'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $message = "Email dan password wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
    } else {
        $user->UserEmail = $email;
        $user->UserPassword = $password;
        $login = $user->login();

        if ($login) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => $login['IDUser'],
                'nama'  => $login['UserNama'],
                'email' => $login['UserEmail'],
                'role'  => $login['UserRole']
            ];
            // PERBAIKAN: Arahkan langsung ke halaman Services
            header("Location: Paket/Services.php"); 
            exit;
        } else {
            // Jika login gagal, pastikan modal muncul kembali dengan pesan
            // Anda mungkin perlu menambahkan JavaScript untuk memicu modal di halaman yang sama
            $message = "Email atau password salah!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Index - Artefax</title>
    <meta name="description" content="" />
    <meta name="keywords" content="" />

    <link href="assets/img/logo Artefax1.png" rel="icon" />
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon" />

    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Questrial:wght@400&display=swap"
        rel="stylesheet" />

    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet" />
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet" />

    <link href="assets/css/main.css" rel="stylesheet" />
</head>

<body class="index-page">

    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="index.php" class="logo d-flex align-items-center">
                <img src="assets/img/logo Artefax.png" alt="Logo Artefax" style="max-height: 70px" />
            </a>

            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="#hero" class="active">Home</a></li>
                    <li><a href="#services">Layanan</a></li>
                    <li><a href="#portfolio">Portfolio</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="view/profil.php">Profile</a></li>
                    <li><a href="RiwayatBooking.php">Riwayat</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>

    <main class="main">
        <section id="hero" class="hero section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="hero-content">
                            <h1>Menciptakan Momen, <span>Mengabadikan Kenangan</span></h1>
                            <p>
                                ARTEFAX.ID adalah partner kreatif Anda dalam menghadirkan acara berkesan.
                                Dari perencanaan hingga dokumentasi, kami menawarkan solusi event organizer
                                dan multimedia yang inovatif, profesional, dan terintegrasi.
                            </p>
                            <div class="hero-actions justify-content-center justify-content-lg-start">
                                <a href="view/login.php" class="btn-primary scrollto">Login Here</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="hero-image">
                            <img src="assets/img/animasi.png" class="img-fluid floating" alt="Hero Image" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="services" class="services section">
            <div class="container section-title">
                <h2>Layanan Kami</h2>
                <p>Kami menyediakan layanan lengkap mulai dari event organizer, dokumentasi visual, hingga multimedia terintegrasi yang membantu mewujudkan acara impian Anda.</p>
            </div>

            <div class="container">
                <div class="row justify-content-center gy-4">

                    <div class="col-lg-6 col-md-8">
                        <div class="service-card">
                            <div class="service-icon"><i class="bi bi-palette"></i></div>
                            <h3>Jasa Event</h3>
                            <p>Kami siap membantu merancang dan menjalankan acara kamu dengan konsep terbaik dan hasil yang maksimal.</p>
                            <?php if (isset($_SESSION['user'])): ?>
                                <a href="Paket/Services.php" class="service-link">Pesan Sekarang <i class="bi bi-arrow-right"></i></a>
                            <?php else: ?>
                                <a href="#" class="service-link" data-bs-toggle="modal" data-bs-target="#loginModal">Pesan Sekarang <i class="bi bi-arrow-right"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>

                    </div>
            </div>
        </section>
        </main>
    
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content overflow-hidden border-0" style="border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
          <div class="modal-header text-white text-center position-relative" style="background: linear-gradient(135deg, #5c99ee, #4c89de); padding: 2.5rem 1rem;">
            <h4 class="modal-title w-100 fw-bold mb-0" id="loginModalLabel" style="font-size: 1.75rem; color: white !important; letter-spacing: 0.5px;">
              Login ke Artefax
            </h4>
            <button type="button" class="btn-close btn-close-white position-absolute end-0 me-4 top-50 translate-middle-y" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <?php if (!empty($message)): ?>
              <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
              <input type="hidden" name="login_submit" value="1">
              
              <div class="mb-3">
                <input type="email" name="email" class="form-control form-control-lg" 
                       placeholder="Email" required autocomplete="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       style="font-size: 1.25rem;"> 
              </div>
              
              <div class="mb-4">
                <div class="password-wrapper position-relative">
                  <input type="password" name="password" id="modalPassword" class="form-control form-control-lg" 
                         placeholder="Password" required autocomplete="current-password"
                         style="font-size: 1.25rem; padding-right: 3.5rem;"> 
                         
                  <button type="button" class="btn toggle-password position-absolute end-0 top-50 translate-middle-y me-3" 
                          onclick="toggleModalPass()" 
                          style="padding: 0; width: 2.5rem; height: 100%; color: #6c757d;">
                    <i class="bi bi-eye" style="font-size: 1.5rem;"></i>
                  </button>
                </div>
              </div>

              <button type="submit" class="btn btn-primary w-100 fw-bold py-3" style="border-radius: 50px; background: linear-gradient(135deg, #5c99ee, #4c89de); border: none; font-size: 1.1rem;">
                Login Sekarang
              </button>
            </form>

            <div class="text-center mt-4">
              <p class="mb-2">Belum punya akun? 
                <a href="view/register.php" class="text-primary fw-bold">Daftar di sini</a>
              </p>
              <a href="view/forgot_password.php" class="text-muted small">Lupa Password?</a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Pastikan fungsi toggleModalPass tersedia
        function toggleModalPass() {
            var x = document.getElementById("modalPassword");
            var icon = document.querySelector("#loginModal .toggle-password i");

            if (x.type === "password") {
                x.type = "text";
                // Ganti ikon menjadi mata tertutup
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                x.type = "password";
                // Ganti ikon kembali menjadi mata terbuka
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
        
        // Tambahkan fungsi untuk menampilkan modal jika ada pesan error
        <?php if (!empty($message)): ?>
            var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            loginModal.show();
        <?php endif; ?>
    </script>

  <!-- Akhir Modal -->
    <!-- End Services Section -->

    <!-- Features Section -->
    <section id="features" class="features section">
      <div class="container">
        <div class="features-grid">
          <!-- Keunggulan 1 -->
          <div class="features-card">
            <div class="icon-wrapper">
              <i class="bi bi-lightbulb"></i>
            </div>
            <h3>Creative & Innovative Team</h3>
            <p>Kami memiliki tim kreatif yang selalu menghadirkan ide dan konsep segar untuk setiap proyek agar hasilnya unik dan berkarakter.</p>
            <div class="features-list">
              <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Konsep desain orisinal</span>
              </div>
              <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Solusi kreatif sesuai kebutuhan klien</span>
              </div>
              <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Sentuhan profesional di setiap detail</span>
              </div>
            </div>
            <div class="image-container">
              <img src="assets/img/At the office-amico.png" alt="Creative Team" class="img-fluid" />
            </div>
          </div>

          <!-- Keunggulan 2 -->
          <div class="features-card">
            <div class="icon-wrapper">
              <i class="bi bi-camera-reels"></i>
            </div>
            <h3>Complete Multimedia Services</h3>
            <p>Artefax menyediakan layanan lengkap mulai dari event organizer, dokumentasi, hingga penyewaan alat multimedia.</p>
            <div class="features-list">
              <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Layanan terintegrasi satu pintu</span>
              </div>
              <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Peralatan modern dan berkualitas</span>
              </div>
              <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Tim teknis berpengalaman</span>
              </div>
            </div>
            <div class="image-container">
              <img src="assets/img/Studio photographer-amico.png" alt="Multimedia Services" class="img-fluid" />
            </div>
          </div>

          <!-- Keunggulan 3 -->
          <div class="features-card">
            <div class="icon-wrapper">
              <i class="bi bi-gear-wide-connected"></i>
            </div>
            <h3>Professional Workflow</h3>
            <p>Kami bekerja dengan sistem terencana agar setiap proyek berjalan lancar, tepat waktu, dan sesuai standar kualitas tinggi.</p>
            <div class="features-list">
              <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Manajemen waktu dan tim yang solid</span>
              </div>
              <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Proses kerja transparan</span>
              </div>
              <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Evaluasi hasil setiap tahap produksi</span>
              </div>
            </div>
            <div class="image-container">
              <img src="assets/img/Events-amico.png" alt="Professional Workflow" class="img-fluid" />
            </div>
          </div>

          <!-- Keunggulan 4 -->
          <div class="features-card">
            <div class="icon-wrapper">
              <i class="bi bi-people"></i>
            </div>
            <h3>Client Satisfaction Focus</h3>
            <p>Kepuasan klien adalah prioritas utama kami, dengan pelayanan yang fleksibel, ramah, dan selalu terbuka terhadap ide baru.</p>
            <div class="features-list">
              <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Komunikasi dua arah yang responsif</span>
              </div>
              <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Penyesuaian konsep sesuai permintaan</span>
              </div>
              <div class="feature-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Layanan after-project yang siap membantu</span>
              </div>
            </div>
            <div class="image-container">
              <img src="assets/img/Partnership-amico.png" alt="Client Focus" class="img-fluid" />
            </div>
          </div>

        </div>
      </div>
    </section>
    <!-- /Features Section -->

    <!-- Portfolio Section -->
    <section id="portfolio" class="portfolio section">
      <!-- Section Title -->
      <div class="container section-title">
        <h2>Portfolio</h2>
        <p>Kumpulan hasil karya terbaik kami yang mencerminkan kreativitas, kualitas, dan komitmen dalam setiap proyek.</p>
      </div>
      <!-- End Section Title -->

      <div class="container">
        <div class="isotope-layout" data-default-filter="*" data-layout="fitRows" data-sort="original-order">
          <div class="portfolio-filters-wrapper">
            <ul class="portfolio-filters isotope-filters">
              <li data-filter="*" class="filter-active">All Projects</li>
              <li data-filter=".filter-wedding">Wedding</li>
              <li data-filter=".filter-yearbook">Year Book</li>
              <li data-filter=".filter-graduation">Graduation</li>
            </ul>
          </div>

          <div class="row gy-4 portfolio-grid isotope-container">
            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-graduation">
              <div class="portfolio-card">
                <div class="image-container">
                  <img src="assets/img/portfolio/foto (1)_2_11zon.webp" class="img-fluid" alt="Brand Identity" loading="lazy" />
                  <div class="overlay">
                    <div class="overlay-content">
                      <a href="assets/img/portfolio/foto (1)_2_11zon.webpp" class="glightbox zoom-link" title="Brand Identity Project">
                        <i class="bi bi-zoom-in"></i>
                      </a>
                      <a href="portfolio-details.html" class="details-link" title="View Project Details">
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
                <div class="content">
                  <h3>Wedding</h3>
                  <p>Corporate branding and visual identity system</p>
                </div>
              </div>
            </div>
            <!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-graduation">
              <div class="portfolio-card">
                <div class="image-container">
                  <img src="assets/img/portfolio/foto (2)_3_11zon.webp" class="img-fluid" alt="E-commerce Platform" loading="lazy" />
                  <div class="overlay">
                    <div class="overlay-content">
                      <a href="assets/img/portfolio/foto (2)_3_11zon.webp" class="glightbox zoom-link" title="E-commerce Platform">
                        <i class="bi bi-zoom-in"></i>
                      </a>
                      <a href="portfolio-details.html" class="details-link" title="View Project Details">
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
                <div class="content">
                  <h3>E-commerce Platform</h3>
                  <p>Modern online shopping experience</p>
                </div>
              </div>
            </div>
            <!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-wedding">
              <div class="portfolio-card">
                <div class="image-container">
                  <img src="assets/img/portfolio/foto (3)_4_11zon.webp" class="img-fluid" alt="Magazine Design" loading="lazy" />
                  <div class="overlay">
                    <div class="overlay-content">
                      <a href="assets/img/portfolio/foto (3)_4_11zon.webp" class="glightbox zoom-link" title="Magazine Design">
                        <i class="bi bi-zoom-in"></i>
                      </a>
                      <a href="portfolio-details.html" class="details-link" title="View Project Details">
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
                <div class="content">
                  <h3>Wedding</h3>
                  <p>Dokumentasi dan konsep acara pernikahan dengan sentuhan artistik dan detail yang berkelas.</p>
                </div>
              </div>
            </div>
            <!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-wedding">
              <div class="portfolio-card">
                <div class="image-container">
                  <img src="assets/img/portfolio/foto (4)_5_11zon.webp" class="img-fluid" alt="Motion Graphics" loading="lazy" />
                  <div class="overlay">
                    <div class="overlay-content">
                      <a href="assets/img/portfolio/foto (4)_5_11zon.webp" class="glightbox zoom-link" title="Motion Graphics">
                        <i class="bi bi-zoom-in"></i>
                      </a>
                      <a href="portfolio-details.html" class="details-link" title="View Project Details">
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
                <div class="content">
                  <h3>Wedding</h3>
                  <p>Dari dekorasi hingga dokumentasi, kami siap bikin hari bahagiamu jadi tak terlupakan.</p>
                </div>
              </div>
            </div>
            <!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-wedding">
              <div class="portfolio-card">
                <div class="image-container">
                  <img src="assets/img/portfolio/foto (5)_6_11zon.webp" class="img-fluid" alt="Logo Collection" loading="lazy" />
                  <div class="overlay">
                    <div class="overlay-content">
                      <a href="assets/img/portfolio/foto (5)_6_11zon.webp" class="glightbox zoom-link" title="Logo Collection">
                        <i class="bi bi-zoom-in"></i>
                      </a>
                      <a href="portfolio-details.html" class="details-link" title="View Project Details">
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
                <div class="content">
                  <h3>Wedding</h3>
                  <p>Kami bantu wujudkan pernikahan impianmu dengan konsep visual yang menawan dan penuh cerita.</p>
                </div>
              </div>
            </div>
            <!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-yearbook">
              <div class="portfolio-card">
                <div class="image-container">
                  <img src="assets/img/portfolio/foto (7)_8_11zon.webp" class="img-fluid" alt="Mobile App Design" loading="lazy" />
                  <div class="overlay">
                    <div class="overlay-content">
                      <a href="assets/img/portfolio/foto (7)_8_11zon.webp" class="glightbox zoom-link" title="Mobile App Design">
                        <i class="bi bi-zoom-in"></i>
                      </a>
                      <a href="portfolio-details.html" class="details-link" title="View Project Details">
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
                <div class="content">
                  <h3>Mobile App Design</h3>
                  <p>User-centered interface design</p>
                </div>
              </div>
            </div>
            <!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-yearbook">
              <div class="portfolio-card">
                <div class="image-container">
                  <img src="assets/img/portfolio/foto (6)_7_11zon.webp" class="img-fluid" alt="Packaging Design" loading="lazy" />
                  <div class="overlay">
                    <div class="overlay-content">
                      <a href="assets/img/portfolio/foto (6)_7_11zon.webp" class="glightbox zoom-link" title="Packaging Design">
                        <i class="bi bi-zoom-in"></i>
                      </a>
                      <a href="portfolio-details.html" class="details-link" title="View Project Details">
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
                <div class="content">
                  <h3>Packaging Design</h3>
                  <p>Sustainable product packaging solutions</p>
                </div>
              </div>
            </div>
            <!-- End Portfolio Item -->
          </div>
          <!-- End Portfolio Grid -->
        </div>
      </div>
    </section>
    <!-- /Portfolio Section -->

    <!-- How We Work Section -->
    <section id="how-we-work" class="how-we-work section">
      <!-- Section Title -->
      <div class="container section-title">
        <h2>Langkah Kami</h2>
        <p>Langkah-langkah kami dalam membantu mewujudkan event dan proyek multimedia Anda dengan hasil terbaik.</p>
      </div>
      <!-- End Section Title -->

      <div class="container">
        <div class="steps-wrapper">
          <div class="row">
            <div class="col-lg-3 col-md-6">
              <div class="step-item">
                <div class="step-circle">
                  <span>1</span>
                </div>
                <h3>Konsultasi & Konsep Awal</h3>
                <p>Kami mulai dengan mendengarkan ide dan kebutuhan Anda, lalu bantu menentukan konsep, tema, serta perkiraan anggaran acara.</p>
              </div>
            </div>

            <div class="col-lg-3 col-md-6">
              <div class="step-item">
                <div class="step-circle">
                  <span>2</span>
                </div>
                <h3>Perencanaan Teknis & Desain</h3>
                <p>Setelah konsep disepakati, tim kami menyusun rundown acara, desain dekorasi, tata panggung, dan kebutuhan teknis lainnya.</p>
              </div>
            </div>

            <div class="col-lg-3 col-md-6">
              <div class="step-item">
                <div class="step-circle">
                  <span>3</span>
                </div>
                <h3>Pelaksanaan Acara</h3>
                <p>Tim Artefax akan mengatur jalannya acara mulai dari pemasangan alat, lighting, sound system, hingga koordinasi di lapangan.</p>
              </div>
            </div>

            <div class="col-lg-3 col-md-6">
              <div class="step-item">
                <div class="step-circle">
                  <span>4</span>
                </div>
                <h3>Evaluasi & Dokumentasi Akhir</h3>
                <p>Setelah acara selesai, kami serahkan dokumentasi lengkap (foto, video, atau live recording) serta laporan singkat hasil pelaksanaan.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- /How We Work Section -->


    <!-- Tabs Section (How We Work Version) -->
    <section id="tabs" class="tabs section">
      <div class="container">
        <div class="tabs-wrapper">
          <div class="tabs-header">
            <ul class="nav nav-tabs">
              <li class="nav-item">
                <a class="nav-link active show" data-bs-toggle="tab" data-bs-target="#tabs-tab-1">
                  <div class="tab-content-preview">
                    <span class="tab-number">01</span>
                    <div class="tab-text">
                      <h6>Konsultasi</h6>
                      <small>Diskusi awal & ide konsep</small>
                    </div>
                  </div>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" data-bs-target="#tabs-tab-2">
                  <div class="tab-content-preview">
                    <span class="tab-number">02</span>
                    <div class="tab-text">
                      <h6>Perencanaan</h6>
                      <small>Desain & persiapan teknis</small>
                    </div>
                  </div>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" data-bs-target="#tabs-tab-3">
                  <div class="tab-content-preview">
                    <span class="tab-number">03</span>
                    <div class="tab-text">
                      <h6>Pelaksanaan</h6>
                      <small>Eksekusi acara di lapangan</small>
                    </div>
                  </div>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" data-bs-target="#tabs-tab-4">
                  <div class="tab-content-preview">
                    <span class="tab-number">04</span>
                    <div class="tab-text">
                      <h6>Evaluasi</h6>
                      <small>Dokumentasi & hasil akhir</small>
                    </div>
                  </div>
                </a>
              </li>
            </ul>
          </div>

          <div class="tab-content">
            <!-- Step 1 -->
            <div class="tab-pane fade active show" id="tabs-tab-1">
              <div class="row align-items-center">
                <div class="col-lg-6">
                  <div class="content-area">
                    <div class="content-badge">
                      <i class="bi bi-chat-dots"></i>
                      <span>Konsultasi & Konsep Awal</span>
                    </div>
                    <h3>Dari Ide Menjadi Rencana</h3>
                    <p>Kami mulai dengan mendengarkan ide dan kebutuhan Anda, lalu membantu menentukan tema, lokasi, dan konsep acara yang paling sesuai.</p>

                    <div class="feature-points">
                      <div class="point-item">
                        <i class="bi bi-arrow-right"></i>
                        <span>Diskusi santai untuk memahami kebutuhan acara</span>
                      </div>
                      <div class="point-item">
                        <i class="bi bi-arrow-right"></i>
                        <span>Rekomendasi konsep & paket layanan terbaik</span>
                      </div>
                      <div class="point-item">
                        <i class="bi bi-arrow-right"></i>
                        <span>Penyesuaian ide dengan anggaran dan target waktu</span>
                      </div>
                    </div>

                    <a href="#" class="explore-link">Mulai Konsultasi <i class="bi bi-arrow-up-right"></i></a>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="visual-content">
                    <img src="assets/img/features/features-1.webp" alt="Konsultasi Artefax" class="img-fluid" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 2 -->
            <div class="tab-pane fade" id="tabs-tab-2">
              <div class="row align-items-center">
                <div class="col-lg-6">
                  <div class="content-area">
                    <div class="content-badge">
                      <i class="bi bi-pencil-square"></i>
                      <span>Perencanaan Teknis</span>
                    </div>
                    <h3>Menyusun Desain & Kebutuhan Teknis</h3>
                    <p>Setelah konsep disepakati, kami menyusun rundown acara, desain dekorasi, sistem panggung, lighting, dan kebutuhan multimedia lainnya.</p>

                    <div class="feature-points">
                      <div class="point-item">
                        <i class="bi bi-arrow-right"></i>
                        <span>Desain dekorasi dan tata panggung profesional</span>
                      </div>
                      <div class="point-item">
                        <i class="bi bi-arrow-right"></i>
                        <span>Penyusunan jadwal dan koordinasi teknis detail</span>
                      </div>
                      <div class="point-item">
                        <i class="bi bi-arrow-right"></i>
                        <span>Penentuan alat & sumber daya yang dibutuhkan</span>
                      </div>
                    </div>

                    <a href="#" class="explore-link">Lihat Rencana <i class="bi bi-arrow-up-right"></i></a>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="visual-content">
                    <img src="assets/img/features/features-2.webp" alt="Perencanaan Artefax" class="img-fluid" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 3 -->
            <div class="tab-pane fade" id="tabs-tab-3">
              <div class="row align-items-center">
                <div class="col-lg-6">
                  <div class="content-area">
                    <div class="content-badge">
                      <i class="bi bi-lightning-charge"></i>
                      <span>Pelaksanaan</span>
                    </div>
                    <h3>Eksekusi dengan Tim Profesional</h3>
                    <p>Tim Artefax memastikan semua berjalan lancar — dari instalasi alat, pengaturan lighting & sound, hingga jalannya acara di lokasi.</p>

                    <div class="feature-points">
                      <div class="point-item">
                        <i class="bi bi-arrow-right"></i>
                        <span>Koordinasi penuh antara tim produksi & client</span>
                      </div>
                      <div class="point-item">
                        <i class="bi bi-arrow-right"></i>
                        <span>Pengawasan teknis selama acara berlangsung</span>
                      </div>
                      <div class="point-item">
                        <i class="bi bi-arrow-right"></i>
                        <span>Penyesuaian real-time untuk hasil maksimal</span>
                      </div>
                    </div>

                    <a href="#" class="explore-link">Lihat Proses Kami <i class="bi bi-arrow-up-right"></i></a>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="visual-content">
                    <img src="assets/img/features/features-4.webp" alt="Pelaksanaan Artefax" class="img-fluid" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 4 -->
            <div class="tab-pane fade" id="tabs-tab-4">
              <div class="row align-items-center">
                <div class="col-lg-6">
                  <div class="content-area">
                    <div class="content-badge">
                      <i class="bi bi-camera-reels"></i>
                      <span>Evaluasi & Dokumentasi</span>
                    </div>
                    <h3>Penutup yang Sempurna</h3>
                    <p>Setelah acara selesai, kami serahkan hasil dokumentasi lengkap (foto, video, live recording) serta laporan pelaksanaan sebagai bahan evaluasi.</p>

                    <div class="feature-points">
                      <div class="point-item">
                        <i class="bi bi-arrow-right"></i>
                        <span>Hasil dokumentasi profesional siap dibagikan</span>
                      </div>
                      <div class="point-item">
                        <i class="bi bi-arrow-right"></i>
                        <span>Laporan singkat hasil pelaksanaan acara</span>
                      </div>
                      <div class="point-item">
                        <i class="bi bi-arrow-right"></i>
                        <span>Feedback & review untuk peningkatan layanan</span>
                      </div>
                    </div>

                    <a href="#" class="explore-link">Lihat Hasil Akhir <i class="bi bi-arrow-up-right"></i></a>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="visual-content">
                    <img src="assets/img/features/features-5.webp" alt="Dokumentasi Artefax" class="img-fluid" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- /Tabs Section -->

    <!-- Testimonials Section removed on index to hide it -->
    <!-- Testimonials section removed -->
    <!-- /Testimonials Section -->

    <!-- Faq Section -->
    <section id="faq" class="faq section">
      <!-- Section Title -->
      <div class="container section-title">
        <h2>Pertanyaan yang Sering Diajukan</h2>
        <p>Temukan jawaban atas pertanyaan umum seputar layanan, pemesanan, dan pelaksanaan acara bersama Artefax.</p>
      </div>
      <!-- End Section Title -->

      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-9">
            <div class="faq-wrapper">

              <!-- FAQ Item 1 -->
              <div class="faq-item faq-active">
                <div class="faq-header">
                  <span class="faq-number">01</span>
                  <h4>Bagaimana cara memesan layanan atau paket acara di Artefax?</h4>
                  <div class="faq-toggle">
                    <i class="bi bi-plus"></i>
                    <i class="bi bi-dash"></i>
                  </div>
                </div>
                <div class="faq-content">
                  <div class="content-inner">
                    <p>Anda dapat memesan layanan melalui halaman kontak kami atau langsung datang ke kantor Artefax. Tim kami akan membantu menentukan konsep, paket, dan kebutuhan acara sesuai anggaran Anda.</p>
                  </div>
                </div>
              </div>
              <!-- End FAQ Item -->

              <!-- FAQ Item 2 -->
              <div class="faq-item">
                <div class="faq-header">
                  <span class="faq-number">02</span>
                  <h4>Apakah saya bisa menyesuaikan paket layanan sesuai kebutuhan acara?</h4>
                  <div class="faq-toggle">
                    <i class="bi bi-plus"></i>
                    <i class="bi bi-dash"></i>
                  </div>
                </div>
                <div class="faq-content">
                  <div class="content-inner">
                    <p>Tentu! Semua paket Artefax bersifat fleksibel. Anda dapat menambah atau mengurangi layanan seperti dekorasi, dokumentasi, atau penyewaan alat sesuai kebutuhan acara Anda.</p>
                  </div>
                </div>
              </div>
              <!-- End FAQ Item -->

              <!-- FAQ Item 3 -->
              <div class="faq-item">
                <div class="faq-header">
                  <span class="faq-number">03</span>
                  <h4>Berapa lama waktu yang dibutuhkan untuk mempersiapkan sebuah acara?</h4>
                  <div class="faq-toggle">
                    <i class="bi bi-plus"></i>
                    <i class="bi bi-dash"></i>
                  </div>
                </div>
                <div class="faq-content">
                  <div class="content-inner">
                    <p>Waktu persiapan tergantung pada skala dan jenis acara. Untuk acara kecil biasanya memerlukan 3–7 hari, sedangkan acara besar seperti pernikahan atau konser bisa memakan waktu hingga beberapa minggu.</p>
                  </div>
                </div>
              </div>
              <!-- End FAQ Item -->

              <!-- FAQ Item 4 -->
              <div class="faq-item">
                <div class="faq-header">
                  <span class="faq-number">04</span>
                  <h4>Apakah Artefax menyediakan dokumentasi acara seperti foto dan video?</h4>
                  <div class="faq-toggle">
                    <i class="bi bi-plus"></i>
                    <i class="bi bi-dash"></i>
                  </div>
                </div>
                <div class="faq-content">
                  <div class="content-inner">
                    <p>Ya, kami menyediakan layanan dokumentasi lengkap meliputi foto, video, dan live recording yang dapat disesuaikan dengan kebutuhan dan konsep acara Anda.</p>
                  </div>
                </div>
              </div>
              <!-- End FAQ Item -->

              <!-- FAQ Item 5 -->
              <div class="faq-item">
                <div class="faq-header">
                  <span class="faq-number">05</span>
                  <h4>Bagaimana sistem pembayaran dan kebijakan pembatalan di Artefax?</h4>
                  <div class="faq-toggle">
                    <i class="bi bi-plus"></i>
                    <i class="bi bi-dash"></i>
                  </div>
                </div>
                <div class="faq-content">
                  <div class="content-inner">
                    <p>Pembayaran dilakukan dalam dua tahap: DP saat pemesanan dan pelunasan sebelum acara berlangsung. Pembatalan maksimal dapat dilakukan 7 hari sebelum acara dengan ketentuan pengembalian sesuai perjanjian awal.</p>
                  </div>
                </div>
              </div>
              <!-- End FAQ Item -->

            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- /Faq Section -->


    <!-- Team Section -->
    <section id="team" class="team section">
      <!-- Section Title 
        <div class="container section-title">
          <h2>Team</h2>
          <p>Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit</p>
        </div>-->
      <!-- End Section Title -->

      <div class="container">
        <!-- Team members are optional — commented out. 
               Untuk mengaktifkan kembali, hapus komentar  dan -->
        <!--
          <div class="row gy-4">
            <div class="col-lg-6">
              <div class="team-member d-flex">
                <div class="member-img">
                  <img src="assets/img/person/person-m-7.webp" class="img-fluid" alt="" loading="lazy" />
                </div>
                <div class="member-info flex-grow-1">
                  <h4>Walter White</h4>
                  <span>Chief Executive Officer</span>
                  <p>Aliquam iure quaerat voluptatem praesentium possimus unde laudantium vel dolorum distinctio dire flow</p>
                  <div class="social">
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-twitter-x"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                    <a href=""><i class="bi bi-youtube"></i></a>
                  </div>
                </div>
              </div>
            </div>
             End Team Member -->

        <!-- Contact Section -->
        <section id="contact" class="contact section">
          <!-- Section Title -->
          <div class="container section-title">
            <h2>Hubungi Kami</h2>
            <p>Punya pertanyaan atau ingin memesan layanan dari Artefax? Silakan isi formulir di bawah atau hubungi kami langsung.</p>
          </div>
          <!-- End Section Title -->

          <div class="container">
            <div class="row align-items-stretch">
              <div class="col-lg-7 order-lg-1 order-2">
                <div class="contact-form-container">
                  <div class="form-intro">
                    <h2>Mulai Percakapan</h2>
                    <p>Kami siap membantu mewujudkan acara impian Anda, dari konsep hingga pelaksanaan. Ceritakan kebutuhan Anda dan tim Artefax akan segera menghubungi!</p>
                  </div>

                  <form action="forms/contact.php" method="post" class="php-email-form contact-form">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-field">
                          <input type="text" name="name" class="form-input" id="userName" placeholder="Nama Anda" required />
                          <label for="userName" class="field-label">Nama</label>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-field">
                          <input type="email" class="form-input" name="email" id="userEmail" placeholder="Email Anda" required />
                          <label for="userEmail" class="field-label">Email</label>
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-field">
                          <input type="tel" class="form-input" name="phone" id="userPhone" placeholder="Nomor Telepon / WhatsApp" />
                          <label for="userPhone" class="field-label">Telepon</label>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-field">
                          <input type="text" class="form-input" name="subject" id="messageSubject" placeholder="Subjek Pesan" required />
                          <label for="messageSubject" class="field-label">Subjek</label>
                        </div>
                      </div>
                    </div>

                    <div class="form-field message-field">
                      <textarea class="form-input message-input" name="message" id="userMessage" rows="5" placeholder="Ceritakan kebutuhan atau ide acara Anda" required></textarea>
                      <label for="userMessage" class="field-label">Pesan</label>
                    </div>

                    <div class="my-3">
                      <div class="loading">Mengirim...</div>
                      <div class="error-message"></div>
                      <div class="sent-message">Pesan Anda telah terkirim. Terima kasih telah menghubungi Artefax!</div>
                    </div>

                    <button type="submit" class="send-button">
                      Kirim Pesan
                      <span class="button-arrow">→</span>
                    </button>
                  </form>
                </div>
              </div>

              <div class="col-lg-5 order-lg-2 order-1">
                <div class="contact-sidebar">
                  <div class="contact-header">
                    <h3>Kontak Langsung</h3>
                    <p>Anda juga bisa menghubungi kami langsung melalui kontak berikut untuk konsultasi, pemesanan layanan, atau penawaran khusus.</p>
                  </div>

                  <div class="contact-methods">
                    <div class="contact-method">
                      <div class="contact-icon">
                        <i class="bi bi-geo-alt"></i>
                      </div>
                      <div class="contact-details">
                        <span class="method-label">Alamat</span>
                        <p>Patrang, Jember, Jawa Timur</p>
                      </div>
                    </div>

                    <div class="contact-method">
                      <div class="contact-icon">
                        <i class="bi bi-envelope"></i>
                      </div>
                      <div class="contact-details">
                        <span class="method-label">Email</span>
                        <p>artefaxm@gmail.com</p>
                      </div>
                    </div>

                    <div class="contact-method">
                      <div class="contact-icon">
                        <i class="bi bi-telephone"></i>
                      </div>
                      <div class="contact-details">
                        <span class="method-label">Telepon</span>
                        <p>+62 812-3456-7890</p>
                      </div>
                    </div>

                    <div class="contact-method">
                      <div class="contact-icon">
                        <i class="bi bi-clock"></i>
                      </div>
                      <div class="contact-details">
                        <span class="method-label">Jam Operasional</span>
                        <p>Senin - Jumat: 09.00 - 18.00<br />Sabtu: 10.00 - 16.00</p>
                      </div>
                    </div>
                  </div>

                  <div class="connect-section">
                    <span class="connect-label">Terhubung dengan Kami</span>
                    <div class="social-links">
                      <a href="#" class="social-link"><i class="bi bi-instagram"></i></a>
                      <a href="#" class="social-link"><i class="bi bi-facebook"></i></a>
                      <a href="#" class="social-link"><i class="bi bi-tiktok"></i></a>
                      <a href="#" class="social-link"><i class="bi bi-whatsapp"></i></a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
        <!-- /Contact Section -->
  </main>

  <footer id="footer" class="footer position-relative light-background">
    <div class="container">
      <div class="row gy-5">
        <div class="col-lg-4">
          <div class="footer-content">
            <a href="index.html" class="logo d-flex align-items-center mb-4">
              <span class="sitename">Artefax.id</span>
            </a>
            <p class="mb-4">
              Artefax.id adalah penyedia layanan event dan persewaan alat profesional yang berlokasi di Jember, Jawa Timur. Kami siap membantu mewujudkan acara terbaik Anda dengan layanan cepat, fleksibel, dan berkualitas.
            </p>

            <div class="newsletter-form">
              <h5>Berlangganan Info & Promo</h5>
              <form action="forms/newsletter.php" method="post" class="php-email-form">
                <div class="input-group">
                  <input type="email" name="email" class="form-control" placeholder="Masukkan email Anda" required />
                  <button type="submit" class="btn-subscribe">
                    <i class="bi bi-send"></i>
                  </button>
                </div>
                <div class="loading">Mengirim...</div>
                <div class="error-message"></div>
                <div class="sent-message">Terima kasih telah berlangganan!</div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-2 col-6">
          <div class="footer-links">
            <h4>Perusahaan</h4>
            <ul>
              <li><a href="#"><i class="bi bi-chevron-right"></i> Tentang Kami</a></li>
              <li><a href="#"><i class="bi bi-chevron-right"></i> Layanan</a></li>
              <li><a href="#"><i class="bi bi-chevron-right"></i> Galeri</a></li>
              <li><a href="#"><i class="bi bi-chevron-right"></i> Testimoni</a></li>
              <li><a href="#"><i class="bi bi-chevron-right"></i> Hubungi Kami</a></li>
            </ul>
          </div>
        </div>

        <div class="col-lg-2 col-6">
          <div class="footer-links">
            <h4>Layanan Kami</h4>
            <ul>
              <li><a href="#"><i class="bi bi-chevron-right"></i> Dekorasi Acara</a></li>
              <li><a href="#"><i class="bi bi-chevron-right"></i> Sewa Sound System</a></li>
              <li><a href="#"><i class="bi bi-chevron-right"></i> Sewa Lighting & Panggung</a></li>
              <li><a href="#"><i class="bi bi-chevron-right"></i> Wedding & Event Organizer</a></li>
              <li><a href="#"><i class="bi bi-chevron-right"></i> Dokumentasi Acara</a></li>
            </ul>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="footer-contact">
            <h4>Hubungi Kami</h4>
            <div class="contact-item">
              <div class="contact-icon">
                <i class="bi bi-geo-alt"></i>
              </div>
              <div class="contact-info">
                <p>Patrang, Kabupaten Jember<br />Jawa Timur, Indonesia</p>
              </div>
            </div>

            <div class="contact-item">
              <div class="contact-icon">
                <i class="bi bi-telephone"></i>
              </div>
              <div class="contact-info">
                <p>+62 856 4581 9510</p>
              </div>
            </div>

            <div class="contact-item">
              <div class="contact-icon">
                <i class="bi bi-envelope"></i>
              </div>
              <div class="contact-info">
                <p>artefaxm@gmail.com</p>
              </div>
            </div>

            <div class="social-links">
              <a href="#"><i class="bi bi-instagram"></i></a>
              <a href="#"><i class="bi bi-facebook"></i></a>
              <a href="#"><i class="bi bi-tiktok"></i></a>
              <a href="#"><i class="bi bi-whatsapp"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="copyright">
              <p>© <span>2025</span> <strong class="px-1 sitename">Artefax.id</strong> <span>All Rights Reserved</span></p>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="footer-bottom-links">
              <a href="#">Kebijakan Privasi</a>
              <a href="#">Syarat & Ketentuan</a>
              <a href="#">FAQ</a>
            </div>
            <div class="credits">
              Dirancang oleh <a href="#">Artefax Creative Team</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>
</body>

</html>