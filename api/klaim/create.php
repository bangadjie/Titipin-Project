<?php
header('Content-Type: application/json');
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Gunakan method POST!"]);
    exit;
}

$item_id = $_POST['item_id'] ?? null;
$claimer_id = $_POST['claimer_id'] ?? ''; // NIM mahasiswa yang login
$kode_tiket = 'KLM-' . strtoupper(substr(uniqid(), -5)); // Generate kode tiket unik otomatis
$status_klaim = 'Pending'; // Status awal selalu pending

if (!$item_id || !$claimer_id) {
    echo json_encode(["status" => "error", "message" => "Data barang atau user tidak valid!"]);
    exit;
}

// Proses unggah bukti kepemilikan
$nama_file_bukti = null;
if (isset($_FILES['bukti_kepemilikan']) && $_FILES['bukti_kepemilikan']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['bukti_kepemilikan']['tmp_name'];
    $ekstensi = pathinfo($_FILES['bukti_kepemilikan']['name'], PATHINFO_EXTENSION);
    $nama_file_bukti = 'bukti_' . time() . '.' . $ekstensi;

    $folder_simpan = '../../assets/uploads/' . $nama_file_bukti;
    move_uploaded_file($tmp_name, $folder_simpan);
}

try {
    $query = "INSERT INTO klaim (kode_tiket, item_id, claimer_id, bukti_kepemilikan, status_klaim) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$kode_tiket, $item_id, $claimer_id, $nama_file_bukti, $status_klaim]);

    echo json_encode(["status" => "sukses", "message" => "Klaim berhasil diajukan! Menunggu admin.", "tiket" => $kode_tiket]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal mengajukan klaim: " . $e->getMessage()]);
}
