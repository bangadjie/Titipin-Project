<?php
header('Content-Type: application/json');
require '../middleware/admin.php'; // Hanya Admin
require '../config/koneksi.php';

try {
    $query = "
        SELECT k.*, 
               b.nama_barang, b.foto AS foto_barang,
               u.nama_lengkap AS nama_claimer
        FROM klaim k 
        JOIN barang b ON k.item_id = b.id_barang 
        JOIN users u ON k.claimer_id = u.nim 
        ORDER BY k.id_klaim DESC
    ";

    $stmt  = $pdo->query($query);
    $klaim = $stmt->fetchAll();

    echo json_encode([
        "status" => "sukses",
        "data"   => $klaim
    ]);
} catch (PDOException $e) {
    error_log("[TITIPIN] klaim/read error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Gagal mengambil data klaim."]);
}
?>