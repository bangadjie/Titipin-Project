<?php
session_start();
header('Content-Type: application/json');
require '../config/koneksi.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Data kosong!"]);
    exit;
}

$email    = $data['email'] ?? null;
$password = $data['password'] ?? null;

if (!$email || !$password) {
    echo json_encode(["status" => "error", "message" => "Email dan password wajib diisi!"]);
    exit;
}

$stmt = $pdo->prepare("SELECT nim, nama_lengkap, email, password, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);

    $_SESSION['user_nim']  = $user['nim'];
    $_SESSION['user_role'] = $user['role'];

    echo json_encode([
        "status"  => "sukses",
        "message" => "Berhasil masuk!",
        "role"    => $user['role'],
        "nama"    => $user['nama_lengkap']
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Email atau sandi salah!"]);
}
?>