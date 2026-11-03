<?php
header('Content-Type: application/json');
require '../middleware/auth.php';  // Wajib login
require '../config/koneksi.php';
require '../config/klaim.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Gunakan method POST!"]);
    exit;
}

$item_id    = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
$claimer_id = $_SESSION['user_nim'];

if (!$item_id || $item_id < 1) {
    echo json_encode(["status" => "error", "message" => "Data barang tidak valid!"]);
    exit;
}

$uploaded_path = null;

function fail_claim(PDO $pdo, string $message, ?string $path = null): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    delete_uploaded_file($path);

    echo json_encode(["status" => "error", "message" => $message]);
    exit;
}

try {
    expire_claims($pdo);

    $stmtBarang = $pdo->prepare(
        "SELECT id_barang, nama_barang, jenis, status, pelapor_id
         FROM barang
         WHERE id_barang = ?"
    );
    $stmtBarang->execute([$item_id]);
    $barang = $stmtBarang->fetch();

    if (!$barang) {
        fail_claim($pdo, "Barang tidak ditemukan!");
    }

    if ($barang['jenis'] !== 'Found') {
        fail_claim($pdo, "Hanya barang temuan yang dapat diklaim!");
    }

    if ($barang['status'] !== 'Tersedia') {
        fail_claim($pdo, "Barang tidak tersedia untuk diklaim. Status saat ini: " . $barang['status'] . ".");
    }

    if ($barang['pelapor_id'] === $claimer_id) {
        fail_claim($pdo, "Kamu tidak dapat mengklaim barang yang kamu laporkan sendiri!");
    }

    $stmtCekKlaim = $pdo->prepare(
        "SELECT id_klaim
         FROM klaim
         WHERE item_id = ? AND claimer_id = ? AND status_klaim = 'Diajukan'"
    );
    $stmtCekKlaim->execute([$item_id, $claimer_id]);

    if ($stmtCekKlaim->fetch()) {
        fail_claim($pdo, "Kamu sudah punya klaim aktif untuk barang ini!");
    }

    if (
        !isset($_FILES['bukti_kepemilikan']) ||
        $_FILES['bukti_kepemilikan']['error'] === UPLOAD_ERR_NO_FILE
    ) {
        fail_claim($pdo, "Foto bukti kepemilikan wajib diupload!");
    }

    if ($_FILES['bukti_kepemilikan']['error'] !== UPLOAD_ERR_OK) {
        fail_claim($pdo, "Gagal mengupload foto bukti. Coba lagi.");
    }

    $file     = $_FILES['bukti_kepemilikan'];
    $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $allowed_ext  = ['jpg', 'jpeg', 'png'];
    $allowed_mime = ['image/jpeg', 'image/png'];
    $max_size     = 5 * 1024 * 1024;

    if ($file['size'] > $max_size) {
        fail_claim($pdo, "Ukuran foto bukti maksimal 5MB!");
    }

    if (!in_array($ekstensi, $allowed_ext, true)) {
        fail_claim($pdo, "Format foto tidak valid! Gunakan JPG atau PNG.");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);

    if (!in_array($mime, $allowed_mime, true)) {
        fail_claim($pdo, "Tipe file tidak diizinkan! Gunakan JPG atau PNG.");
    }

    $nama_file_bukti = 'bukti_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ekstensi;
    $uploaded_path   = __DIR__ . '/../../assets/uploads/' . $nama_file_bukti;

    if (!move_uploaded_file($file['tmp_name'], $uploaded_path)) {
        fail_claim($pdo, "Gagal menyimpan foto bukti. Coba lagi.");
    }

    $kode_redeem = generate_redeem_code();
    $kode_tiket  = 'KLM-' . strtoupper(bin2hex(random_bytes(4)));

    $pdo->beginTransaction();

    $stmtBarang = $pdo->prepare(
        "SELECT id_barang, jenis, status, pelapor_id
         FROM barang
         WHERE id_barang = ?
         FOR UPDATE"
    );
    $stmtBarang->execute([$item_id]);
    $barang = $stmtBarang->fetch();

    if (!$barang) {
        fail_claim($pdo, "Barang tidak ditemukan!", $uploaded_path);
    }

    if ($barang['jenis'] !== 'Found') {
        fail_claim($pdo, "Hanya barang temuan yang dapat diklaim!", $uploaded_path);
    }

    if ($barang['status'] !== 'Tersedia') {
        fail_claim($pdo, "Barang tidak tersedia untuk diklaim. Status saat ini: " . $barang['status'] . ".", $uploaded_path);
    }

    if ($barang['pelapor_id'] === $claimer_id) {
        fail_claim($pdo, "Kamu tidak dapat mengklaim barang yang kamu laporkan sendiri!", $uploaded_path);
    }

    $stmtCekKlaim->execute([$item_id, $claimer_id]);

    if ($stmtCekKlaim->fetch()) {
        fail_claim($pdo, "Kamu sudah punya klaim aktif untuk barang ini!", $uploaded_path);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO klaim
            (kode_tiket, item_id, claimer_id, bukti_kepemilikan, status_klaim, redeem_code_hash, redeem_expires_at)
         VALUES (?, ?, ?, ?, 'Diajukan', ?, NOW() + INTERVAL '24 hours')
         RETURNING id_klaim, redeem_expires_at"
    );
    $stmt->execute([
        $kode_tiket,
        $item_id,
        $claimer_id,
        $nama_file_bukti,
        password_hash($kode_redeem, PASSWORD_DEFAULT)
    ]);

    $klaim = $stmt->fetch();

    $pdo->commit();

    echo json_encode([
        "status"  => "sukses",
        "message" => "Klaim berhasil diajukan! Kode redeem berlaku 24 jam.",
        "data"    => [
            "id_klaim"          => (int) $klaim['id_klaim'],
            "kode_tiket"        => $kode_tiket,
            "redeem_code"       => $kode_redeem,
            "redeem_expires_at" => $klaim['redeem_expires_at'],
            "status_klaim"      => "Diajukan"
        ],
        "tiket"             => $kode_tiket,
        "redeem_code"       => $kode_redeem,
        "redeem_expires_at" => $klaim['redeem_expires_at']
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    delete_uploaded_file($uploaded_path);

    error_log("[TITIPIN] klaim/create error: " . $e->getMessage());

    if (str_contains($e->getMessage(), 'klaim_satu_pending_per_user_barang')) {
        echo json_encode(["status" => "error", "message" => "Kamu sudah punya klaim aktif untuk barang ini!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal mengajukan klaim. Hubungi administrator."]);
    }
}
?>