<?php
ob_start(); // <-- Tambahkan ini agar header() tetap bisa dijalankan meski ada output
session_start();

// Hapus data sesi login_attempts dan login_timeout jika tidak diperlukan saat memulai
if (!isset($_SESSION['user'])) {
    unset($_SESSION['login_attempts']);
    unset($_SESSION['login_timeout']);
}

require_once "config/koneksi.php";
require_once "class/users.php";

$database = new Database();
$conn = $database->getConnection();
$user = new User($conn);

$message = ""; // Inisialisasi $message sebagai string kosong

// PROSES LOGIN
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login_submit'])) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }

    // Cek timeout
    if (isset($_SESSION['login_timeout']) && time() < $_SESSION['login_timeout']) {
        $message = "Akun Anda terkunci sementara. Silakan coba lagi nanti.";
    } elseif ($_SESSION['login_attempts'] >= 5) {
        $message = "Terlalu banyak percobaan login. Silakan coba lagi setelah 5 menit.";
        $_SESSION['login_timeout'] = time() + 300;
    } else {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($email) || empty($password)) {
            $message = "Email dan password wajib diisi!";
            $_SESSION['login_attempts']++;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Format email tidak valid!";
            $_SESSION['login_attempts']++;
        } else {
            $user->Email = $email;
            $user->Password = $password;

            $login = $user->login();

            if ($login) {
                $_SESSION['login_attempts'] = 0;
                unset($_SESSION['login_timeout']);
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id' => $login['IDUser'],
                    'nama' => $login['NamaUser'],
                    'email' => $login['Email'],
                    'role' => $login['Role']
                ];

                // Redirect ke halaman layanan
                header("Location: layanan/service-details.php");
                exit;
            } else {
                $message = "Email atau password salah!";
                $_SESSION['login_attempts']++;
            }
        }
    }
}

ob_end_flush(); // <-- pastikan output buffer ditutup dengan benar
?>

<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Index - Artefax</title>
    <meta name="description" content="" />
    <meta name="keywords" content="" />

    <!-- Favicons -->
    <link href="assets/img/logo Artefax1.png" rel="icon" />
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon" />

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Questrial:wght@400&display=swap"
      rel="stylesheet"
    />

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet" />
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet" />

    <!-- Main CSS File -->
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
        <li><a href="#about">About</a></li>
        <li><a href="#services">Layanan</a></li>
        <li><a href="#portfolio">Portfolio</a></li>
        <li><a href="#team">Team</a></li>
        <li><a href="#contact">Contact</a></li>

      <li>
        <a href="logout.php"
          class="btn btn-danger px-3 py-2 text-white"
          style="border-radius: 8px;"
          onclick="return confirm('Apakah Anda yakin ingin logout?');">
          Logout
        </a>
      </li>

      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>
  </div>
</header>


    <main class="main">
      <!-- Hero Section -->
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
          <img src="assets/img/illustration/illustration-28.webp" class="img-fluid floating" alt="Hero Image" />
        </div>
      </div>
    </div>
  </div>
</section>

      <!-- /Hero Section -->

      <!-- Clients Section -->
      <section id="clients" class="clients section">
        <div class="container">
          <div class="swiper init-swiper">
            <script type="application/json" class="swiper-config">
              {
                "loop": true,
                "speed": 600,
                "autoplay": {
                  "delay": 5000
                },
                "slidesPerView": "auto",
                "breakpoints": {
                  "320": {
                    "slidesPerView": 2,
                    "spaceBetween": 40
                  },
                  "480": {
                    "slidesPerView": 3,
                    "spaceBetween": 60
                  },
                  "640": {
                    "slidesPerView": 4,
                    "spaceBetween": 80
                  },
                  "992": {
                    "slidesPerView": 6,
                    "spaceBetween": 120
                  }
                }
              }
            </script>
            <div class="swiper-wrapper align-items-center">
              <div class="swiper-slide"><img src="assets/img/clients/clients-1.webp" class="img-fluid" alt="" /></div>
              <div class="swiper-slide"><img src="assets/img/clients/clients-2.webp" class="img-fluid" alt="" /></div>
              <div class="swiper-slide"><img src="assets/img/clients/clients-3.webp" class="img-fluid" alt="" /></div>
              <div class="swiper-slide"><img src="assets/img/clients/clients-4.webp" class="img-fluid" alt="" /></div>
              <div class="swiper-slide"><img src="assets/img/clients/clients-5.webp" class="img-fluid" alt="" /></div>
              <div class="swiper-slide"><img src="assets/img/clients/clients-6.webp" class="img-fluid" alt="" /></div>
              <div class="swiper-slide"><img src="assets/img/clients/clients-7.webp" class="img-fluid" alt="" /></div>
              <div class="swiper-slide"><img src="assets/img/clients/clients-8.webp" class="img-fluid" alt="" /></div>
            </div>
          </div>
        </div>
      </section>
      <!-- /Clients Section -->

      <!-- About Section -->
      <section id="about" class="about section">
        <div class="container">
          <div class="row align-items-center">
            <!-- Image Column -->
            <div class="col-lg-6">
              <div class="about-image">
                <img src="assets/img/about/about-portrait-4.webp" alt="About" class="img-fluid" />
              </div>
            </div>

            <!-- Content Column -->
            <div class="col-lg-6">
              <div class="content">
                <h2>Mengenal Lebih Dekat ARTEFAX.ID</h2>
                <p class="lead">ARTEFAX.ID adalah perusahaan kreatif yang bergerak di bidang Event Organizer (EO) dan layanan multimedia terintegrasi.</p>

                <p>
                  Kami menghadirkan solusi lengkap untuk berbagai kebutuhan acara dan dokumentasi profesional, mulai dari perencanaan hingga eksekusi. Dengan tim yang berpengalaman dan peralatan berstandar industri, kami berkomitmen
                  memberikan hasil terbaik bagi setiap klien.
                </p>

                <!-- Stats Row -->
                <div class="stats-row">
                  <div class="stat-item">
                    <h3><span data-purecounter-start="0" data-purecounter-end="150" data-purecounter-duration="1" class="purecounter"></span>+</h3>
                    <p>Projects Completed</p>
                  </div>
                  <div class="stat-item">
                    <h3><span data-purecounter-start="0" data-purecounter-end="12" data-purecounter-duration="1" class="purecounter"></span>+</h3>
                    <p>Years Experience</p>
                  </div>
                  <div class="stat-item">
                    <h3><span data-purecounter-start="0" data-purecounter-end="98" data-purecounter-duration="1" class="purecounter"></span>%</h3>
                    <p>Client Satisfaction</p>
                  </div>
                </div>
                <!-- End Stats Row -->

                <!-- CTA Button -->
                <div class="cta-wrapper">
                  <a href="#" class="btn-cta">
                    <span>Discover Our Story</span>
                    <i class="bi bi-arrow-right"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- /About Section -->

      <!-- Services Section -->
