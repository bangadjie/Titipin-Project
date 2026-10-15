<?php
header('Content-Type: application/json');
require '../config/koneksi.php';

try {
    $query = "
        select b.*, u.nama_lengkap AS nama_pelapor 
        from barang b 
        left join users u on b.pelapor_id = u.nim 
        order by b.tanggal desc
    ";
    $stmt = $pdo->query($query);
    $barang = $stmt->fetchAll();

    echo json_encode([
        "status" => "sukses",
        "data" => $barang
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal ambil data barang: " . $e->getMessage()]);
}
