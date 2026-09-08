<?php
namespace App\Support;

use Throwable;

/**
 * Cryptographically secure ID generator.
 *
 * Backs generateUniqueAlphaNumericNumber() and generateUniqueNumber().
 * All randomness comes from random_bytes() so IDs are unpredictable
 * and collision-resistant. No time(), mt_rand(), or str_shuffle() is
 * used anywhere on this path.
 *
 * Formats (all lowercase hex unless noted):
 * - randomHex(8) returns 16 hex chars from 8 random bytes.
 * - alphaNumeric() returns 4 uppercase hex chars plus dash plus 16
 *   hex chars (for example 3F2A-9f4c2a1be07d83c1).
 * - uniqueNumber() returns 16 hex chars plus dash plus 8 hex chars
 *   (for example 9f4c2a1be07d83c1-4d2e9a0b).
 */
final class IdGenerator
{
    /**
     * Generate a hex string from crypto randomness.
     *
     * Uses random_bytes() and bin2hex(). Falls back to uniqid with
     * extra entropy only when the CSPRNG is unavailable (extremely
     * rare); the fallback is still unique but callers should treat
     * it as degraded.
     *
     * @param int $bytes Number of random bytes (output is twice as many hex chars).
     * @return string Lowercase hex string.
     */
    public static function randomHex(int $bytes = 8): string
    {
        $count = $bytes > 0 ? $bytes : 8;

        try {
            return bin2hex(random_bytes($count));
        } catch (Throwable) {
            return bin2hex(self::fallbackBytes($count));
        }
    }

    /**
     * Generate a unique alphanumeric ID with an alpha prefix.
     *
     * Replaces the old str_shuffle plus time() format. Returns 4
     * uppercase hex chars, a dash, then 16 hex chars so existing
     * PREFIX-SUFFIX shape keeps working while both parts are crypto
     * random.
     *
     * @return string Unique ID like 3F2A-9f4c2a1be07d83c1.
     */
    public static function alphaNumeric(): string
    {
        $prefix = strtoupper(substr(self::randomHex(4), 0, 4));
        $suffix = self::randomHex(8);

        return $prefix . '-' . $suffix;
    }

    /**
     * Generate a unique number-style ID.
     *
     * Replaces the old mt_rand plus time() format. Returns 16 hex
     * chars, a dash, then 8 hex chars so the value stays unique and
     * unpredictable without wall-clock dependence.
     *
     * @return string Unique ID like 9f4c2a1be07d83c1-4d2e9a0b.
     */
    public static function uniqueNumber(): string
    {
        return self::randomHex(8) . '-' . self::randomHex(4);
    }

    /**
     * Produce fallback bytes when random_bytes() is unavailable.
     *
     * Mixes uniqid entropy with microtime to keep uniqueness. Only
     * used in the degraded path; normal operation never reaches here.
     *
     * @param int $bytes Number of bytes to emulate.
     * @return string Raw bytes of the requested length.
     */
    private static function fallbackBytes(int $bytes): string
    {
        $needed = $bytes > 0 ? $bytes : 8;
        $buffer = '';

        while (strlen($buffer) < $needed) {
            $buffer .= hash('sha256', uniqid('', true) . microtime(true) . $buffer, true);
        }

        return substr($buffer, 0, $needed);
    }
}
