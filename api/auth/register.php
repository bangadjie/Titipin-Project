<?php
header('Content-Type: application/json');
require '../config/koneksi.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Data kosong!"]);
    exit;
}

$nim = $data['nim'];
$username = $data['username'];
$nama_lengkap = $data['nama_lengkap'];
$email = $data['email'];
$password = $data['password'];
$konfirm_password = $data['konfirm_password'];
$jurusan = $data['jurusan'];
$kelas = $data['kelas'];
$no_telepon = $data['no_telepon'];

if ($password !== $konfirm_password) {
    echo json_encode(["status" => "error", "message" => "Sandi beda!"]);
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (nim, username, nama_lengkap, email, password, role, jurusan, kelas, no_telepon) VALUES (?, ?, ?, ?, ?, 'Mahasiswa', ?, ?, ?)");
    $stmt->execute([$nim, $username, $nama_lengkap, $email, $hashed_password, $jurusan, $kelas, $no_telepon]);
    
    echo json_encode(["status" => "sukses", "message" => "Orang baru masuk goa sebagai Mahasiswa!"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "NIM atau Email sudah ada!"]);
}
?>