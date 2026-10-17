<?php
header('Content-Type: application/json');
require '../middleware/admin.php'; // Hanya Admin
require '../config/koneksi.php';

try {
    $stmt  = $pdo->query("SELECT nim, username, nama_lengkap, email, role, jurusan, kelas, no_telepon FROM users");
    $users = $stmt->fetchAll();

    echo json_encode([
        "status" => "sukses",
        "data"   => $users
    ]);
} catch (PDOException $e) {
    error_log("[TITIPIN] users/read error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Gagal ambil data user."]);
}
?>