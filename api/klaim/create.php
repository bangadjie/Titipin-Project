<?php
header('Content-Type: application/json');
require '../middleware/auth.php';  // Wajib login
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Gunakan method POST!"]);
    exit;
}

$item_id      = $_POST['item_id'] ?? null;
$claimer_id   = $_SESSION['user_nim']; 
$kode_tiket   = 'KLM-' . strtoupper(bin2hex(random_bytes(4))); 
$status_klaim = 'Pending';

if (!$item_id) {
    echo json_encode(["status" => "error", "message" => "Data barang tidak valid!"]);
    exit;
}

$stmtCekKlaim = $pdo->prepare("SELECT id_klaim FROM klaim WHERE item_id = ? AND claimer_id = ?");
$stmtCekKlaim->execute([$item_id, $claimer_id]);
if ($stmtCekKlaim->fetch()) {
    echo json_encode(["status" => "error", "message" => "Kamu sudah pernah mengajukan klaim untuk barang ini!"]);
    exit;
}

$nama_file_bukti = null;
if (isset($_FILES['bukti_kepemilikan']) && $_FILES['bukti_kepemilikan']['error'] === UPLOAD_ERR_OK) {
    $file     = $_FILES['bukti_kepemilikan'];
    $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $allowed_ext  = ['jpg', 'jpeg', 'png', 'pdf'];
    $allowed_mime = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_size     = 5 * 1024 * 1024; // Maks 5MB (lebih besar karena bisa PDF)

    if ($file['size'] > $max_size) {
        echo json_encode(["status" => "error", "message" => "Ukuran file bukti maksimal 5MB!"]);
        exit;
    }

    if (!in_array($ekstensi, $allowed_ext)) {
        echo json_encode(["status" => "error", "message" => "Format bukti tidak valid! Gunakan JPG, PNG, atau PDF."]);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mime)) {
        echo json_encode(["status" => "error", "message" => "Tipe file tidak diizinkan!"]);
        exit;
    }

    $nama_file_bukti = 'bukti_' . time() . '_' . uniqid() . '.' . $ekstensi;
    $folder_simpan   = '../../assets/uploads/' . $nama_file_bukti;

    if (!move_uploaded_file($file['tmp_name'], $folder_simpan)) {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan file bukti. Coba lagi."]);
        exit;
    }
}

try {
    $query = "INSERT INTO klaim (kode_tiket, item_id, claimer_id, bukti_kepemilikan, status_klaim) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt  = $pdo->prepare($query);
    $stmt->execute([$kode_tiket, $item_id, $claimer_id, $nama_file_bukti, $status_klaim]);

    echo json_encode([
        "status"  => "sukses",
        "message" => "Klaim berhasil diajukan! Menunggu verifikasi admin.",
        "tiket"   => $kode_tiket
    ]);
} catch (PDOException $e) {
    error_log("[TITIPIN] klaim/create error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Gagal mengajukan klaim. Hubungi administrator."]);
}