<section id="services" class="services section">
  <div class="container section-title">
    <h2>Layanan Kami</h2>
    <p>Kami menyediakan layanan lengkap mulai dari event organizer, dokumentasi visual, hingga multimedia terintegrasi yang membantu mewujudkan acara impian Anda.</p>
  </div>

  <div class="container">
    <div class="row gy-4">

      <!-- Layanan 1: Event Organizer -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-palette"></i></div>
          <h3>Event Organizer</h3>
          <p>Kami siap membantu merancang dan menjalankan acara kamu dengan konsep terbaik dan hasil yang maksimal.</p>
          <?php if (isset($_SESSION['user'])): ?>
            <a href="layanan/service-details.php" class="service-link">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php else: ?>
            <a href="#" class="service-link" data-bs-toggle="modal" data-bs-target="#loginModal">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Layanan 2: Wedding Organizer -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-heart"></i></div>
          <h3>Wedding Organizer</h3>
          <p>Mewujudkan pernikahan impian Anda dengan perencanaan matang, dekorasi elegan, dan eksekusi penuh kesan.</p>
          <?php if (isset($_SESSION['user'])): ?>
            <a href="layanan/service-details.php" class="service-link">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php else: ?>
            <a href="#" class="service-link" data-bs-toggle="modal" data-bs-target="#loginModal">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Layanan 3: Graduation -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-camera"></i></div>
          <h3>Graduation</h3>
          <p>Kami mengabadikan setiap momen penting Anda dengan hasil foto dan video berkualitas profesional.</p>
          <?php if (isset($_SESSION['user'])): ?>
            <a href="layanan/service-details.php" class="service-link">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php else: ?>
            <a href="#" class="service-link" data-bs-toggle="modal" data-bs-target="#loginModal">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Layanan 4: Photography & Videography -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-display"></i></div>
          <h3>Photography & Videography</h3>
          <p>Kami mengabadikan setiap momen penting kamu dengan hasil foto dan video berkualitas tinggi.</p>
          <?php if (isset($_SESSION['user'])): ?>
            <a href="layanan/service-details.php" class="service-link">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php else: ?>
            <a href="#" class="service-link" data-bs-toggle="modal" data-bs-target="#loginModal">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Layanan 5: Company Profile  -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-lightning"></i></div>
          <h3>Company Profile</h3>
          <p>Kami membantu menampilkan citra profesional perusahaan kamu melalui video dan desain profil yang menarik.</p>
          <?php if (isset($_SESSION['user'])): ?>
            <a href="layanan/service-details.php" class="service-link">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php else: ?>
            <a href="#" class="service-link" data-bs-toggle="modal" data-bs-target="#loginModal">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Layanan 6: Graphic Design -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-mic"></i></div>
          <h3>Graphic Design</h3>
          <p>Kami menciptakan desain visual yang kreatif dan sesuai dengan karakter brand kamu.</p>
          <?php if (isset($_SESSION['user'])): ?>
            <a href="layanan/service-details.php" class="service-link">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php else: ?>
            <a href="#" class="service-link" data-bs-toggle="modal" data-bs-target="#loginModal">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Layanan 7: Live Streaming -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-mic"></i></div>
          <h3>Live Streaming</h3>
          <p>Kami menyediakan layanan siaran langsung dengan kualitas gambar dan suara yang jernih untuk berbagai acara.</p>
          <?php if (isset($_SESSION['user'])): ?>
            <a href="layanan/service-details.php" class="service-link">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php else: ?>
            <a href="#" class="service-link" data-bs-toggle="modal" data-bs-target="#loginModal">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Layanan 8: Yearbook Production -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-mic"></i></div>
          <h3>Yearbook Production</h3>
          <p>Kami membantu mengemas kenangan terbaikmu dalam buku tahunan yang menarik dan berkesan.</p>
          <?php if (isset($_SESSION['user'])): ?>
            <a href="layanan/service-details.php" class="service-link">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php else: ?>
            <a href="#" class="service-link" data-bs-toggle="modal" data-bs-target="#loginModal">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Layanan 9: Special Effect / Stage SFX -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-mic"></i></div>
          <h3>Special Effect / Stage SFX</h3>
          <p>Kami menghadirkan efek panggung profesional untuk menambah keseruan dan kemegahan acara kamu.</p>
          <?php if (isset($_SESSION['user'])): ?>
            <a href="layanan/service-details.php" class="service-link">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php else: ?>
            <a href="#" class="service-link" data-bs-toggle="modal" data-bs-target="#loginModal">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Layanan 10: Photobox APM -->
      <div class="col-lg-4 col-md-6">
        <div class="service-card">
          <div class="service-icon"><i class="bi bi-mic"></i></div>
          <h3>Photobox APM</h3>
          <p>Kami menyediakan photobox modern dengan hasil cetak instan untuk melengkapi keseruan acara kamu.</p>
          <?php if (isset($_SESSION['user'])): ?>
            <a href="layanan/service-details.php" class="service-link">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php else: ?>
            <a href="#" class="service-link" data-bs-toggle="modal" data-bs-target="#loginModal">Learn More <i class="bi bi-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

