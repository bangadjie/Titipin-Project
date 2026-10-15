<?php
header('Content-Type: application/json');
require '../config/koneksi.php';

// Karena hanya update teks status, kita bisa pakai JSON input biasa
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$id_klaim = $data['id_klaim'] ?? null;
$status_klaim = $data['status_klaim'] ?? ''; // 'Disetujui', 'Ditolak', atau 'Selesai'
$admin_id = $data['admin_id'] ?? null; // NIM Admin yang mengeksekusi

if (!$id_klaim || !$status_klaim) {
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap!"]);
    exit;
}

try {
    // Update status klaim dan catat admin yang memproses
    $query = "UPDATE klaim SET status_klaim = ?, admin_id = ? WHERE id_klaim = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$status_klaim, $admin_id, $id_klaim]);

    // Opsi Tambahan (Logika Bisnis): 
    // Jika klaim 'Disetujui' atau 'Selesai', kita update juga status di tabel barang
    if ($status_klaim === 'Disetujui' || $status_klaim === 'Selesai') {
        // Ambil item_id dari klaim ini
        $stmtCek = $pdo->prepare("SELECT item_id FROM klaim WHERE id_klaim = ?");
        $stmtCek->execute([$id_klaim]);
        $klaim = $stmtCek->fetch();

        if ($klaim) {
            $stmtBarang = $pdo->prepare("UPDATE barang SET status = 'Diklaim' WHERE id_barang = ?");
            $stmtBarang->execute([$klaim['item_id']]);
        }
    }

    echo json_encode(["status" => "sukses", "message" => "Status klaim berhasil diperbarui menjadi " . $status_klaim]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal update status: " . $e->getMessage()]);
}
