<?php
const REDEEM_CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
const REDEEM_CODE_LENGTH = 5;
const REDEEM_CODE_TTL_HOURS = 24;

function generate_redeem_code(): string
{
    $code = '';
    $max  = strlen(REDEEM_CODE_ALPHABET) - 1;

    for ($i = 0; $i < REDEEM_CODE_LENGTH; $i++) {
        $code .= REDEEM_CODE_ALPHABET[random_int(0, $max)];
    }

    return $code;
}

function normalize_redeem_code(string $code): string
{
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
}

function is_valid_redeem_code_format(string $code): bool
{
    return strlen($code) === REDEEM_CODE_LENGTH
        && strspn($code, REDEEM_CODE_ALPHABET) === REDEEM_CODE_LENGTH;
}

function expire_claims(PDO $pdo): void
{
    $stmt = $pdo->prepare("
        UPDATE klaim
        SET status_klaim = 'Ditolak',
            alasan_penolakan = 'Kode redeem kedaluwarsa (24 jam).'
        WHERE status_klaim = 'Diajukan'
          AND redeem_expires_at IS NOT NULL
          AND redeem_expires_at <= NOW()
    ");
    $stmt->execute();
}

function delete_uploaded_file(?string $path): void
{
    if ($path !== null && $path !== '' && file_exists($path)) {
        unlink($path);
    }
}

function reject_pending_claims_for_item(PDO $pdo, int $itemId, int $exceptClaimId, string $reason): void
{
    $stmt = $pdo->prepare("
        UPDATE klaim
        SET status_klaim = 'Ditolak',
            alasan_penolakan = ?
        WHERE item_id = ?
          AND id_klaim <> ?
          AND status_klaim = 'Diajukan'
    ");
    $stmt->execute([$reason, $itemId, $exceptClaimId]);
}
