<?php
header('Content-Type: application/json');
require '../config/koneksi.php';

try {
    $stmt = $pdo->query("SELECT nim, username, nama_lengkap, email, role, jurusan, kelas, no_telepon FROM users");
    $users = $stmt->fetchAll();
    
    echo json_encode([
        "status" => "sukses",
        "data" => $users
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal ambil data: " . $e->getMessage()]);
}
?>