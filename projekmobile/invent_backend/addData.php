<?php
include 'koneksi.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// dapatkan body JSON atau fallback ke form-data
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input) {
    $input = $_POST;
}

// Normalize possible field names: 'nama', 'barang', 'name'
$barang  = null;
if (isset($input['nama'])) $barang = trim($input['nama']);
elseif (isset($input['barang'])) $barang = trim($input['barang']);
elseif (isset($input['name'])) $barang = trim($input['name']);

$jumlah  = null;
if (isset($input['jumlah'])) $jumlah = $input['jumlah'];
elseif (isset($input['qty'])) $jumlah = $input['qty'];
elseif (isset($input['quantity'])) $jumlah = $input['quantity'];

// coerce jumlah to integer when possible but keep null if empty
if ($jumlah !== null && $jumlah !== '') {
    if (!is_numeric($jumlah)) {
        $jumlah = null; // will trigger validation error below
    } else {
        $jumlah = intval($jumlah);
    }
}

// validasi sederhana
$errors = [];
if ($barang === null || $barang === '')  $errors[] = 'item (nama/barang) diperlukan';
if ($jumlah === null) $errors[] = 'jumlah diperlukan atau bukan angka';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'errors' => $errors, 'received' => $input, 'raw' => $raw], JSON_UNESCAPED_UNICODE);
    mysqli_close($conn);
    exit;
}

// query insert (sesuaikan nama kolom/tabel)
$stmt = mysqli_prepare($conn, "INSERT INTO item (nama, jumlah) VALUES (?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    mysqli_close($conn);
    exit;
}

// bind params: s = string, d = double, i = integer
mysqli_stmt_bind_param($stmt, 'si', $barang, $jumlah);

if (mysqli_stmt_execute($stmt)) {
    $insert_id = mysqli_insert_id($conn);
    http_response_code(201);
    echo json_encode(['status' => 'success', 'id' => $insert_id], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);