<!-- Modal Login -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    // Tampilkan pesan reset jika ada di URL
                    if (isset($_GET['reset_message'])) {
                        echo '<div class="alert ' . (strpos($_GET['reset_message'], 'Gagal') !== false || strpos($_GET['reset_message'], 'tidak') !== false ? 'alert-danger' : 'alert-success') . '" role="alert">';
                        echo htmlspecialchars(urldecode($_GET['reset_message']));
                        echo '</div>';
                    }
                    if (!empty($message)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
                        <!-- Tampilkan link Lupa Password hanya jika ada pesan error -->
                        <div class="text-center mt-2">
                            <a href="http://localhost/Artefax/view/forgot_password.php" class="link-primary">Lupa Password?</a>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="login_submit" value="1">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                        <div class="text-center mt-3">
                            <a href="view/register.php" class="link-primary">Belum punya akun? Register</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</section>
      <!-- End Services Section -->

      <!-- Features Section -->
      <section id="features" class="features section">
        <div class="container">
          <div class="features-grid">
            <div class="features-card">
              <div class="icon-wrapper">
                <i class="bi bi-laptop"></i>
              </div>
              <h3>Streamlined Workflow Solution</h3>
              <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.</p>
              <div class="features-list">
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Integrated development environment</span>
                </div>
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Cloud-based collaborative tools</span>
                </div>
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Automated testing procedures</span>
                </div>
              </div>
              <div class="image-container">
                <img src="assets/img/illustration/illustration-14.webp" alt="Streamlined Workflow" class="img-fluid" />
              </div>
            </div>

            <div class="features-card">
              <div class="icon-wrapper">
                <i class="bi bi-graph-up"></i>
              </div>
              <h3>Performance Analytics</h3>
              <p>Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel.</p>
              <div class="features-list">
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Real-time data visualization</span>
                </div>
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Custom report generation</span>
                </div>
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Predictive analysis models</span>
                </div>
              </div>
              <div class="image-container">
                <img src="assets/img/illustration/illustration-6.webp" alt="Performance Analytics" class="img-fluid" />
              </div>
            </div>

            <div class="features-card">
              <div class="icon-wrapper">
                <i class="bi bi-shield-lock"></i>
              </div>
              <h3>Enterprise Security Framework</h3>
              <p>Quisque velit nisi, pretium ut lacinia in, elementum id enim. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar.</p>
              <div class="features-list">
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Multi-factor authentication</span>
                </div>
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>End-to-end encryption standard</span>
                </div>
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Automated security audits</span>
                </div>
              </div>
              <div class="image-container">
                <img src="assets/img/illustration/illustration-7.webp" alt="Security Framework" class="img-fluid" />
              </div>
            </div>

            <div class="features-card">
              <div class="icon-wrapper">
                <i class="bi bi-people"></i>
              </div>
              <h3>Collaborative Team Environment</h3>
              <p>Praesent sapien massa, convallis a pellentesque nec, egestas non nisi. Cras ultricies ligula sed magna dictum porta.</p>
              <div class="features-list">
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Shared workspace functionality</span>
                </div>
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Real-time communication tools</span>
                </div>
                <div class="feature-item">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Progress tracking dashboards</span>
                </div>
              </div>
              <div class="image-container">
                <img src="assets/img/illustration/illustration-8.webp" alt="Team Environment" class="img-fluid" />
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
          <p>Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit</p>
        </div>
        <!-- End Section Title -->

        <div class="container">
          <div class="isotope-layout" data-default-filter="*" data-layout="fitRows" data-sort="original-order">
            <div class="portfolio-filters-wrapper">
              <ul class="portfolio-filters isotope-filters">
                <li data-filter="*" class="filter-active">All Projects</li>
                <li data-filter=".filter-branding">Branding</li>
                <li data-filter=".filter-web">Web Design</li>
                <li data-filter=".filter-print">Print Design</li>
                <li data-filter=".filter-motion">Motion</li>
              </ul>
            </div>

            <div class="row gy-4 portfolio-grid isotope-container">
              <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-branding">
                <div class="portfolio-card">
                  <div class="image-container">
                    <img src="assets/img/portfolio/portfolio-3.webp" class="img-fluid" alt="Brand Identity" loading="lazy" />
                    <div class="overlay">
                      <div class="overlay-content">
                        <a href="assets/img/portfolio/portfolio-3.webp" class="glightbox zoom-link" title="Brand Identity Project">
                          <i class="bi bi-zoom-in"></i>
                        </a>
                        <a href="portfolio-details.html" class="details-link" title="View Project Details">
                          <i class="bi bi-arrow-right"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                  <div class="content">
                    <h3>Brand Identity</h3>
                    <p>Corporate branding and visual identity system</p>
                  </div>
                </div>
              </div>
              <!-- End Portfolio Item -->

              <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-web">
                <div class="portfolio-card">
                  <div class="image-container">
                    <img src="assets/img/portfolio/portfolio-7.webp" class="img-fluid" alt="E-commerce Platform" loading="lazy" />
                    <div class="overlay">
                      <div class="overlay-content">
                        <a href="assets/img/portfolio/portfolio-7.webp" class="glightbox zoom-link" title="E-commerce Platform">
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

              <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-print">
                <div class="portfolio-card">
                  <div class="image-container">
                    <img src="assets/img/portfolio/portfolio-portrait-5.webp" class="img-fluid" alt="Magazine Design" loading="lazy" />
                    <div class="overlay">
                      <div class="overlay-content">
                        <a href="assets/img/portfolio/portfolio-portrait-5.webp" class="glightbox zoom-link" title="Magazine Design">
                          <i class="bi bi-zoom-in"></i>
                        </a>
                        <a href="portfolio-details.html" class="details-link" title="View Project Details">
                          <i class="bi bi-arrow-right"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                  <div class="content">
                    <h3>Magazine Design</h3>
                    <p>Editorial layout and typography</p>
                  </div>
                </div>
              </div>
              <!-- End Portfolio Item -->

              <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-motion">
                <div class="portfolio-card">
                  <div class="image-container">
                    <img src="assets/img/portfolio/portfolio-8.webp" class="img-fluid" alt="Motion Graphics" loading="lazy" />
                    <div class="overlay">
                      <div class="overlay-content">
                        <a href="assets/img/portfolio/portfolio-8.webp" class="glightbox zoom-link" title="Motion Graphics">
                          <i class="bi bi-zoom-in"></i>
                        </a>
                        <a href="portfolio-details.html" class="details-link" title="View Project Details">
                          <i class="bi bi-arrow-right"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                  <div class="content">
                    <h3>Motion Graphics</h3>
                    <p>Animated visual storytelling</p>
                  </div>
                </div>
              </div>
              <!-- End Portfolio Item -->

              <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-branding">
                <div class="portfolio-card">
                  <div class="image-container">
                    <img src="assets/img/portfolio/portfolio-9.webp" class="img-fluid" alt="Logo Collection" loading="lazy" />
                    <div class="overlay">
                      <div class="overlay-content">
                        <a href="assets/img/portfolio/portfolio-9.webp" class="glightbox zoom-link" title="Logo Collection">
                          <i class="bi bi-zoom-in"></i>
                        </a>
                        <a href="portfolio-details.html" class="details-link" title="View Project Details">
                          <i class="bi bi-arrow-right"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                  <div class="content">
                    <h3>Logo Collection</h3>
                    <p>Diverse brand mark explorations</p>
                  </div>
                </div>
              </div>
              <!-- End Portfolio Item -->

              <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-web">
                <div class="portfolio-card">
                  <div class="image-container">
                    <img src="assets/img/portfolio/portfolio-portrait-8.webp" class="img-fluid" alt="Mobile App Design" loading="lazy" />
                    <div class="overlay">
                      <div class="overlay-content">
                        <a href="assets/img/portfolio/portfolio-portrait-8.webp" class="glightbox zoom-link" title="Mobile App Design">
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

              <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-print">
                <div class="portfolio-card">
                  <div class="image-container">
                    <img src="assets/img/portfolio/portfolio-10.webp" class="img-fluid" alt="Packaging Design" loading="lazy" />
                    <div class="overlay">
                      <div class="overlay-content">
                        <a href="assets/img/portfolio/portfolio-10.webp" class="glightbox zoom-link" title="Packaging Design">
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

              <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-motion">
                <div class="portfolio-card">
                  <div class="image-container">
                    <img src="assets/img/portfolio/portfolio-11.webp" class="img-fluid" alt="Brand Animation" loading="lazy" />
                    <div class="overlay">
                      <div class="overlay-content">
                        <a href="assets/img/portfolio/portfolio-11.webp" class="glightbox zoom-link" title="Brand Animation">
                          <i class="bi bi-zoom-in"></i>
                        </a>
                        <a href="portfolio-details.html" class="details-link" title="View Project Details">
                          <i class="bi bi-arrow-right"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                  <div class="content">
                    <h3>Brand Animation</h3>
                    <p>Dynamic brand identity systems</p>
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
          <h2>How We Work</h2>
          <p>Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit</p>
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
                  <h3>Discovery</h3>
                  <p>Understanding your business goals and requirements through in-depth analysis and consultation sessions.</p>
                </div>
              </div>

              <div class="col-lg-3 col-md-6">
                <div class="step-item">
                  <div class="step-circle">
                    <span>2</span>
                  </div>
                  <h3>Planning</h3>
                  <p>Creating detailed project roadmaps and strategies aligned with your objectives and timeline requirements.</p>
                </div>
              </div>

              <div class="col-lg-3 col-md-6">
                <div class="step-item">
                  <div class="step-circle">
                    <span>3</span>
                  </div>
                  <h3>Execution</h3>
                  <p>Implementing solutions with precision while maintaining transparent communication throughout the process.</p>
                </div>
              </div>

              <div class="col-lg-3 col-md-6">
                <div class="step-item">
                  <div class="step-circle">
                    <span>4</span>
                  </div>
                  <h3>Delivery</h3>
                  <p>Finalizing implementations and providing comprehensive support to ensure long-term success.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- /How We Work Section -->

      <!-- Tabs Section -->
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
                        <h6>Innovation</h6>
                        <small>Creative solutions</small>
                      </div>
                    </div>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" data-bs-toggle="tab" data-bs-target="#tabs-tab-2">
                    <div class="tab-content-preview">
                      <span class="tab-number">02</span>
                      <div class="tab-text">
                        <h6>Strategy</h6>
                        <small>Business growth</small>
                      </div>
                    </div>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" data-bs-toggle="tab" data-bs-target="#tabs-tab-3">
                    <div class="tab-content-preview">
                      <span class="tab-number">03</span>
                      <div class="tab-text">
                        <h6>Performance</h6>
                        <small>Optimal results</small>
                      </div>
                    </div>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" data-bs-toggle="tab" data-bs-target="#tabs-tab-4">
                    <div class="tab-content-preview">
                      <span class="tab-number">04</span>
                      <div class="tab-text">
                        <h6>Integration</h6>
                        <small>Seamless workflow</small>
                      </div>
                    </div>
                  </a>
                </li>
              </ul>
            </div>

            <div class="tab-content">
              <div class="tab-pane fade active show" id="tabs-tab-1">
                <div class="row align-items-center">
                  <div class="col-lg-6">
                    <div class="content-area">
                      <div class="content-badge">
                        <i class="bi bi-lightbulb"></i>
                        <span>Innovation Hub</span>
                      </div>
                      <h3>Revolutionary Design Thinking</h3>
                      <p>Sed ut perspiciatis unde omnis natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p>

                      <div class="highlight-stats">
                        <div class="stat-item">
                          <span class="stat-value">145%</span>
                          <span class="stat-label">Innovation Rate</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">28K</span>
                          <span class="stat-label">Ideas Generated</span>
                        </div>
                      </div>

                      <div class="feature-points">
                        <div class="point-item">
                          <i class="bi bi-arrow-right"></i>
                          <span>Nemo enim ipsam voluptatem quia voluptas sit</span>
                        </div>
                        <div class="point-item">
                          <i class="bi bi-arrow-right"></i>
                          <span>Aspernatur aut odit fugit sed quia consequuntur</span>
                        </div>
                        <div class="point-item">
                          <i class="bi bi-arrow-right"></i>
                          <span>Magni dolores eos qui ratione voluptatem</span>
                        </div>
                      </div>

                      <a href="#" class="explore-link"> Explore Innovation <i class="bi bi-arrow-up-right"></i> </a>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="visual-content">
                      <img src="assets/img/features/features-2.webp" alt="" class="img-fluid" />
                      <div class="floating-element">
                        <div class="floating-card">
                          <i class="bi bi-lightning-charge"></i>
                          <div class="card-info">
                            <span>Speed</span>
                            <strong>3x Faster</strong>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="tabs-tab-2">
                <div class="row align-items-center">
                  <div class="col-lg-6">
                    <div class="content-area">
                      <div class="content-badge">
                        <i class="bi bi-compass"></i>
                        <span>Strategic Planning</span>
                      </div>
                      <h3>Data-Driven Business Strategy</h3>
                      <p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.</p>

                      <div class="highlight-stats">
                        <div class="stat-item">
                          <span class="stat-value">234%</span>
                          <span class="stat-label">Growth Rate</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">156</span>
                          <span class="stat-label">Strategies</span>
                        </div>
                      </div>

                      <div class="feature-points">
                        <div class="point-item">
                          <i class="bi bi-arrow-right"></i>
                          <span>Similique sunt in culpa qui officia deserunt</span>
                        </div>
                        <div class="point-item">
                          <i class="bi bi-arrow-right"></i>
                          <span>Mollitia animi id est laborum et dolorum fuga</span>
                        </div>
                        <div class="point-item">
                          <i class="bi bi-arrow-right"></i>
                          <span>Et harum quidem rerum facilis est expedita</span>
                        </div>
                      </div>

                      <a href="#" class="explore-link"> View Strategy <i class="bi bi-arrow-up-right"></i> </a>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="visual-content">
                      <img src="assets/img/features/features-4.webp" alt="" class="img-fluid" />
                      <div class="floating-element">
                        <div class="floating-card">
                          <i class="bi bi-graph-up-arrow"></i>
                          <div class="card-info">
                            <span>Growth</span>
                            <strong>+189% ROI</strong>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="tabs-tab-3">
                <div class="row align-items-center">
                  <div class="col-lg-6">
                    <div class="content-area">
                      <div class="content-badge">
                        <i class="bi bi-speedometer2"></i>
                        <span>High Performance</span>
                      </div>
                      <h3>Optimized System Performance</h3>
                      <p>Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet voluptates repudiandae sint et molestiae non recusandae itaque earum rerum hic tenetur sapiente delectus.</p>

                      <div class="highlight-stats">
                        <div class="stat-item">
                          <span class="stat-value">99.8%</span>
                          <span class="stat-label">System Uptime</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">2.4s</span>
                          <span class="stat-label">Load Time</span>
                        </div>
                      </div>

                      <div class="feature-points">
                        <div class="point-item">
                          <i class="bi bi-arrow-right"></i>
                          <span>Ut aut reiciendis voluptatibus maiores alias</span>
                        </div>
                        <div class="point-item">
                          <i class="bi bi-arrow-right"></i>
                          <span>Consequatur aut perferendis doloribus asperiores</span>
                        </div>
                        <div class="point-item">
                          <i class="bi bi-arrow-right"></i>
                          <span>Repellat nam libero tempore cum soluta nobis</span>
                        </div>
                      </div>

                      <a href="#" class="explore-link"> Check Performance <i class="bi bi-arrow-up-right"></i> </a>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="visual-content">
                      <img src="assets/img/features/features-1.webp" alt="" class="img-fluid" />
                      <div class="floating-element">
                        <div class="floating-card">
                          <i class="bi bi-cpu"></i>
                          <div class="card-info">
                            <span>Power</span>
                            <strong>128 Cores</strong>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="tabs-tab-4">
                <div class="row align-items-center">
                  <div class="col-lg-6">
                    <div class="content-area">
                      <div class="content-badge">
                        <i class="bi bi-puzzle"></i>
                        <span>Smart Integration</span>
                      </div>
                      <h3>Seamless Workflow Integration</h3>
                      <p>Eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus omnis voluptas assumenda est omnis dolor repellendus temporibus autem quibusdam et aut officiis debitis.</p>

                      <div class="highlight-stats">
                        <div class="stat-item">
                          <span class="stat-value">450+</span>
                          <span class="stat-label">Integrations</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">85%</span>
                          <span class="stat-label">Automation</span>
                        </div>
                      </div>

                      <div class="feature-points">
                        <div class="point-item">
                          <i class="bi bi-arrow-right"></i>
                          <span>Rerum necessitatibus saepe eveniet voluptates</span>
                        </div>
                        <div class="point-item">
                          <i class="bi bi-arrow-right"></i>
                          <span>Repudiandae sint et molestiae non recusandae</span>
                        </div>
                        <div class="point-item">
                          <i class="bi bi-arrow-right"></i>
                          <span>Itaque earum rerum hic tenetur sapiente</span>
                        </div>
                      </div>

                      <a href="#" class="explore-link"> Start Integration <i class="bi bi-arrow-up-right"></i> </a>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="visual-content">
                      <img src="assets/img/features/features-5.webp" alt="" class="img-fluid" />
                      <div class="floating-element">
                        <div class="floating-card">
                          <i class="bi bi-link-45deg"></i>
                          <div class="card-info">
                            <span>Connected</span>
                            <strong>24/7 Sync</strong>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- /Tabs Section -->

      <!-- Testimonials Section -->
      <section id="testimonials" class="testimonials section">
        <!-- Section Title -->
        <div class="container section-title">
          <h2>Testimonials</h2>
          <p>Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit</p>
        </div>
        <!-- End Section Title -->

        <div class="container">
          <div class="testimonial-slider swiper init-swiper">
            <script type="application/json" class="swiper-config">
              {
                "loop": true,
                "speed": 600,
                "autoplay": {
                  "delay": 4000
                },
                "slidesPerView": 1,
                "spaceBetween": 30,
                "navigation": {
                  "nextEl": ".swiper-button-next",
                  "prevEl": ".swiper-button-prev"
                },
                "breakpoints": {
                  "768": {
                    "slidesPerView": 2
                  },
                  "1200": {
                    "slidesPerView": 3
                  }
                }
              }
            </script>

            <div class="swiper-wrapper">
              <!-- Testimonial Slide 1 -->
              <div class="swiper-slide">
                <div class="testimonial-item">
                  <div class="testimonial-header">
                    <img src="assets/img/person/person-f-12.webp" alt="Client" class="img-fluid" loading="lazy" />
                    <div class="rating">
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                    </div>
                  </div>
                  <div class="testimonial-body">
                    <p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum consectetur adipiscing elit sed eiusmod tempor.</p>
                  </div>
                  <div class="testimonial-footer">
                    <h5>Jessica Martinez</h5>
                    <span>UX Designer</span>
                    <div class="quote-icon">
                      <i class="bi bi-chat-quote-fill"></i>
                    </div>
                  </div>
                </div>
              </div>
              <!-- End Testimonial Slide -->

              <!-- Testimonial Slide 2 -->
              <div class="swiper-slide">
                <div class="testimonial-item">
                  <div class="testimonial-header">
                    <img src="assets/img/person/person-m-8.webp" alt="Client" class="img-fluid" loading="lazy" />
                    <div class="rating">
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                    </div>
                  </div>
                  <div class="testimonial-body">
                    <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt in culpa.</p>
                  </div>
                  <div class="testimonial-footer">
                    <h5>David Rodriguez</h5>
                    <span>Software Engineer</span>
                    <div class="quote-icon">
                      <i class="bi bi-chat-quote-fill"></i>
                    </div>
                  </div>
                </div>
              </div>
              <!-- End Testimonial Slide -->

              <!-- Testimonial Slide 3 -->
              <div class="swiper-slide">
                <div class="testimonial-item">
                  <div class="testimonial-header">
                    <img src="assets/img/person/person-f-6.webp" alt="Client" class="img-fluid" loading="lazy" />
                    <div class="rating">
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                    </div>
                  </div>
                  <div class="testimonial-body">
                    <p>Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam quis nostrud.</p>
                  </div>
                  <div class="testimonial-footer">
                    <h5>Amanda Wilson</h5>
                    <span>Creative Director</span>
                    <div class="quote-icon">
                      <i class="bi bi-chat-quote-fill"></i>
                    </div>
                  </div>
                </div>
              </div>
              <!-- End Testimonial Slide -->

              <!-- Testimonial Slide 4 -->
              <div class="swiper-slide">
                <div class="testimonial-item">
                  <div class="testimonial-header">
                    <img src="assets/img/person/person-m-12.webp" alt="Client" class="img-fluid" loading="lazy" />
                    <div class="rating">
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                    </div>
                  </div>
                  <div class="testimonial-body">
                    <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium totam rem aperiam eaque ipsa quae ab illo inventore veritatis.</p>
                  </div>
                  <div class="testimonial-footer">
                    <h5>Ryan Thompson</h5>
                    <span>Business Analyst</span>
                    <div class="quote-icon">
                      <i class="bi bi-chat-quote-fill"></i>
                    </div>
                  </div>
                </div>
              </div>
              <!-- End Testimonial Slide -->

              <!-- Testimonial Slide 5 -->
              <div class="swiper-slide">
                <div class="testimonial-item">
                  <div class="testimonial-header">
                    <img src="assets/img/person/person-f-10.webp" alt="Client" class="img-fluid" loading="lazy" />
                    <div class="rating">
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                      <i class="bi bi-star-fill"></i>
                    </div>
                  </div>
                  <div class="testimonial-body">
                    <p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi.</p>
                  </div>
                  <div class="testimonial-footer">
                    <h5>Rachel Chen</h5>
                    <span>Project Manager</span>
                    <div class="quote-icon">
                      <i class="bi bi-chat-quote-fill"></i>
                    </div>
                  </div>
                </div>
              </div>
              <!-- End Testimonial Slide -->
            <div class="swiper-navigation">
              <div class="swiper-button-prev"></div>
              <div class="swiper-button-next"></div>
            </div>
          </div>
        </div>
      </section>
      <!-- /Testimonials Section -->

      <!-- Faq Section -->
      <section id="faq" class="faq section">
        <!-- Section Title -->
        <div class="container section-title">
          <h2>Frequently Asked Questions</h2>
          <p>Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit</p>
        </div>
        <!-- End Section Title -->

        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-9">
              <div class="faq-wrapper">
                <div class="faq-item faq-active">
                  <div class="faq-header">
                    <span class="faq-number">01</span>
                    <h4>Donec sollicitudin molestie malesuada proin eget tortor?</h4>
                    <div class="faq-toggle">
                      <i class="bi bi-plus"></i>
                      <i class="bi bi-dash"></i>
                    </div>
                  </div>
                  <div class="faq-content">
                    <div class="content-inner">
                      <p>Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Donec rutrum congue leo eget malesuada. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae.</p>
                    </div>
                  </div>
                </div>
                <!-- End FAQ Item -->

                <div class="faq-item">
                  <div class="faq-header">
                    <span class="faq-number">02</span>
                    <h4>Sed porttitor lectus nibh vivamus magna justo?</h4>
                    <div class="faq-toggle">
                      <i class="bi bi-plus"></i>
                      <i class="bi bi-dash"></i>
                    </div>
                  </div>
                  <div class="faq-content">
                    <div class="content-inner">
                      <p>Nulla porttitor accumsan tincidunt. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Cras ultricies ligula sed magna dictum porta. Vivamus suscipit tortor eget felis porttitor volutpat.</p>
                    </div>
                  </div>
                </div>
                <!-- End FAQ Item -->

                <div class="faq-item">
                  <div class="faq-header">
                    <span class="faq-number">03</span>
                    <h4>Pellentesque habitant morbi tristique senectus?</h4>
                    <div class="faq-toggle">
                      <i class="bi bi-plus"></i>
                      <i class="bi bi-dash"></i>
                    </div>
                  </div>
                  <div class="faq-content">
                    <div class="content-inner">
                      <p>Quisque velit nisi, pretium ut lacinia in, elementum id enim. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Donec sollicitudin molestie malesuada.</p>
                    </div>
                  </div>
                </div>
                <!-- End FAQ Item -->

                <div class="faq-item">
                  <div class="faq-header">
                    <span class="faq-number">04</span>
                    <h4>Lorem ipsum dolor sit amet consectetur adipiscing?</h4>
                    <div class="faq-toggle">
                      <i class="bi bi-plus"></i>
                      <i class="bi bi-dash"></i>
                    </div>
                  </div>
                  <div class="faq-content">
                    <div class="content-inner">
                      <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium. Totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto.</p>
                    </div>
                  </div>
                </div>
                <!-- End FAQ Item -->

                <div class="faq-item">
                  <div class="faq-header">
                    <span class="faq-number">05</span>
                    <h4>Curabitur aliquet quam id dui posuere blandit?</h4>
                    <div class="faq-toggle">
                      <i class="bi bi-plus"></i>
                      <i class="bi bi-dash"></i>
                    </div>
                  </div>
                  <div class="faq-content">
                    <div class="content-inner">
                      <p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati.</p>
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
        <!-- Section Title -->
        <div class="container section-title">
          <h2>Team</h2>
          <p>Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit</p>
        </div>
        <!-- End Section Title -->

        <div class="container">
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
            <!-- End Team Member -->

            <div class="col-lg-6">
              <div class="team-member d-flex">
                <div class="member-img">
                  <img src="assets/img/person/person-f-8.webp" class="img-fluid" alt="" loading="lazy" />
                </div>
                <div class="member-info flex-grow-1">
                  <h4>Sarah Jhonson</h4>
                  <span>Product Manager</span>
                  <p>Labore ipsam sit consequatur exercitationem rerum laboriosam laudantium aut quod dolores exercitationem ut</p>
                  <div class="social">
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-twitter-x"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                    <a href=""><i class="bi bi-youtube"></i></a>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Team Member -->

            <div class="col-lg-6">
              <div class="team-member d-flex">
                <div class="member-img">
                  <img src="assets/img/person/person-m-6.webp" class="img-fluid" alt="" loading="lazy" />
                </div>
                <div class="member-info flex-grow-1">
                  <h4>William Anderson</h4>
                  <span>CTO</span>
                  <p>Illum minima ea autem doloremque ipsum quidem quas aspernatur modi ut praesentium vel tque sed facilis at qui</p>
                  <div class="social">
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-twitter-x"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                    <a href=""><i class="bi bi-youtube"></i></a>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Team Member -->

            <div class="col-lg-6">
              <div class="team-member d-flex">
                <div class="member-img">
                  <img src="assets/img/person/person-f-4.webp" class="img-fluid" alt="" loading="lazy" />
                </div>
                <div class="member-info flex-grow-1">
                  <h4>Amanda Jepson</h4>
                  <span>Accountant</span>
                  <p>Magni voluptatem accusamus assumenda cum nisi aut qui dolorem voluptate sed et veniam quasi quam consectetur</p>
                  <div class="social">
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-twitter-x"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                    <a href=""><i class="bi bi-youtube"></i></a>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Team Member -->

            <div class="col-lg-6">
              <div class="team-member d-flex">
                <div class="member-img">
                  <img src="assets/img/person/person-m-12.webp" class="img-fluid" alt="" loading="lazy" />
                </div>
                <div class="member-info flex-grow-1">
                  <h4>Brian Doe</h4>
                  <span>Marketing</span>
                  <p>Qui consequuntur quos accusamus magnam quo est molestiae eius laboriosam sunt doloribus quia impedit laborum velit</p>
                  <div class="social">
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-twitter-x"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                    <a href=""><i class="bi bi-youtube"></i></a>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Team Member -->

            <div class="col-lg-6">
              <div class="team-member d-flex">
                <div class="member-img">
                  <img src="assets/img/person/person-f-9.webp" class="img-fluid" alt="" loading="lazy" />
                </div>
                <div class="member-info flex-grow-1">
                  <h4>Josepha Palas</h4>
                  <span>Operation</span>
                  <p>Sint sint eveniet explicabo amet consequatur nesciunt error enim rerum earum et omnis fugit eligendi cupiditate vel</p>
                  <div class="social">
                    <a href=""><i class="bi bi-facebook"></i></a>
                    <a href=""><i class="bi bi-twitter-x"></i></a>
                    <a href=""><i class="bi bi-linkedin"></i></a>
                    <a href=""><i class="bi bi-youtube"></i></a>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Team Member -->
          </div>
        </div>
      </section>
      <!-- /Team Section -->

      <!-- Contact Section -->
      <section id="contact" class="contact section">
        <!-- Section Title -->
        <div class="container section-title">
          <h2>Contact</h2>
          <p>Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit</p>
        </div>
        <!-- End Section Title -->

        <div class="container">
          <div class="row align-items-stretch">
            <div class="col-lg-7 order-lg-1 order-2">
              <div class="contact-form-container">
                <div class="form-intro">
                  <h2>Let's Start a Conversation</h2>
                  <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat.</p>
                </div>

                <form action="forms/contact.php" method="post" class="php-email-form contact-form">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-field">
                        <input type="text" name="name" class="form-input" id="userName" placeholder="Your Name" required="" />
                        <label for="userName" class="field-label">Name</label>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-field">
                        <input type="email" class="form-input" name="email" id="userEmail" placeholder="Your Email" required="" />
                        <label for="userEmail" class="field-label">Email</label>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-field">
                        <input type="tel" class="form-input" name="phone" id="userPhone" placeholder="Your Phone" />
                        <label for="userPhone" class="field-label">Phone</label>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-field">
                        <input type="text" class="form-input" name="subject" id="messageSubject" placeholder="Subject" required="" />
                        <label for="messageSubject" class="field-label">Subject</label>
                      </div>
                    </div>
                  </div>

                  <div class="form-field message-field">
                    <textarea class="form-input message-input" name="message" id="userMessage" rows="5" placeholder="Tell us about your project" required=""></textarea>
                    <label for="userMessage" class="field-label">Message</label>
                  </div>

                  <div class="my-3">
                    <div class="loading">Loading</div>
                    <div class="error-message"></div>
                    <div class="sent-message">Your message has been sent. Thank you!</div>
                  </div>

                  <button type="submit" class="send-button">
                    Send Message
                    <span class="button-arrow">→</span>
                  </button>
                </form>
              </div>
            </div>

            <div class="col-lg-5 order-lg-2 order-1">
              <div class="contact-sidebar">
                <div class="contact-header">
                  <h3>Get in Touch</h3>
                  <p>Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam quis nostrud.</p>
                </div>

                <div class="contact-methods">
                  <div class="contact-method">
                    <div class="contact-icon">
                      <i class="bi bi-geo-alt"></i>
                    </div>
                    <div class="contact-details">
                      <span class="method-label">Address</span>
                      <p>892 Park Avenue, Manhattan<br />New York, NY 10075</p>
                    </div>
                  </div>

                  <div class="contact-method">
                    <div class="contact-icon">
                      <i class="bi bi-envelope"></i>
                    </div>
                    <div class="contact-details">
                      <span class="method-label">Email</span>
                      <p>hello@businessdemo.com</p>
                    </div>
                  </div>

                  <div class="contact-method">
                    <div class="contact-icon">
                      <i class="bi bi-telephone"></i>
                    </div>
                    <div class="contact-details">
                      <span class="method-label">Phone</span>
                      <p>+1 (555) 789-2468</p>
                    </div>
                  </div>

                  <div class="contact-method">
                    <div class="contact-icon">
                      <i class="bi bi-clock"></i>
                    </div>
                    <div class="contact-details">
                      <span class="method-label">Hours</span>
                      <p>Monday - Friday: 9AM - 6PM<br />Saturday: 10AM - 4PM</p>
                    </div>
                  </div>
                </div>

                <div class="connect-section">
                  <span class="connect-label">Connect with us</span>
                  <div class="social-links">
                    <a href="#" class="social-link">
                      <i class="bi bi-linkedin"></i>
                    </a>
                    <a href="#" class="social-link">
                      <i class="bi bi-twitter-x"></i>
                    </a>
                    <a href="#" class="social-link">
                      <i class="bi bi-instagram"></i>
                    </a>
                    <a href="#" class="social-link">
                      <i class="bi bi-facebook"></i>
                    </a>
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
              <p class="mb-4">Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae. Donec velit neque auctor sit amet aliquam vel ullamcorper sit amet ligula.</p>

              <div class="newsletter-form">
                <h5>Stay Updated</h5>
                <form action="forms/newsletter.php" method="post" class="php-email-form">
                  <div class="input-group">
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required="" />
                    <button type="submit" class="btn-subscribe">
                      <i class="bi bi-send"></i>
                    </button>
                  </div>
                  <div class="loading">Loading</div>
                  <div class="error-message"></div>
                  <div class="sent-message">Thank you for subscribing!</div>
                </form>
              </div>
            </div>
          </div>

          <div class="col-lg-2 col-6">
            <div class="footer-links">
              <h4>Company</h4>
              <ul>
                <li>
                  <a href="#"><i class="bi bi-chevron-right"></i> About</a>
                </li>
                <li>
                  <a href="#"><i class="bi bi-chevron-right"></i> Careers</a>
                </li>
                <li>
                  <a href="#"><i class="bi bi-chevron-right"></i> Press</a>
                </li>
                <li>
                  <a href="#"><i class="bi bi-chevron-right"></i> Blog</a>
                </li>
                <li>
                  <a href="#"><i class="bi bi-chevron-right"></i> Contact</a>
                </li>
              </ul>
            </div>
          </div>

          <div class="col-lg-2 col-6">
            <div class="footer-links">
              <h4>Solutions</h4>
              <ul>
                <li>
                  <a href="#"><i class="bi bi-chevron-right"></i> Digital Strategy</a>
                </li>
                <li>
                  <a href="#"><i class="bi bi-chevron-right"></i> Cloud Computing</a>
                </li>
                <li>
                  <a href="#"><i class="bi bi-chevron-right"></i> Data Analytics</a>
                </li>
                <li>
                  <a href="#"><i class="bi bi-chevron-right"></i> AI Solutions</a>
                </li>
                <li>
                  <a href="#"><i class="bi bi-chevron-right"></i> Cybersecurity</a>
                </li>
              </ul>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="footer-contact">
              <h4>Get in Touch</h4>
              <div class="contact-item">
                <div class="contact-icon">
                  <i class="bi bi-geo-alt"></i>
                </div>
                <div class="contact-info">
                  <p>2847 Maple Avenue<br />Los Angeles, CA 90210<br />United States</p>
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
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
                <a href="#"><i class="bi bi-youtube"></i></a>
                <a href="#"><i class="bi bi-github"></i></a>
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
                <p>© <span>Copyright</span> <strong class="px-1 sitename">MyWebsite</strong> <span>All Rights Reserved</span></p>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="footer-bottom-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Cookie Policy</a>
              </div>
              <div class="credits">

                Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
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
