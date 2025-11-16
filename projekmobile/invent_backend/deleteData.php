<?php
include 'koneksi.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// support multiple ways: DELETE (body JSON), POST (form/json) or GET query (for simple links)
$method = $_SERVER['REQUEST_METHOD'];

$id = null;
if ($method === 'DELETE') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (isset($body['id'])) $id = intval($body['id']);
} elseif ($method === 'POST') {
    // fallback for clients that cannot send DELETE
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (isset($body['id'])) {
        $id = intval($body['id']);
    } elseif (isset($_POST['id'])) {
        $id = intval($_POST['id']);
    }
} elseif ($method === 'GET') {
    if (isset($_GET['id'])) $id = intval($_GET['id']);
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    mysqli_close($conn);
    exit;
}

if (!$id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'id diperlukan']);
    mysqli_close($conn);
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM item WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    mysqli_close($conn);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    $affected = mysqli_stmt_affected_rows($stmt);
    if ($affected > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus', 'id' => $id], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Item tidak ditemukan atau sudah terhapus']);
    }
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);