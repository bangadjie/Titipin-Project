<?php
header('Content-Type: application/json');
require '../config/koneksi.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$id_kerusakan    = $data['id_kerusakan'] ?? null;
$status          = $data['status'] ?? ''; 
$catatan_teknisi = $data['catatan_teknisi'] ?? '';

if (!$id_kerusakan || !$status) {
    echo json_encode(["status" => "error", "message" => "ID atau Status tidak boleh kosong!"]);
    exit;
}

try {
    $query = "UPDATE kerusakan_fasilitas SET status = ?, catatan_teknisi = ? WHERE id_kerusakan = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$status, $catatan_teknisi, $id_kerusakan]);
    
    echo json_encode(["status" => "sukses", "message" => "Status perbaikan berhasil diubah menjadi " . $status]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal update status perbaikan: " . $e->getMessage()]);
}
?>