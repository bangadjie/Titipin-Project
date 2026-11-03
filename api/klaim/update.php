<?php
header('Content-Type: application/json');
require '../middleware/admin.php';
require '../config/koneksi.php';
require '../config/klaim.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Gunakan method POST!"]);
    exit;
}

$data = $_POST;
if (empty($data)) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (!is_array($data)) {
        $data = [];
    }
}

$id_klaim     = filter_var($data['id_klaim'] ?? null, FILTER_VALIDATE_INT);
$status_klaim = trim((string) ($data['status_klaim'] ?? ''));
$alasan       = trim((string) ($data['alasan_penolakan'] ?? ''));

if (!$id_klaim || $id_klaim < 1) {
    echo json_encode(["status" => "error", "message" => "ID klaim tidak ditemukan!"]);
    exit;
}

if ($status_klaim === '') {
    echo json_encode(["status" => "error", "message" => "Status klaim wajib diisi!"]);
    exit;
}

if ($status_klaim === 'Disetujui') {
    echo json_encode([
        "status"  => "error",
        "message" => "Persetujuan klaim harus lewat endpoint klaim/redeem.php dengan kode redeem."
    ]);
    exit;
}

if ($status_klaim !== 'Ditolak') {
    echo json_encode(["status" => "error", "message" => "Status klaim hanya boleh Ditolak di endpoint ini!"]);
    exit;
}

function fail_update_claim(PDO $pdo, string $message): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode(["status" => "error", "message" => $message]);
    exit;
}

try {
    expire_claims($pdo);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id_klaim, item_id, status_klaim
        FROM klaim
        WHERE id_klaim = ?
        FOR UPDATE
    ");
    $stmt->execute([$id_klaim]);
    $klaim = $stmt->fetch();

    if (!$klaim) {
        fail_update_claim($pdo, "Klaim tidak ditemukan!");
    }

    if ($klaim['status_klaim'] === 'Disetujui') {
        fail_update_claim($pdo, "Klaim yang sudah disetujui tidak dapat ditolak!");
    }

    if ($alasan === '') {
        $alasan = 'Ditolak oleh admin.';
    }

    $stmtUpdate = $pdo->prepare("
        UPDATE klaim
        SET status_klaim = 'Ditolak',
            alasan_penolakan = ?
        WHERE id_klaim = ?
    ");
    $stmtUpdate->execute([$alasan, $id_klaim]);

    $pdo->commit();

    echo json_encode([
        "status"  => "sukses",
        "message" => "Klaim berhasil ditolak.",
        "data"    => [
            "id_klaim"         => (int) $id_klaim,
            "status_klaim"     => "Ditolak",
            "alasan_penolakan" => $alasan
        ]
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("[TITIPIN] klaim/update error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Gagal memperbarui data klaim."]);
}
?>