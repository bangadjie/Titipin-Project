<?php
header('Content-Type: application/json');
require '../middleware/auth.php';
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Gunakan method POST!"]);
    exit;
}

$nama_fasilitas      = $_POST['nama_fasilitas'] ?? '';
$kategori_id         = $_POST['kategori_id'] ?? null;
$lokasi_id           = $_POST['lokasi_id'] ?? null;
$deskripsi_kerusakan = $_POST['deskripsi_kerusakan'] ?? '';
$pelapor_id          = $_SESSION['user_nim'];
$tanggal_lapor       = date('Y-m-d');
$status              = 'Dilaporkan';

if (!$nama_fasilitas || !$deskripsi_kerusakan) {
    echo json_encode(["status" => "error", "message" => "Data laporan belum lengkap!"]);
    exit;
}

$nama_file_foto = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $file     = $_FILES['foto'];
    $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $allowed_ext  = ['jpg', 'jpeg', 'png'];
    $allowed_mime = ['image/jpeg', 'image/png'];
    $max_size     = 2 * 1024 * 1024; // Maks 2MB

    // Cek ukuran
    if ($file['size'] > $max_size) {
        echo json_encode(["status" => "error", "message" => "Ukuran foto maksimal 2MB!"]);
        exit;
    }

    // Cek ekstensi (whitelist)
    if (!in_array($ekstensi, $allowed_ext)) {
        echo json_encode(["status" => "error", "message" => "Format foto tidak valid! Gunakan JPG atau PNG."]);
        exit;
    }

    // Cek MIME type sungguhan
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mime)) {
        echo json_encode(["status" => "error", "message" => "Tipe file tidak diizinkan!"]);
        exit;
    }

    $nama_file_foto = 'fasilitas_' . time() . '_' . uniqid() . '.' . $ekstensi;
    $folder_simpan  = '../../assets/uploads/' . $nama_file_foto;

    if (!move_uploaded_file($file['tmp_name'], $folder_simpan)) {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan foto. Coba lagi."]);
        exit;
    }
}

try {
    $query = "INSERT INTO kerusakan_fasilitas (nama_fasilitas, kategori_id, lokasi_id, deskripsi_kerusakan, foto, tanggal_lapor, status, pelapor_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt  = $pdo->prepare($query);
    $stmt->execute([$nama_fasilitas, $kategori_id, $lokasi_id, $deskripsi_kerusakan, $nama_file_foto, $tanggal_lapor, $status, $pelapor_id]);

    echo json_encode(["status" => "sukses", "message" => "Kerusakan fasilitas berhasil dilaporkan!"]);
} catch (PDOException $e) {
    error_log("[TITIPIN] fasilitas/create error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Gagal lapor kerusakan. Hubungi administrator."]);
}
