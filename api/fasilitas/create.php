<?php
header('Content-Type: application/json');
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Gunakan method POST!"]);
    exit;
}

$nama_fasilitas      = $_POST['nama_fasilitas'] ?? '';
$kategori_id         = $_POST['kategori_id'] ?? null;
$lokasi_id           = $_POST['lokasi_id'] ?? null;
$deskripsi_kerusakan = $_POST['deskripsi_kerusakan'] ?? '';
$pelapor_id          = $_POST['pelapor_id'] ?? '';
$tanggal_lapor       = date('Y-m-d');
$status              = 'Dilaporkan'; 

if (!$nama_fasilitas || !$deskripsi_kerusakan || !$pelapor_id) {
    echo json_encode(["status" => "error", "message" => "Data laporan belum lengkap!"]);
    exit;
}

$nama_file_foto = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['foto']['tmp_name'];
    $ekstensi = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $nama_file_foto = 'fasilitas_' . time() . '_' . uniqid() . '.' . $ekstensi;

    $folder_simpan = '../../assets/uploads/' . $nama_file_foto;
    move_uploaded_file($tmp_name, $folder_simpan);
}

try {
    $query = "INSERT INTO kerusakan_fasilitas (nama_fasilitas, kategori_id, lokasi_id, deskripsi_kerusakan, foto, tanggal_lapor, status, pelapor_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $nama_fasilitas,
        $kategori_id,
        $lokasi_id,
        $deskripsi_kerusakan,
        $nama_file_foto,
        $tanggal_lapor,
        $status,
        $pelapor_id
    ]);

    echo json_encode(["status" => "sukses", "message" => "Kerusakan fasilitas berhasil dilaporkan!"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal lapor kerusakan: " . $e->getMessage()]);
}
