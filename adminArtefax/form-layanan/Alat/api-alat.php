<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . "/../../../config/koneksi.php";
require_once __DIR__ . "/../../../class/alat.php";

$db = new Database();
$conn = $db->getConnection();
$alat = new Alat($conn);

// Menangani preflight OPTIONS request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Fungsi bantu untuk output JSON
function response($status, $message, $data = null) {
    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

switch ($method) {
    case 'GET':
        // Jika ada parameter ID
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $data = $alat->readOne($id);
            if ($data) response("success", "Data ditemukan", $data);
            else response("error", "Data tidak ditemukan");
        } else {
            $data = $alat->readAll();
            response("success", "Data semua layanan", $data);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input) response("error", "Input JSON tidak valid");

        $result = $alat->create($input);
        if ($result) response("success", "Data berhasil ditambahkan");
        else response("error", "Gagal menambah data");
        break;

    case 'PUT':
        $input = json_decode(file_get_contents("php://input"), true);
        if (!isset($input['IDAlat'])) response("error", "IDAlat wajib disertakan");

        $result = $alat->update($input['IDAlat'], $input);
        if ($result) response("success", "Data berhasil diupdate");
        else response("error", "Gagal update data");
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        if (!isset($input['IDAlat'])) response("error", "IDAlat wajib disertakan");

        $result = $alat->delete($input['IDAlat']);
        if ($result) response("success", "Data berhasil dihapus");
        else response("error", "Gagal menghapus data");
        break;

    default:
        response("error", "Metode tidak diizinkan");
}
?>
