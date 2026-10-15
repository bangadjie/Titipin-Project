<?php
header('Content-Type: application/json');
require '../config/koneksi.php';

try {
    $query = "
        SELECT f.*, u.nama_lengkap AS nama_pelapor 
        FROM kerusakan_fasilitas f
        LEFT JOIN users u ON f.pelapor_id = u.nim
        ORDER BY f.tanggal_lapor DESC
    ";

    $stmt = $pdo->query($query);
    $fasilitas = $stmt->fetchAll();

    echo json_encode([
        "status" => "sukses",
        "data" => $fasilitas
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal mengambil data fasilitas: " . $e->getMessage()]);
}
