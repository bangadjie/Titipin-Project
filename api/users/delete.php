<?php
header('Content-Type: application/json');
require '../middleware/admin.php'; // Hanya Admin
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Gunakan method POST!"]);
    exit;
}

$data = $_POST;
if (empty($data)) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (!is_array($data)) {
        $data = [];
    }
}

$nim = trim((string) ($data['nim'] ?? ''));

if ($nim === '') {
    echo json_encode(["status" => "error", "message" => "NIM user tidak ditemukan!"]);
    exit;
}

if ($nim === ($_SESSION['user_nim'] ?? null)) {
    echo json_encode(["status" => "error", "message" => "Tidak dapat menghapus akun sendiri saat masih login!"]);
    exit;
}

try {
    $stmtCek = $pdo->prepare("SELECT nim FROM users WHERE nim = ?");
    $stmtCek->execute([$nim]);

    if (!$stmtCek->fetch()) {
        echo json_encode(["status" => "error", "message" => "User tidak ditemukan!"]);
        exit;
    }

    $stmtDelete = $pdo->prepare("DELETE FROM users WHERE nim = ?");
    $stmtDelete->execute([$nim]);

    echo json_encode(["status" => "sukses", "message" => "User berhasil dihapus!"]);
} catch (PDOException $e) {
    error_log("[TITIPIN] users/delete error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Gagal menghapus data user."]);
}
?>