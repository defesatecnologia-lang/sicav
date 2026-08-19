<?php

require_once __DIR__ . '/../config/app.php';

const MFA_SECRET_BYTES = 20;
const MFA_TIME_STEP = 30;
const MFA_CODE_LENGTH = 6;
const MFA_RECOVERY_CODES_COUNT = 10;

function mfaBase32Encode(string $bytes): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    foreach (str_split($bytes) as $byte) {
        $binary .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
    }

    $base32 = '';
    foreach (str_split($binary, 5) as $chunk) {
        $base32 .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
    }

    $pad = strlen($base32) % 8;
    if ($pad !== 0) {
        $base32 .= str_repeat('=', 8 - $pad);
    }

    return $base32;
}

function mfaBase32Decode(string $base32): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32 = strtoupper(rtrim($base32, '='));

    $binary = '';
    foreach (str_split($base32) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) {
            throw new InvalidArgumentException('Caractere invalido no secret base32.');
        }
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }

    $bytes = '';
    foreach (str_split($binary, 8) as $chunk) {
        if (strlen($chunk) < 8) {
            break;
        }
        $bytes .= chr(bindec($chunk));
    }

    return $bytes;
}

function mfaGenerateSecret(): string
{
    return mfaBase32Encode(random_bytes(MFA_SECRET_BYTES));
}

function mfaGenerateTotp(string $secret, int $timeSlice): string
{
    $key = mfaBase32Decode($secret);

    $binary = pack('N*', 0) . pack('N*', $timeSlice);
    $hash = hash_hmac('sha1', $binary, $key, true);

    $offset = ord(substr($hash, -1)) & 0x0F;
    $code = (
        ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        (ord($hash[$offset + 3]) & 0xFF)
    ) % 1000000;

    return str_pad((string) $code, MFA_CODE_LENGTH, '0', STR_PAD_LEFT);
}

function mfaVerifyCode(string $secret, string $code, int $window = 1): bool
{
    $code = trim($code);
    if (!preg_match('/^\d{' . MFA_CODE_LENGTH . '}$/', $code)) {
        return false;
    }

    $currentSlice = (int) floor(time() / MFA_TIME_STEP);

    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(mfaGenerateTotp($secret, $currentSlice + $i), $code)) {
            return true;
        }
    }

    return false;
}

function mfaOtpauthUri(string $secret, string $label, string $issuer): string
{
    $issuerClean = preg_replace('/[^A-Za-z0-9 ]/', '', $issuer);
    $labelClean = preg_replace('/[^A-Za-z0-9 ]/', '', $label);

    return 'otpauth://totp/' . rawurlencode($labelClean)
        . '?secret=' . rawurlencode($secret)
        . '&issuer=' . rawurlencode($issuerClean)
        . '&algorithm=SHA1&digits=' . MFA_CODE_LENGTH
        . '&period=' . MFA_TIME_STEP;
}

function mfaEncryptionKey(): string
{
    $key = appEnv('VISITORPASS_MFA_KEY');
    if ($key === null || strlen($key) !== 64) {
        throw new RuntimeException('VISITORPASS_MFA_KEY ausente ou invalido (esperado 64 hex chars = 32 bytes).');
    }

    $decoded = hex2bin($key);
    if ($decoded === false) {
        throw new RuntimeException('VISITORPASS_MFA_KEY nao e hexadecimal valido.');
    }

    return $decoded;
}

function mfaEncryptSecret(string $secret): string
{
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = sodium_crypto_secretbox($secret, $nonce, mfaEncryptionKey());
    return base64_encode($nonce . $ciphertext);
}

function mfaDecryptSecret(string $stored): string
{
    $decoded = base64_decode($stored, true);
    if ($decoded === false || strlen($decoded) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
        throw new RuntimeException('Secret MFA armazenado invalido.');
    }

    $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

    $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, mfaEncryptionKey());
    if ($plain === false) {
        throw new RuntimeException('Falha ao decriptar secret MFA.');
    }

    return $plain;
}

function mfaGenerateRecoveryCodes(): array
{
    $codes = [];
    for ($i = 0; $i < MFA_RECOVERY_CODES_COUNT; $i++) {
        $codes[] = strtoupper(bin2hex(random_bytes(5)));
    }
    return $codes;
}

function mfaHashRecoveryCode(string $code): string
{
    return hash('sha256', $code);
}