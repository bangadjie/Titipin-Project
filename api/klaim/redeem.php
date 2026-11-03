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

$id_klaim    = filter_var($data['id_klaim'] ?? null, FILTER_VALIDATE_INT);
$redeem_code = normalize_redeem_code((string) ($data['redeem_code'] ?? ''));

if (!$id_klaim || $id_klaim < 1) {
    echo json_encode(["status" => "error", "message" => "ID klaim tidak ditemukan!"]);
    exit;
}

if (!is_valid_redeem_code_format($redeem_code)) {
    echo json_encode(["status" => "error", "message" => "Kode redeem harus 5 karakter (huruf/angka)!"]);
    exit;
}

function fail_redeem(PDO $pdo, string $message): void
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
        SELECT k.*,
               b.nama_barang,
               b.status AS status_barang,
               CASE
                   WHEN k.redeem_expires_at IS NULL OR k.redeem_expires_at <= NOW() THEN 1
                   ELSE 0
               END AS redeem_expired
        FROM klaim k
        JOIN barang b ON b.id_barang = k.item_id
        WHERE k.id_klaim = ?
        FOR UPDATE OF k, b
    ");
    $stmt->execute([$id_klaim]);
    $klaim = $stmt->fetch();

    if (!$klaim) {
        fail_redeem($pdo, "Klaim tidak ditemukan!");
    }

    if ($klaim['status_klaim'] === 'Disetujui') {
        fail_redeem($pdo, "Klaim ini sudah disetujui dan tidak bisa diredeem lagi.");
    }

    if ($klaim['status_klaim'] === 'Ditolak') {
        $alasan = $klaim['alasan_penolakan'] ?: 'Klaim sudah ditolak.';
        fail_redeem($pdo, "Klaim tidak dapat diproses: " . $alasan);
    }

    if ($klaim['status_klaim'] !== 'Diajukan') {
        fail_redeem($pdo, "Status klaim tidak valid untuk redeem.");
    }

    if ((int) $klaim['redeem_expired'] === 1) {
        $stmtExpired = $pdo->prepare("
            UPDATE klaim
            SET status_klaim = 'Ditolak',
                alasan_penolakan = 'Kode redeem kedaluwarsa (24 jam).'
            WHERE id_klaim = ?
        ");
        $stmtExpired->execute([$id_klaim]);

        $pdo->commit();

        echo json_encode([
            "status"  => "error",
            "message" => "Kode redeem sudah kedaluwarsa. Klaim otomatis ditolak."
        ]);
        exit;
    }

    if (empty($klaim['redeem_code_hash'])) {
        fail_redeem($pdo, "Klaim ini tidak memiliki kode redeem.");
    }

    if (!password_verify($redeem_code, $klaim['redeem_code_hash'])) {
        fail_redeem($pdo, "Kode redeem tidak valid!");
    }

    if (empty($klaim['bukti_kepemilikan'])) {
        fail_redeem($pdo, "Bukti kepemilikan belum diupload. Klaim tidak dapat disetujui.");
    }

    if ($klaim['status_barang'] !== 'Tersedia') {
        if ($klaim['status_barang'] === 'Selesai') {
            $stmtTolak = $pdo->prepare("
                UPDATE klaim
                SET status_klaim = 'Ditolak',
                    alasan_penolakan = 'Barang sudah diklaim user lain.'
                WHERE id_klaim = ?
            ");
            $stmtTolak->execute([$id_klaim]);

            $pdo->commit();

            echo json_encode([
                "status"  => "error",
                "message" => "Barang sudah diklaim user lain. Klaim ini otomatis ditolak."
            ]);
            exit;
        }

        fail_redeem($pdo, "Barang tidak tersedia untuk diserahkan. Status saat ini: " . $klaim['status_barang'] . ".");
    }

    $stmtApprove = $pdo->prepare("
        UPDATE klaim
        SET status_klaim = 'Disetujui',
            admin_id = ?,
            redeem_used_at = NOW(),
            alasan_penolakan = NULL
        WHERE id_klaim = ?
    ");
    $stmtApprove->execute([$_SESSION['user_nim'], $id_klaim]);

    $stmtBarang = $pdo->prepare("
        UPDATE barang
        SET status = 'Selesai'
        WHERE id_barang = ? AND status = 'Tersedia'
    ");
    $stmtBarang->execute([$klaim['item_id']]);

    if ($stmtBarang->rowCount() !== 1) {
        fail_redeem($pdo, "Barang gagal ditutup. Status barang sudah berubah.");
    }

    reject_pending_claims_for_item(
        $pdo,
        (int) $klaim['item_id'],
        (int) $id_klaim,
        'Barang sudah diklaim user lain.'
    );

    $pdo->commit();

    echo json_encode([
        "status"  => "sukses",
        "message" => "Kode redeem valid. Klaim disetujui dan barang ditandai Selesai.",
        "data"    => [
            "id_klaim"     => (int) $klaim['id_klaim'],
            "kode_tiket"   => $klaim['kode_tiket'],
            "item_id"      => (int) $klaim['item_id'],
            "nama_barang"  => $klaim['nama_barang'],
            "claimer_id"   => $klaim['claimer_id'],
            "admin_id"     => $_SESSION['user_nim'],
            "status_klaim" => "Disetujui"
        ]
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("[TITIPIN] klaim/redeem error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Gagal memproses redeem. Hubungi administrator."]);
}
