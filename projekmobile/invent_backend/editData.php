<?php
include 'koneksi.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GET ?id= to fetch a single item
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'id diperlukan']);
        exit;
    }

    $stmt = mysqli_prepare($conn, "SELECT id, nama AS barang, jumlah FROM item WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($data) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Item tidak ditemukan']);
    }

    mysqli_close($conn);
    exit;
}

// POST to update item (body JSON or form-data)
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input) {
    $input = $_POST;
}

// terima id, dan field barang atau nama serta jumlah
$id = isset($input['id']) ? intval($input['id']) : null;

$barang = null;
if (isset($input['barang'])) $barang = trim($input['barang']);
elseif (isset($input['nama'])) $barang = trim($input['nama']);

$jumlah_raw = isset($input['jumlah']) ? $input['jumlah'] : null;

$errors = [];
if (!$id) $errors[] = 'id diperlukan';
if ($barang === null || $barang === '') $errors[] = 'barang/nama diperlukan';
if ($jumlah_raw === null || $jumlah_raw === '') $errors[] = 'jumlah diperlukan';
elseif (!is_numeric($jumlah_raw)) $errors[] = 'jumlah harus angka';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'errors' => $errors, 'received' => $input], JSON_UNESCAPED_UNICODE);
    mysqli_close($conn);
    exit;
}

$jumlah = intval($jumlah_raw);

// update query (sesuaikan nama kolom bila perlu)
$stmt = mysqli_prepare($conn, "UPDATE item SET nama = ?, jumlah = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
    mysqli_close($conn);
    exit;
}

mysqli_stmt_bind_param($stmt, 'sii', $barang, $jumlah, $id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil diperbarui'], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Tidak ada perubahan pada data atau id tidak ditemukan'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => mysqli_stmt_error($stmt)], JSON_UNESCAPED_UNICODE);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);