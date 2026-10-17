<?php
header('Content-Type: application/json');
require '../middleware/admin.php'; 
require '../config/koneksi.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$id_barang = $data['id_barang'] ?? null;

if (!$id_barang) {
    echo json_encode(["status" => "error", "message" => "ID Barang tidak ditemukan!"]);
    exit;
}

try {
    $stmtCek = $pdo->prepare("SELECT foto FROM barang WHERE id_barang = ?");
    $stmtCek->execute([$id_barang]);
    $barang = $stmtCek->fetch();

    if (!$barang) {
        echo json_encode(["status" => "error", "message" => "Barang tidak ditemukan!"]);
        exit;
    }

    if ($barang['foto']) {
        $pathFoto = '../../assets/uploads/' . $barang['foto'];
        if (file_exists($pathFoto)) {
            unlink($pathFoto);
        }
    }

    $stmtDelete = $pdo->prepare("DELETE FROM barang WHERE id_barang = ?");
    $stmtDelete->execute([$id_barang]);

    echo json_encode(["status" => "sukses", "message" => "Barang dan fotonya berhasil dihapus!"]);
} catch (PDOException $e) {
    error_log("[TITIPIN] barang/delete error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Gagal menghapus data. Mungkin barang sedang terkait dengan klaim aktif."]);
}
