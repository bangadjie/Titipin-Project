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

$email = $data['email'];
$password = $data['password'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    // Ingat siapa yang masuk
    $_SESSION['user_nim'] = $user['nim'];
    $_SESSION['user_role'] = $user['role'];
    
    echo json_encode([
        "status" => "sukses", 
        "message" => "Berhasil masuk!", 
        "role" => $user['role'],
        "nama" => $user['nama_lengkap']
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Email atau sandi salah!"]);
}
?>