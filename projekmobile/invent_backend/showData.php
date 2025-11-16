<?php
include 'koneksi.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($id) {
    $stmt = mysqli_prepare($conn, "SELECT id, nama AS barang, jumlah FROM item WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($res);

    if ($data) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Item not found']);
    }

    mysqli_stmt_close($stmt);
} else {
    $sql = "SELECT id, nama AS barang, jumlah FROM item";
    if ($result = mysqli_query($conn, $sql)) {
        $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
        mysqli_free_result($result);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}

mysqli_close($conn);
