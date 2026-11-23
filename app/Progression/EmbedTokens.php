<?php

namespace App\Progression;

use App\Models\User;

/**
 * Read-only tokens for the in-game progression viewer (spec 018 D2). The game server mints one for a
 * player with its service account; the mod hands it to the embedded page through the browser bridge,
 * never in a URL. Stateless (HMAC over the app key) and short-lived, so there is nothing to revoke.
 */
final class EmbedTokens
{
    private const AUDIENCE = 'progression-embed';

    private const PREFIX = 'pe1';

    /** @return array{token: string, expires_at: int} */
    public function issue(User $user): array
    {
        $expiresAt = now()->getTimestamp() + max(60, (int) config('progression.embed_token_ttl'));
        $payload = self::encode(json_encode(['sub' => $user->id, 'aud' => self::AUDIENCE, 'exp' => $expiresAt], JSON_THROW_ON_ERROR));

        return ['token' => self::PREFIX.".{$payload}.".$this->sign($payload), 'expires_at' => $expiresAt];
    }

    public function verify(?string $token): ?User
    {
        if ($token === null || strlen($token) > 512) {
            return null;
        }
        $parts = explode('.', $token);
        if (count($parts) !== 3 || $parts[0] !== self::PREFIX || ! hash_equals($this->sign($parts[1]), $parts[2])) {
            return null;
        }
        $claims = json_decode((string) self::decode($parts[1]), true);
        if (! is_array($claims) || ($claims['aud'] ?? null) !== self::AUDIENCE || ! is_int($claims['sub'] ?? null) || ! is_int($claims['exp'] ?? null)) {
            return null;
        }
        if ($claims['exp'] < now()->getTimestamp()) {
            return null;
        }

        return User::find($claims['sub']);
    }

    private function sign(string $payload): string
    {
        // A key of its own, derived from the app key, so these signatures never match another HMAC use of it.
        $key = hash_hmac('sha256', self::AUDIENCE, (string) config('app.key'), true);

        return self::encode(hash_hmac('sha256', self::PREFIX.'.'.$payload, $key, true));
    }

    private static function encode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private static function decode(string $text): string|false
    {
        return base64_decode(strtr($text, '-_', '+/'), true);
    }
}
