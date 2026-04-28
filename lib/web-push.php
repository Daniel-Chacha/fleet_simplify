<?php
/**
 * FleetSimplify VBMS — Self-contained Web Push sender (RFC 8030 + 8291 + 8292).
 *
 * Sends an encrypted push notification to a single subscription. No external
 * dependencies — uses only OpenSSL primitives that ship with PHP 7.3+.
 *
 * The flow per RFC 8291 (aes128gcm content-encoding):
 *   1. Generate an ephemeral P-256 keypair (the "as" / application server key).
 *   2. ECDH(as_priv, ua_pub) gives a 32-byte shared secret.
 *   3. HKDF(salt=auth_secret, IKM=shared_secret, info="WebPush: info\0"||ua||as)
 *      gives a 32-byte intermediate IKM.
 *   4. A fresh 16-byte salt is generated; HKDF expands the IKM with that salt
 *      to derive the AES-128-GCM Content-Encryption Key (CEK, 16B) and Nonce
 *      (12B) using "Content-Encoding: aes128gcm\0" / "Content-Encoding: nonce\0".
 *   5. Plaintext is padded with 0x02 (single-record marker), encrypted, the
 *      auth tag is appended, and the body is framed as
 *         salt(16) || rs(4 BE) || idlen(1) || keyid=as_public(65) || ciphertext
 *   6. A VAPID JWT is signed (ES256) with our long-lived private key, claiming
 *      `aud=origin of endpoint`, `exp=now+12h`, `sub=mailto:…`.
 *   7. POST to the subscription endpoint with headers:
 *         Content-Type: application/octet-stream
 *         Content-Encoding: aes128gcm
 *         TTL: <seconds>
 *         Authorization: vapid t=<JWT>, k=<public key, base64url>
 *
 * @see https://datatracker.ietf.org/doc/html/rfc8291  (encryption)
 * @see https://datatracker.ietf.org/doc/html/rfc8292  (VAPID)
 * @see https://datatracker.ietf.org/doc/html/rfc8030  (HTTP framing)
 */

namespace FleetSimplify\WebPush;

class WebPushException extends \RuntimeException {}

class WebPush
{
    /** @var string base64url-encoded 65-byte uncompressed P-256 public key */
    private string $vapidPublic;
    /** @var string base64url-encoded 32-byte private scalar */
    private string $vapidPrivate;
    /** @var string mailto: or https:// — required by some browsers (e.g. Chrome) */
    private string $vapidSubject;

    public function __construct(string $vapidPublic, string $vapidPrivate, string $vapidSubject)
    {
        $this->vapidPublic  = $vapidPublic;
        $this->vapidPrivate = $vapidPrivate;
        $this->vapidSubject = $vapidSubject;
    }

