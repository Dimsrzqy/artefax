<?php
session_start(); // Start session for feedback messages
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/Users.php";

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("<p style='color:red;'>❌ Koneksi database gagal.</p>");
}

$user = new User($conn);
$karyawanList = $user->getKaryawan();

// Handle feedback messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-90680653-2"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'UA-90680653-2');
    </script>

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Meta -->
    <meta name="description" content="Responsive Bootstrap 4 Dashboard Template">
    <meta name="author" content="BootstrapDash">

    <title>Form Karyawan</title>

    <!-- Vendor CSS -->
    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
    <link href="../lib/spectrum-colorpicker/spectrum.css" rel="stylesheet">
    <link href="../lib/select2/css/select2.min.css" rel="stylesheet">
    <link href="../lib/ion-rangeslider/css/ion.rangeSlider.css" rel="stylesheet">
    <link href="../lib/ion-rangeslider/css/ion.rangeSlider.skinFlat.css" rel="stylesheet">
    <link href="../lib/amazeui-datetimepicker/css/amazeui.datetimepicker.css" rel="stylesheet">
    <link href="../lib/jquery-simple-datetimepicker/jquery.simple-dtpicker.css" rel="stylesheet">
    <link href="../lib/pickerjs/picker.min.css" rel="stylesheet">

    <!-- Azia CSS -->
    <link rel="stylesheet" href="../css/azia.css">

    <!-- Custom CSS -->
    <style>
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .custom-table th,
        .custom-table td {
            border: 1px solid #ccc;
            padding: 10px 14px;
            text-align: center;
        }

        .custom-table th {
            background-color: #3366ff;
            color: white;
            font-weight: bold;
        }

        .custom-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .custom-table tr:hover {
            background-color: #fff3cd;
            transition: background-color 0.3s;
        }

        .custom-table td {
            color: #333;
        }

        .modal {
            z-index: 10000;
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            align-items: center;
            justify-content: center;
        }

        .modal-dialog {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            position: relative;
            z-index: 10001;
            animation: fadeIn 0.3s ease-in-out;
        }

        .modal button {
            pointer-events: auto !important;
            cursor: pointer !important;
        }

        .modal input, .modal textarea {
            pointer-events: auto !important;
            user-select: auto !important;
            cursor: text !important;
            background-color: #fff !important;
            border: 1px solid #ccc !important;
            padding: 8px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Additional CSS to ensure inputs are editable */
        .form-control {
            pointer-events: auto !important;
            user-select: auto !important;
            cursor: text !important;
            background-color: #fff !important;
        }

        .modal * {
            pointer-events: auto !important;
        }

        /* Feedback messages */
        .alert-success, .alert-danger {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="az-header">
        <div class="container">
            <div class="az-header-left">
                <a href="../index.html" class="az-logo"><span></span> Artefax</a>
                <a href="" id="azMenuShow" class="az-header-menu-icon d-lg-none"><span></span></a>
            </div>
            <div class="az-header-menu">
                <div class="az-header-menu-header">
                    <a href="index.html" class="az-logo"><span></span> azia</a>
                    <a href="" class="close">&times;</a>
                </div>
                <ul class="nav">
                    <li class="nav-item">
                        <a href="../index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a>
                    </li>
                    <li class="nav-item active">
                        <a href="form-karyawan.php" class="nav-link"><i class="typcn typcn-chart-bar-outline"></i>Karyawan</a>
                    </li>
                    <li class="nav-item">
                        <a href="../form-layanan/form-layanan.php" class="nav-link"><i class="typcn typcn-chart-bar-outline"></i> Layanan</a>
                    </li>
                    <li class="nav-item">
                        <a href="form-user.php" class="nav-link"><i class="typcn typcn-chart-bar-outline"></i> Users</a>
                    </li>
                    <li class="nav-item">
              <a href="" class="nav-link with-sub"><i class="typcn typcn-book"></i> Components</a>
              <div class="az-menu-sub">
                <div class="container">
                  <div>
                    <nav class="nav">
                      <a href="elem-buttons.html" class="nav-link">Buttons</a>
                      <a href="elem-dropdown.html" class="nav-link">Dropdown</a>
                      <a href="elem-icons.html" class="nav-link">Icons</a>
                      <a href="table-basic.html" class="nav-link">Table</a>
                    </nav>
                  </div>
                </div>
                <!-- container -->
              </div>
            </li>
                </ul>
            </div>
            <div class="az-header-right">
                <a href="https://www.bootstrapdash.com/demo/azia-free/docs/documentation.html" target="_blank" class="az-header-search-link"><i class="far fa-file-alt"></i></a>
                <a href="" class="az-header-search-link"><i class="fas fa-search"></i></a>
                <div class="az-header-message">
                    <a href="#"><i class="typcn typcn-messages"></i></a>
                </div>
                <div class="dropdown az-header-notification">
                    <a href="" class="new"><i class="typcn typcn-bell"></i></a>
                    <div class="dropdown-menu">
                        <div class="az-dropdown-header mg-b-20 d-sm-none">
                            <a href="" class="az-header-arrow"><i class="icon ion-md-arrow-back"></i></a>
                        </div>
                        <h6 class="az-notification-title">Notifications</h6>
                        <p class="az-notification-text">You have 2 unread notification</p>
                        <div class="az-notification-list">
                            <div class="media new">
                                <div class="az-img-user"><img src="../img/faces/face2.jpg" alt=""></div>
                                <div class="media-body">
                                    <p>Congratulate <strong>Socrates Itumay</strong> for work anniversaries</p>
                                    <span>Mar 15 12:32pm</span>
                                </div>
                            </div>
                            <div class="media new">
                                <div class="az-img-user online"><img src="../img/faces/face3.jpg" alt=""></div>
                                <div class="media-body">
                                    <p><strong>Joyce Chua</strong> just created a new blog post</p>
                                    <span>Mar 13 04:16am</span>
                                </div>
                            </div>
                            <div class="media">
                                <div class="az-img-user"><img src="../img/faces/face4.jpg" alt=""></div>
                                <div class="media-body">
                                    <p><strong>Althea Cabardo</strong> just created a new blog post</p>
                                    <span>Mar 13 02:56am</span>
                                </div>
                            </div>
                            <div class="media">
                                <div class="az-img-user"><img src="../img/faces/face5.jpg" alt=""></div>
                                <div class="media-body">
                                    <p><strong>Adrian Monino</strong> added new comment on your photo</p>
                                    <span>Mar 12 10:40pm</span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-footer"><a href="">View All Notifications</a></div>
                    </div>
                </div>
                <div class="dropdown az-profile-menu">
                    <a href="" class="az-img-user"><img src="../img/faces/face1.jpg" alt=""></a>
                    <div class="dropdown-menu">
                        <div class="az-dropdown-header d-sm-none">
                            <a href="" class="az-header-arrow"><i class="icon ion-md-arrow-back"></i></a>
                        </div>
                        <div class="az-header-profile">
                            <div class="az-img-user">
                                <img src="../img/faces/face1.jpg" alt="">
                            </div>
                            <h6>Aziana Pechon</h6>
                            <span>Premium Member</span>
                        </div>
                        <a href="" class="dropdown-item"><i class="typcn typcn-user-outline"></i> My Profile</a>
                        <a href="" class="dropdown-item"><i class="typcn typcn-edit"></i> Edit Profile</a>
                        <a href="" class="dropdown-item"><i class="typcn typcn-time"></i> Activity Logs</a>
                        <a href="" class="dropdown-item"><i class="typcn typcn-cog-outline"></i> Account Settings</a>
                        <a href="page-signin.html" class="dropdown-item"><i class="typcn typcn-power-outline"></i> Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
        <div class="container">
            <div class="az-content-left az-content-left-components">
                <div class="component-item">
                    <label>Karyawan</label>
                    <nav class="nav flex-column">
                        <a href="form-karyawan.php" class="nav-link active">Karyawan</a>
                        <a href="absensi-karyawan.php" class="nav-link">Absensi</a>
                    </nav>
                </div>
            </div>

            <div class="az-content-body pd-lg-l-40 d-flex flex-column">
                <div class="az-content-breadcrumb">
                    <span>Data</span>
                    <span>Karyawan</span>
                </div>

                <div class="d-flex justify-content-between align-items-center mg-b-20">
                    <h4 class="tx-20 tx-bold">Daftar Karyawan</h4>
                    <button class="btn btn-primary" onclick="openTambahPopup()">
                        <i class="fas fa-plus"></i> Tambah Karyawan
                    </button>
                </div>

                <!-- Display feedback messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <div class="col-lg-12" style="max-width: 100%; padding-right: 0;">
                    <div style="width: 100%; overflow-x: auto;">
                        <?php
                        if ($karyawanList && count($karyawanList) > 0) {
                            echo "<table class='custom-table' style='width: 100%;'>
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Karyawan</th>
                                            <th>Email</th>
                                            <th>No HP</th>
                                            <th>Alamat</th>
                                            <th>Role</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>";

                            $no = 1;
                            foreach ($karyawanList as $karyawan) {
                                echo "<tr>
                                        <td>" . $no++ . "</td>
                                        <td>" . htmlspecialchars($karyawan['UserNama']) . "</td>
                                        <td>" . htmlspecialchars($karyawan['UserEmail']) . "</td>
                                        <td>" . htmlspecialchars($karyawan['UserNoHP']) . "</td>
                                        <td>" . htmlspecialchars($karyawan['UserAlamat']) . "</td>
                                        <td>" . htmlspecialchars($karyawan['UserRole']) . "</td>
                                        <td>
                                            <form action='hapus_karyawan.php' method='POST' onsubmit='return confirm(\"Apakah Anda yakin ingin menghapus karyawan ini?\");'>
                                                <input type='hidden' name='id' value='" . htmlspecialchars($karyawan['IDUser']) . "'>
                                                <button type='submit' class='btn btn-sm btn-danger'>
                                                    <i class='fas fa-trash'></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>";
                            }

                            echo "</tbody></table>";
                        } else {
                            echo "<p class='text-center'>Belum ada karyawan terdaftar.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popup Tambah -->
    <div id="popupForm" class="modal" style="display: none;">
        <div class="modal-dialog">
            <button type="button" onclick="closePopup()" style="position: absolute; top: 10px; right: 10px; border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
            <h5 id="popupTitle" class="mb-3">Tambah Karyawan</h5>
            <form id="formKaryawan" action="tambah_karyawan.php" method="POST">
                <input type="hidden" id="idUser" name="IDUser">
                <div class="form-group">
                    <label for="namaUser">Nama Karyawan</label>
                    <input type="text" id="namaUser" name="NamaUser" class="form-control" required pattern="[A-Za-z\s]{2,50}" title="Nama hanya boleh berisi huruf dan spasi, minimal 2 karakter, maksimal 50 karakter">
                </div>
                <div class="form-group">
                    <label for="emailUser">Email</label>
                    <input type="email" id="emailUser" name="Email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="Password" class="form-control" required minlength="6" title="Password harus minimal 6 karakter">
                </div>
                <div class="form-group">
                    <label for="noHP">No HP</label>
                    <input type="text" id="noHP" name="NoHP" class="form-control" required pattern="[0-9]{10,15}" title="No HP harus berisi 10-15 angka">
                </div>
                <div class="form-group">
                    <label for="alamat">Alamat</label>
                    <input type="text" id="alamat" name="Alamat" class="form-control" required minlength="5" maxlength="100">
                </div>
                <input type="hidden" name="Role" value="Karyawan">
                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-secondary mr-2" onclick="closePopup()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Minimal JavaScript for popup control
        function openTambahPopup() {
            console.log('Opening Tambah Popup');
            const popup = document.getElementById('popupForm');
            if (!popup) {
                console.error('Popup element not found');
                return;
            }
            document.getElementById('popupTitle').textContent = "Tambah Karyawan";
            document.getElementById('formKaryawan').reset();
            document.getElementById('idUser').value = "";
            popup.style.display = 'flex';
            document.getElementById('namaUser').focus();
        }

        function closePopup() {
            console.log('Closing popup');
            const popup = document.getElementById('popupForm');
            if (popup) {
                popup.style.display = 'none';
                document.getElementById('formKaryawan').reset();
            } else {
                console.error('Popup element not found');
            }
        }

        // Ensure inputs are editable
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Page loaded');
            const inputs = document.querySelectorAll('#formKaryawan input');
            inputs.forEach(input => {
                input.removeAttribute('disabled');
                input.removeAttribute('readonly');
                input.style.pointerEvents = 'auto';
                input.style.userSelect = 'auto';
                console.log(`Input ${input.id} is enabled:`, !input.hasAttribute('disabled'), !input.hasAttribute('readonly'));
            });
        });
    </script>

    <!-- Comment out azia.js to prevent interference -->
    <!-- <script src="../js/azia.js"></script> -->
</body>
</html>