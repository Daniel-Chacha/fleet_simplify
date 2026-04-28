-- FleetSimplify VBMS — Web Push subscriptions
-- Run this once after the main schema/seed. Stores per-mechanic browser
-- push subscription tokens. Each browser+device combination produces its
-- own row, so a mechanic may have several rows (work laptop, phone, etc.).

USE fleetsimplify;

CREATE TABLE IF NOT EXISTS push_subscriptions (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mechanic_id   INT UNSIGNED NOT NULL,
    endpoint      TEXT         NOT NULL,                         -- push service URL (FCM / Mozilla / etc.)
    endpoint_hash CHAR(64)     NOT NULL,                         -- sha256(endpoint), used for unique key (TEXT can't be UNIQUE directly)
    p256dh        VARCHAR(140) NOT NULL,                         -- base64url-encoded 65-byte uncompressed P-256 public key
    auth_secret   VARCHAR(40)  NOT NULL,                         -- base64url-encoded 16-byte auth secret
    user_agent    VARCHAR(255) NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at  TIMESTAMP    NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_endpoint_hash (endpoint_hash),
    KEY idx_push_mechanic (mechanic_id),
    CONSTRAINT fk_push_mechanic FOREIGN KEY (mechanic_id) REFERENCES mechanics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
