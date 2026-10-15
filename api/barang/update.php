<?php
header('Content-Type: application/json');
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Hanya menerima method POST!"]);
    exit;
}

$id_barang   = $_POST['id_barang'] ?? null;
$nama_barang = $_POST['nama_barang'] ?? '';
$kategori_id = $_POST['kategori_id'] ?? null;
$lokasi_id   = $_POST['lokasi_id'] ?? null;
$jenis       = $_POST['jenis'] ?? '';
$deskripsi   = $_POST['deskripsi'] ?? '';
$status      = $_POST['status'] ?? '';

if (!$id_barang) {
    echo json_encode(["status" => "error", "message" => "ID Barang tidak ditemukan!"]);
    exit;
}

try {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $stmtCek = $pdo->prepare("SELECT foto FROM barang WHERE id_barang = ?");
        $stmtCek->execute([$id_barang]);
        $barangLama = $stmtCek->fetch();

        if ($barangLama && $barangLama['foto']) {
            $pathLama = '../../assets/uploads/' . $barangLama['foto'];
            if (file_exists($pathLama)) {
                unlink($pathLama); // Hapus foto lama
            }
        }

        $tmp_name = $_FILES['foto']['tmp_name'];
        $ekstensi = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nama_file_foto = time() . '_' . uniqid() . '.' . $ekstensi;

        $folder_simpan = '../../assets/uploads/' . $nama_file_foto;
        move_uploaded_file($tmp_name, $folder_simpan);

        $query = "UPDATE barang SET nama_barang = ?, kategori_id = ?, lokasi_id = ?, jenis = ?, deskripsi = ?, status = ?, foto = ? WHERE id_barang = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$nama_barang, $kategori_id, $lokasi_id, $jenis, $deskripsi, $status, $nama_file_foto, $id_barang]);
    } else {
        $query = "UPDATE barang SET nama_barang = ?, kategori_id = ?, lokasi_id = ?, jenis = ?, deskripsi = ?, status = ? WHERE id_barang = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$nama_barang, $kategori_id, $lokasi_id, $jenis, $deskripsi, $status, $id_barang]);
    }

    echo json_encode(["status" => "sukses", "message" => "Data barang berhasil diperbarui!"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Gagal update data: " . $e->getMessage()]);
}
