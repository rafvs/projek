<?php
// auth.php
// Simple authentication backend for inventarisBarang using PDO + sessions + CSRF
// Place this file in /c:/xampp/htdocs/inventarisBarang/invent_backend/auth.php
// Adjust DB settings below to match your environment.

// Simple CORS handling so dev server (different origin/port) can use cookies.
// If you deploy to production on same origin, you can tighten or remove this.
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    // Allow the requesting origin to interact (for dev only). Adjust in prod.
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Preflight request
    exit;
}

session_start();

// ---------- CONFIG ----------
$dbHost = '127.0.0.1';
$dbName = 'barang';
$dbUser = 'root';
$dbPass = '';
$charset = 'utf8mb4';

// Secret code authentication
$secretCode = '123456';
// ----------------------------

$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Optional: create users table if it doesn't exist (includes username)
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ---------- CSRF ----------
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

// ---------- AUTH FUNCTIONS ----------
function register_user(PDO $pdo, $username, $password) {
    $username = trim($username);
    if (strlen($username) < 3) return ['ok' => false, 'message' => 'Username minimal 3 karakter'];
    if (strlen($password) < 6) return ['ok' => false, 'message' => 'Password minimal 6 karakter'];

    // Check exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u");
    $stmt->execute([':u' => $username]);
    if ($stmt->fetch()) return ['ok' => false, 'message' => 'Username sudah terpakai'];

    $pwHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (:u, :p)");
    $stmt->execute([':u' => $username, ':p' => $pwHash]);
    return ['ok' => true, 'message' => 'Registrasi berhasil'];
}

function login_user(PDO $pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = :u");
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();
    if (!$row) return ['ok' => false, 'message' => 'Username atau password salah'];

    if (!password_verify($password, $row['password_hash'])) {
        return ['ok' => false, 'message' => 'Username atau password salah'];
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $row['id'];
    // Ensure secret flag is not set
    unset($_SESSION['secret_authenticated']);
    return ['ok' => true, 'message' => 'Login berhasil'];
}

function login_with_secret($code) {
    global $secretCode;
    if (!is_string($code) || $code === '') {
        return ['ok' => false, 'message' => 'Kode rahasia diperlukan'];
    }
    if (!hash_equals($secretCode, $code)) {
        return ['ok' => false, 'message' => 'Kode rahasia salah'];
    }

    session_regenerate_id(true);
    // Mark session as authenticated via secret code
    $_SESSION['secret_authenticated'] = true;
    // Optionally set a sentinel user_id
    $_SESSION['user_id'] = 0;
    return ['ok' => true, 'message' => 'Login berhasil dengan kode rahasia'];
}

function logout_user() {
    // unset our flags and destroy session
    unset($_SESSION['secret_authenticated']);
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function current_user(PDO $pdo) {
    // If secret-authenticated, return a minimal user representation
    if (!empty($_SESSION['secret_authenticated'])) {
        return ['id' => 0, 'username' => 'secret_user', 'created_at' => null];
    }

    if (empty($_SESSION['user_id'])) return null;
    // If user_id is 0 but not secret_authenticated, treat as no user
    if ($_SESSION['user_id'] === 0) return null;

    $stmt = $pdo->prepare("SELECT id, username, created_at FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// ---------- HTTP API ----------
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? null;

if ($method === 'GET' && $action === 'csrf') {
    echo json_encode(['status' => 'ok', 'csrf' => get_csrf_token()]);
    exit;
}

if ($method === 'GET' && $action === 'status') {
    $user = current_user($pdo);
    if ($user) {
        echo json_encode(['status' => 'ok', 'authenticated' => true, 'user' => $user]);
    } else {
        echo json_encode(['status' => 'ok', 'authenticated' => false]);
    }
    exit;
}

if ($method === 'POST') {
    // Expecting csrf token for state-changing operations
    $csrf = $_POST['csrf'] ?? '';
    if (!verify_csrf_token($csrf)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    if ($action === 'register') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $res = register_user($pdo, $username, $password);
        echo json_encode($res['ok'] ? ['status' => 'ok', 'message' => $res['message']] : ['status' => 'error', 'message' => $res['message']]);
        exit;
    }

    if ($action === 'login') {
        // Enforce secret code authentication only (mandatory).
        $code = $_POST['code'] ?? '';
        if ($code === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Kode rahasia wajib diisi']);
            exit;
        }
        $res = login_with_secret($code);
        echo json_encode($res['ok'] ? ['status' => 'ok', 'message' => $res['message']] : ['status' => 'error', 'message' => $res['message']]);
        exit;
    }

    if ($action === 'logout') {
        logout_user();
        echo json_encode(['status' => 'ok', 'message' => 'Logout berhasil']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal']);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Metode atau aksi tidak valid']);