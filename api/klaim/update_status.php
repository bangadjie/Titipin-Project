<?php
header('Content-Type: application/json');
require '../middleware/admin.php'; // Hanya Admin
require '../config/koneksi.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$id_klaim     = $data['id_klaim'] ?? null;
$status_klaim = $data['status_klaim'] ?? ''; 
$admin_id     = $_SESSION['user_nim'];        

if (!$id_klaim || !$status_klaim) {
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap!"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $query = "UPDATE klaim SET status_klaim = ?, admin_id = ? WHERE id_klaim = ?";
    $stmt  = $pdo->prepare($query);
    $stmt->execute([$status_klaim, $admin_id, $id_klaim]);

    if ($status_klaim === 'Disetujui' || $status_klaim === 'Selesai') {
        $stmtCek = $pdo->prepare("SELECT item_id FROM klaim WHERE id_klaim = ?");
        $stmtCek->execute([$id_klaim]);
        $klaim = $stmtCek->fetch();

        if ($klaim) {
            $stmtBarang = $pdo->prepare("UPDATE barang SET status = 'Diklaim' WHERE id_barang = ?");
            $stmtBarang->execute([$klaim['item_id']]);
        }
    }

    $pdo->commit();

    echo json_encode(["status" => "sukses", "message" => "Status klaim berhasil diperbarui menjadi " . $status_klaim]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("[TITIPIN] klaim/update_status error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Gagal update status klaim. Semua perubahan dibatalkan."]);
}
