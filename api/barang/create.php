<?php
header('Content-Type: application/json');
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Hanya menerima method POST!"]);
    exit;
}

$nama_barang    = $_POST['nama_barang'] ?? '';
$kategori_id    = $_POST['kategori_id'] ?? null;
$lokasi_id      = $_POST['lokasi_id'] ?? null;
$jenis          = $_POST['jenis'] ?? '';
$deskripsi      = $_POST['deskripsi'] ?? '';
$tanggal        = date('Y-m-d');
$status         = 'Dilaporkan';
$pelapor_id     = $_POST['pelapor_id'] ?? '';

$nama_file_foto = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['foto']['tmp_name'];
    $ekstensi = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);

    $nama_file_foto = time() . '_' . uniqid() . '.' . $ekstensi;

    $folder_simpan = '../../assets/uploads/' . $nama_file_foto;
    move_uploaded_file($tmp_name, $folder_simpan);
}

try {
    $query = "INSERT INTO barang (nama_barang, kategori_id, lokasi_id, jenis, deskripsi, tanggal, foto, status, pelapor_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $nama_barang,
        $kategori_id,
        $lokasi_id,
        $jenis,
        $deskripsi,
        $tanggal,
        $nama_file_foto,
        $status,
        $pelapor_id
    ]);

    echo json_encode(["status" => "sukses", "message" => "Barang berhasil dilaporkan!"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal simpan data: " . $e->getMessage()]);
}
