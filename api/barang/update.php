<?php
header('Content-Type: application/json');
require '../middleware/admin.php'; // Hanya Admin
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
        $file     = $_FILES['foto'];
        $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $allowed_ext  = ['jpg', 'jpeg', 'png'];
        $allowed_mime = ['image/jpeg', 'image/png'];
        $max_size     = 2 * 1024 * 1024; // Maks 2MB bisa nambah

        if ($file['size'] > $max_size) {
            echo json_encode(["status" => "error", "message" => "Ukuran foto maksimal 2MB!"]);
            exit;
        }

        if (!in_array($ekstensi, $allowed_ext)) {
            echo json_encode(["status" => "error", "message" => "Format foto tidak valid! Gunakan JPG atau PNG."]);
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_mime)) {
            echo json_encode(["status" => "error", "message" => "Tipe file tidak diizinkan!"]);
            exit;
        }

        $stmtCek = $pdo->prepare("SELECT foto FROM barang WHERE id_barang = ?");
        $stmtCek->execute([$id_barang]);
        $barangLama = $stmtCek->fetch();

        if ($barangLama && $barangLama['foto']) {
            $pathLama = '../../assets/uploads/' . $barangLama['foto'];
            if (file_exists($pathLama)) {
                unlink($pathLama);
            }
        }

        $nama_file_foto = time() . '_' . uniqid() . '.' . $ekstensi;
        $folder_simpan  = '../../assets/uploads/' . $nama_file_foto;

        if (!move_uploaded_file($file['tmp_name'], $folder_simpan)) {
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan foto baru. Coba lagi."]);
            exit;
        }

        $query = "UPDATE barang SET nama_barang = ?, kategori_id = ?, lokasi_id = ?, jenis = ?, deskripsi = ?, status = ?, foto = ? WHERE id_barang = ?";
        $stmt  = $pdo->prepare($query);
        $stmt->execute([$nama_barang, $kategori_id, $lokasi_id, $jenis, $deskripsi, $status, $nama_file_foto, $id_barang]);
    } else {
        $query = "UPDATE barang SET nama_barang = ?, kategori_id = ?, lokasi_id = ?, jenis = ?, deskripsi = ?, status = ? WHERE id_barang = ?";
        $stmt  = $pdo->prepare($query);
        $stmt->execute([$nama_barang, $kategori_id, $lokasi_id, $jenis, $deskripsi, $status, $id_barang]);
    }

    echo json_encode(["status" => "sukses", "message" => "Data barang berhasil diperbarui!"]);
} catch (PDOException $e) {
    error_log("[TITIPIN] barang/update error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Gagal update data. Hubungi administrator."]);
}
