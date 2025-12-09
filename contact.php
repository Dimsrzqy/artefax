<?php
$receiving_email_address = 'artefaxm@gmail.com';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $name = strip_tags(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $subject = trim($_POST["subject"]);
    $message = trim($_POST["message"]);
    $phone = isset($_POST["phone"]) ? trim($_POST["phone"]) : '-';

    if ( empty($name) OR empty($message) OR !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo "Mohon lengkapi formulir dengan benar.";
        exit;
    }

    $email_content = "Nama: $name\n";
    $email_content .= "Email: $email\n";
    $email_content .= "Telepon: $phone\n\n";
    $email_content .= "Pesan:\n$message\n";


    $email_headers = "From: $name <$email>";

    // Kirim Email
    // CATATAN: Fungsi mail() hanya bekerja jika server (XAMPP/Hosting) sudah dikonfigurasi SMTP-nya.
    if (mail($receiving_email_address, $subject, $email_content, $email_headers)) {
        http_response_code(200);
        echo "OK"; // Penting! Javascript template biasanya menunggu balasan "OK"
    } else {
        http_response_code(500);
        echo "Maaf, terjadi kesalahan saat mengirim pesan.";
    }

} else {
    http_response_code(403);
    echo "Ada masalah dengan pengiriman formulir Anda, silakan coba lagi.";
}
?>