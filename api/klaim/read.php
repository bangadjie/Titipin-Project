<?php
header('Content-Type: application/json');
require '../middleware/admin.php'; // Hanya Admin
require '../config/koneksi.php';
require '../config/klaim.php';

try {
    expire_claims($pdo);

    $query = "
        SELECT k.id_klaim,
               k.kode_tiket,
               k.item_id,
               k.claimer_id,
               k.admin_id,
               k.bukti_kepemilikan,
               k.bukti_serah_terima,
               k.status_klaim,
               k.redeem_expires_at,
               k.redeem_used_at,
               k.alasan_penolakan,
               b.nama_barang,
               b.foto AS foto_barang,
               b.jenis,
               b.status AS status_barang,
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