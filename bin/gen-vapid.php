<?php
/**
 * FleetSimplify VBMS — VAPID keypair generator.
 *
 * Run from the project root:
 *   php bin/gen-vapid.php
 *
 * Prints a new pair of base64url-encoded VAPID keys. Paste them into
 * config/push.php (FS_VAPID_PUBLIC and FS_VAPID_PRIVATE).
 *
 * The same key pair must be used for every push, otherwise existing browser
 * subscriptions immediately become invalid (the public key is part of the
 * subscription handshake).
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }

$key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
if ($key === false) { fwrite(STDERR, "openssl_pkey_new failed\n"); exit(1); }

$details = openssl_pkey_get_details($key);
$x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
$y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
$d = str_pad($details['ec']['d'], 32, "\x00", STR_PAD_LEFT);
$pub65 = "\x04" . $x . $y;

$b64u = function (string $s): string {
    return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
};

echo "Generated a fresh VAPID keypair.\n";
echo "Paste these into config/push.php (replace the existing constants):\n\n";
echo "const FS_VAPID_PUBLIC  = '" . $b64u($pub65) . "';\n";
echo "const FS_VAPID_PRIVATE = '" . $b64u($d)     . "';\n\n";
echo "After updating, every existing push_subscriptions row is invalidated\n";
echo "and mechanics must click \"Enable notifications\" again.\n";
