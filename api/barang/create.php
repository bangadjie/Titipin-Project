<?php
header('Content-Type: application/json');
require '../middleware/auth.php';
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Hanya menerima method POST!"]);
    exit;
}

$nama_barang = $_POST['nama_barang'] ?? '';
$kategori_id = $_POST['kategori_id'] ?? null;
$lokasi_id   = $_POST['lokasi_id'] ?? null;
$jenis       = $_POST['jenis'] ?? '';
$deskripsi   = $_POST['deskripsi'] ?? '';
$tanggal     = date('Y-m-d');
$status      = 'Dilaporkan';
$pelapor_id  = $_SESSION['user_nim'];

if (!$nama_barang || !$jenis) {
    echo json_encode(["status" => "error", "message" => "Nama barang dan jenis wajib diisi!"]);
    exit;
}

$nama_file_foto = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $file     = $_FILES['foto'];
    $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $allowed_ext  = ['jpg', 'jpeg', 'png'];
    $allowed_mime = ['image/jpeg', 'image/png'];
    $max_size     = 2 * 1024 * 1024;

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

    $nama_file_foto = time() . '_' . uniqid() . '.' . $ekstensi;
    $folder_simpan  = '../../assets/uploads/' . $nama_file_foto;

    if (!move_uploaded_file($file['tmp_name'], $folder_simpan)) {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan foto. Coba lagi."]);
        exit;
    }
}

try {
    $query = "INSERT INTO barang (nama_barang, kategori_id, lokasi_id, jenis, deskripsi, tanggal, foto, status, pelapor_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt  = $pdo->prepare($query);
    $stmt->execute([$nama_barang, $kategori_id, $lokasi_id, $jenis, $deskripsi, $tanggal, $nama_file_foto, $status, $pelapor_id]);

    echo json_encode(["status" => "sukses", "message" => "Barang berhasil dilaporkan!"]);
} catch (PDOException $e) {
    error_log("[TITIPIN] barang/create error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Gagal simpan data. Hubungi administrator."]);
}
