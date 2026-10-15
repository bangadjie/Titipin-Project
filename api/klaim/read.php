<?php
header('Content-Type: application/json');
require '../config/koneksi.php';

try {
    // JOIN klaim, barang (untuk tahu barang apa), dan users (untuk tahu siapa yang klaim)
    $query = "
        select k.*, 
               b.nama_barang, b.foto AS foto_barang,
               u.nama_lengkap AS nama_claimer
        from klaim k 
        join barang b on k.item_id = b.id_barang 
        join users u ON k.claimer_id = u.nim 
        order by k.id_klaim desc
    ";
    
    $stmt = $pdo->query($query);
    $klaim = $stmt->fetchAll();
    
    echo json_encode([
        "status" => "sukses",
        "data" => $klaim
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal mengambil data klaim: " . $e->getMessage()]);
}
?>