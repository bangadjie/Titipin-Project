<?php
header('Content-Type: application/json');
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

    if ($barang && $barang['foto']) {
        $pathFoto = '../../assets/uploads/' . $barang['foto'];
        if (file_exists($pathFoto)) {
            unlink($pathFoto);
        }
    }

    $stmtDelete = $pdo->prepare("DELETE FROM barang WHERE id_barang = ?");
    $stmtDelete->execute([$id_barang]);

    echo json_encode(["status" => "sukses", "message" => "Barang dan fotonya berhasil dihapus dari sistem!"]);
} catch (PDOException $e) {
    // Menangani error jika barang tidak bisa dihapus karena sedang berelasi dengan tabel klaim
    echo json_encode(["status" => "error", "message" => "Gagal menghapus data: " . $e->getMessage()]);
}