    /**
     * Send one push. Returns ['ok' => bool, 'status' => int, 'body' => string, 'error' => ?string].
     * On 404/410 the caller should delete the stored subscription (the browser
     * has revoked it permanently).
     *
     * @param array  $subscription ['endpoint' => string, 'p256dh' => string, 'auth' => string]
     *                             where p256dh/auth are base64url with no padding.
     * @param string $payload      JSON or arbitrary bytes (≤ ~3 KB after framing). May be ''.
     * @param int    $ttl          Seconds the push service should retry delivery (0–2592000).
     */
    public function send(array $subscription, string $payload = '', int $ttl = 86400): array
    {
        if (empty($subscription['endpoint']) || empty($subscription['p256dh']) || empty($subscription['auth'])) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'invalid subscription'];
        }
        try {
            [$body, $asPublic] = $this->buildEncryptedBody($subscription, $payload);
            $jwt = $this->buildVapidJwt($subscription['endpoint']);
            $headers = [
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'TTL: ' . max(0, min(2592000, $ttl)),
                'Authorization: vapid t=' . $jwt . ', k=' . $this->vapidPublic,
                'Content-Length: ' . strlen($body),
            ];
            return $this->httpPost($subscription['endpoint'], $headers, $body);
        } catch (\Throwable $ex) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => $ex->getMessage()];
        }
    }

    // ------------------------------------------------------------------
    // Encryption (RFC 8291 / aes128gcm)
    // ------------------------------------------------------------------
    private function buildEncryptedBody(array $sub, string $payload): array
    {
        $uaPublicRaw  = self::b64uDecode($sub['p256dh']);   // 65 bytes 0x04||X||Y
        $authSecret   = self::b64uDecode($sub['auth']);     // 16 bytes
        if (strlen($uaPublicRaw) !== 65 || $uaPublicRaw[0] !== "\x04") {
            throw new WebPushException('invalid p256dh');
        }

        // Ephemeral P-256 keypair for this push.
        $asPrivate = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($asPrivate === false) throw new WebPushException('openssl_pkey_new failed');
        $det = openssl_pkey_get_details($asPrivate);
        $asPublicRaw = "\x04" .
            str_pad($det['ec']['x'], 32, "\x00", STR_PAD_LEFT) .
            str_pad($det['ec']['y'], 32, "\x00", STR_PAD_LEFT);

        // ECDH(as_priv, ua_pub) — needs ua_pub as a PEM key.
        $uaPubPem = self::rawP256ToPem($uaPublicRaw);
        $uaPubKey = openssl_pkey_get_public($uaPubPem);
        if ($uaPubKey === false) throw new WebPushException('invalid subscription public key');
        $shared = openssl_pkey_derive($uaPubKey, $asPrivate, 32);
        if ($shared === false) throw new WebPushException('ecdh derive failed');

        // Step 1: combine shared secret with subscription auth secret.
        $info = "WebPush: info\x00" . $uaPublicRaw . $asPublicRaw;
        $ikm  = hash_hkdf('sha256', $shared, 32, $info, $authSecret);

        // Step 2: derive CEK + nonce using a fresh per-message salt.
        $salt  = random_bytes(16);
        $cek   = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00",     $salt);

        // Pad with 0x02 (last-and-only-record marker) and encrypt.
        $plaintext  = $payload . "\x02";
        $tag        = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        if ($ciphertext === false) throw new WebPushException('aes-gcm encrypt failed');

        // aes128gcm framing: salt | rs | idlen | keyid | ciphertext|tag
        $rs    = pack('N', 4096);   // any value > body works; 4096 is conventional
        $idlen = chr(65);
        $body  = $salt . $rs . $idlen . $asPublicRaw . $ciphertext . $tag;

        return [$body, $asPublicRaw];
    }

    /** Wrap a 65-byte uncompressed P-256 public key as a PEM SubjectPublicKeyInfo. */
    private static function rawP256ToPem(string $raw): string
    {
        // Fixed ASN.1 prefix for SPKI EC P-256.
        $prefix = "\x30\x59\x30\x13\x06\x07\x2A\x86\x48\xCE\x3D\x02\x01"
                . "\x06\x08\x2A\x86\x48\xCE\x3D\x03\x01\x07\x03\x42\x00";
        $der = $prefix . $raw;
        return "-----BEGIN PUBLIC KEY-----\n" .
               chunk_split(base64_encode($der), 64, "\n") .
               "-----END PUBLIC KEY-----\n";
    }

    // ------------------------------------------------------------------
    // VAPID JWT (RFC 8292)
    // ------------------------------------------------------------------
    private function buildVapidJwt(string $endpoint): string
    {
        $parts = parse_url($endpoint);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            throw new WebPushException('invalid endpoint URL');
        }
        $aud = $parts['scheme'] . '://' . $parts['host']
             . (!empty($parts['port']) ? ':' . $parts['port'] : '');

        $header  = ['typ' => 'JWT', 'alg' => 'ES256'];
        $payload = [
            'aud' => $aud,
            'exp' => time() + 12 * 3600,
            'sub' => $this->vapidSubject,
        ];
        $signingInput = self::b64uEncode(json_encode($header)) . '.' . self::b64uEncode(json_encode($payload));

        // Build PEM private key from raw 32-byte scalar + matching public.
        $privPem = self::rawP256PrivToPem(self::b64uDecode($this->vapidPrivate), self::b64uDecode($this->vapidPublic));
        $key = openssl_pkey_get_private($privPem);
        if ($key === false) throw new WebPushException('cannot load VAPID private key');

        $derSig = '';
        if (!openssl_sign($signingInput, $derSig, $key, OPENSSL_ALGO_SHA256)) {
            throw new WebPushException('JWT signing failed');
        }
        // ES256 requires raw 64-byte (R||S) signature, not DER. Convert.
        $rs = self::derToRawEcdsaSig($derSig);

        return $signingInput . '.' . self::b64uEncode($rs);
    }

    /** Build a PEM EC private key from a raw 32-byte scalar + 65-byte uncompressed public key. */
    private static function rawP256PrivToPem(string $priv32, string $pub65): string
    {
        // RFC 5915 EC private-key DER:
        //   30 77                                       SEQUENCE
        //     02 01 01                                  INTEGER 1     (version)
        //     04 20 <priv32>                            OCTET STRING  (privateKey)
        //     A0 0A 06 08 2A 86 48 CE 3D 03 01 07       [0] OID prime256v1
        //     A1 44 03 42 00 <pub65>                    [1] BIT STRING (publicKey)
        $der = "\x30\x77"
             . "\x02\x01\x01"
             . "\x04\x20" . $priv32
             . "\xA0\x0A\x06\x08\x2A\x86\x48\xCE\x3D\x03\x01\x07"
             . "\xA1\x44\x03\x42\x00" . $pub65;
        return "-----BEGIN EC PRIVATE KEY-----\n" .
               chunk_split(base64_encode($der), 64, "\n") .
               "-----END EC PRIVATE KEY-----\n";
    }

    /** Convert a DER-encoded ECDSA signature to raw 64-byte (R||S). */
    private static function derToRawEcdsaSig(string $der): string
    {
        // Parse minimal ASN.1: SEQUENCE { INTEGER R, INTEGER S }.
        $pos = 0;
        if (ord($der[$pos++]) !== 0x30) throw new WebPushException('bad DER signature');
        $seqLen = ord($der[$pos++]);
        if ($seqLen & 0x80) {
            $n = $seqLen & 0x7F;
            $seqLen = 0;
            for ($i = 0; $i < $n; $i++) $seqLen = ($seqLen << 8) | ord($der[$pos++]);
        }
        $readInt = function () use ($der, &$pos) {
            if (ord($der[$pos++]) !== 0x02) throw new WebPushException('bad DER int');
            $len = ord($der[$pos++]);
            $bytes = substr($der, $pos, $len);
            $pos += $len;
            // Strip leading 0x00 (positive sign) and left-pad to 32.
            $bytes = ltrim($bytes, "\x00");
            if (strlen($bytes) > 32) throw new WebPushException('ECDSA component too large');
            return str_pad($bytes, 32, "\x00", STR_PAD_LEFT);
        };
        $r = $readInt();
        $s = $readInt();
        return $r . $s;
    }

    // ------------------------------------------------------------------
    // HTTP transport
    // ------------------------------------------------------------------
    private function httpPost(string $url, array $headers, string $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => $err];
        }
        // 201/202/204 are all success per RFC 8030.
        $ok = $code >= 200 && $code < 300;
        return ['ok' => $ok, 'status' => $code, 'body' => (string)$resp, 'error' => $ok ? null : "HTTP $code"];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------
    public static function b64uEncode(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }
    public static function b64uDecode(string $s): string
    {
        $pad = strlen($s) % 4;
        if ($pad) $s .= str_repeat('=', 4 - $pad);
        return base64_decode(strtr($s, '-_', '+/')) ?: '';
    }
}
