-- FleetSimplify VBMS — Schema
-- Database: fleetsimplify
-- Engine: InnoDB, charset utf8mb4

CREATE DATABASE IF NOT EXISTS fleetsimplify
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE fleetsimplify;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS incident_reports;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS mechanics;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------
-- users (drivers)
-- ---------------------------------------------------------------
CREATE TABLE users (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(120) NOT NULL,
  email       VARCHAR(190) NOT NULL,
  password    VARCHAR(255) NOT NULL,
  mobile      VARCHAR(10)  NOT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- mechanics
-- ---------------------------------------------------------------
CREATE TABLE mechanics (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name                VARCHAR(120) NOT NULL,
  email               VARCHAR(190) NOT NULL,
  password            VARCHAR(255) NOT NULL,
  mobile              VARCHAR(10)  NOT NULL,
  town                VARCHAR(80)  NOT NULL,
  address             VARCHAR(255) NOT NULL,
  licence_no          VARCHAR(60)  NOT NULL,
  business_name       VARCHAR(150) NOT NULL,
  service_description VARCHAR(255) NOT NULL DEFAULT '',
  availability        VARCHAR(120) NOT NULL DEFAULT '24/7',
  status              ENUM('pending','approved') NOT NULL DEFAULT 'pending',
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mechanics_email (email),
  KEY idx_mechanics_status (status),
  KEY idx_mechanics_town (town)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- admins
-- ---------------------------------------------------------------
CREATE TABLE admins (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name      VARCHAR(120) NOT NULL,
  email     VARCHAR(190) NOT NULL,
  password  VARCHAR(255) NOT NULL,
  role      VARCHAR(40)  NOT NULL DEFAULT 'super',
  PRIMARY KEY (id),
  UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- bookings
-- ---------------------------------------------------------------
CREATE TABLE bookings (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_number      VARCHAR(20)  NOT NULL,
  user_id             INT UNSIGNED NOT NULL,
  mechanic_id         INT UNSIGNED NULL,
  vehicle_plate       VARCHAR(20)  NOT NULL,
  vehicle_type        VARCHAR(40)  NOT NULL,
  breakdown_cause     VARCHAR(60)  NOT NULL,
  breakdown_location  VARCHAR(60)  NOT NULL,
  severity            VARCHAR(20)  NOT NULL DEFAULT 'Minor',
  status              ENUM('new','in_progress','completed','rejected') NOT NULL DEFAULT 'new',
  repair_method       VARCHAR(60)  NULL,
  downtime_reason     VARCHAR(60)  NULL,
  spare_parts_used    VARCHAR(255) NULL,
  repair_time_minutes INT UNSIGNED NULL,
  amount              DECIMAL(10,2) NOT NULL DEFAULT 0,
  driver_lat          DECIMAL(10,7) NULL,
  driver_lng          DECIMAL(10,7) NULL,
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bookings_number (booking_number),
  KEY idx_bookings_user (user_id),
  KEY idx_bookings_mechanic (mechanic_id),
  KEY idx_bookings_status (status),
  KEY idx_bookings_created (created_at),
  CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_bookings_mechanic FOREIGN KEY (mechanic_id) REFERENCES mechanics(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- messages (chat)
-- ---------------------------------------------------------------
CREATE TABLE messages (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id   INT UNSIGNED NOT NULL,
  sender_type  ENUM('user','mechanic') NOT NULL,
  sender_id    INT UNSIGNED NOT NULL,
  message      TEXT NOT NULL,
  sent_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_messages_booking (booking_id),
  CONSTRAINT fk_messages_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- ratings
-- ---------------------------------------------------------------
CREATE TABLE ratings (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id  INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  mechanic_id INT UNSIGNED NOT NULL,
  rating      TINYINT UNSIGNED NOT NULL,
  comment     TEXT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ratings_booking (booking_id),
  KEY idx_ratings_mechanic (mechanic_id),
  CONSTRAINT fk_ratings_booking  FOREIGN KEY (booking_id)  REFERENCES bookings(id)  ON DELETE CASCADE,
  CONSTRAINT fk_ratings_user     FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  CONSTRAINT fk_ratings_mechanic FOREIGN KEY (mechanic_id) REFERENCES mechanics(id) ON DELETE CASCADE,
  CONSTRAINT chk_ratings_range CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- locations (current mechanic GPS — one row per mechanic)
-- ---------------------------------------------------------------
CREATE TABLE locations (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mechanic_id INT UNSIGNED NOT NULL,
  latitude    DECIMAL(10,7) NOT NULL,
  longitude   DECIMAL(10,7) NOT NULL,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_locations_mechanic (mechanic_id),
  CONSTRAINT fk_locations_mechanic FOREIGN KEY (mechanic_id) REFERENCES mechanics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- payments
-- ---------------------------------------------------------------
CREATE TABLE payments (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id      INT UNSIGNED NOT NULL,
  user_id         INT UNSIGNED NOT NULL,
  amount          DECIMAL(10,2) NOT NULL,
  method          ENUM('mpesa','bank','card') NOT NULL,
  status          ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
  transaction_ref VARCHAR(40) NOT NULL,
  detail_masked   VARCHAR(60) NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payments_txn (transaction_ref),
  KEY idx_payments_booking (booking_id),
  KEY idx_payments_user (user_id),
  CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- incident_reports (driver / vehicle / road)
-- ---------------------------------------------------------------
CREATE TABLE incident_reports (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id  INT UNSIGNED NOT NULL,
  cause       ENUM('driver_handling','poor_vehicle_checks','road_conditions','other') NOT NULL,
  description TEXT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_incident_booking (booking_id),
  CONSTRAINT fk_incident_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
