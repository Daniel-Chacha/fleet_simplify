-- FleetSimplify VBMS — Seed data
-- Default passwords (bcrypt-hashed below):
--   Admins:    Admin@123
--   Users:     User@123
--   Mechanics: Mech@123

USE fleetsimplify;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE incident_reports;
TRUNCATE TABLE payments;
TRUNCATE TABLE locations;
TRUNCATE TABLE ratings;
TRUNCATE TABLE messages;
TRUNCATE TABLE bookings;
TRUNCATE TABLE admins;
TRUNCATE TABLE mechanics;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------
-- admins (2)
-- ---------------------------------------------------------------
INSERT INTO admins (id, name, email, password, role) VALUES
(1, 'Daniel Otieno', 'admin@fleetsimplify.local', '$2y$12$gSyMRCnt49MOnMKA0VtblugI7..AVoqd3uhCkTi.9tpGMCUNMnt2O', 'super'),
(2, 'Operations Lead', 'ops@fleetsimplify.local',   '$2y$12$gSyMRCnt49MOnMKA0VtblugI7..AVoqd3uhCkTi.9tpGMCUNMnt2O', 'ops');

-- ---------------------------------------------------------------
-- users / drivers (20)
-- ---------------------------------------------------------------
INSERT INTO users (id, name, email, password, mobile, created_at) VALUES
(1,  'James Kariuki',     'james.kariuki@example.com',     '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345001', '2024-08-01 09:10:00'),
(2,  'Mary Wanjiru',      'mary.wanjiru@example.com',      '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345002', '2024-08-04 10:20:00'),
(3,  'Peter Otieno',      'peter.otieno@example.com',      '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345003', '2024-08-12 11:00:00'),
(4,  'Grace Achieng',     'grace.achieng@example.com',     '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345004', '2024-09-01 12:00:00'),
(5,  'Samuel Mwangi',     'samuel.mwangi@example.com',     '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345005', '2024-09-10 13:00:00'),
(6,  'Lucy Njeri',        'lucy.njeri@example.com',        '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345006', '2024-09-22 14:00:00'),
(7,  'David Kimani',      'david.kimani@example.com',      '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0112345007', '2024-10-05 09:30:00'),
(8,  'Faith Atieno',      'faith.atieno@example.com',      '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0112345008', '2024-10-18 11:45:00'),
(9,  'Robert Mutua',      'robert.mutua@example.com',      '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345009', '2024-11-02 08:20:00'),
(10, 'Esther Nduta',      'esther.nduta@example.com',      '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345010', '2024-11-12 10:00:00'),
(11, 'Joseph Kiprop',     'joseph.kiprop@example.com',     '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345011', '2024-12-01 09:00:00'),
(12, 'Susan Chebet',      'susan.chebet@example.com',      '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345012', '2024-12-15 13:30:00'),
(13, 'Michael Onyango',   'michael.onyango@example.com',   '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345013', '2025-01-04 12:10:00'),
(14, 'Linet Akinyi',      'linet.akinyi@example.com',      '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0112345014', '2025-01-22 14:00:00'),
(15, 'Charles Wekesa',    'charles.wekesa@example.com',    '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0112345015', '2025-02-03 09:45:00'),
(16, 'Beatrice Wambui',   'beatrice.wambui@example.com',   '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345016', '2025-02-18 11:30:00'),
(17, 'Patrick Njoroge',   'patrick.njoroge@example.com',   '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345017', '2025-03-01 08:30:00'),
(18, 'Diana Wairimu',     'diana.wairimu@example.com',     '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345018', '2025-03-15 10:30:00'),
(19, 'Kevin Otieno',      'kevin.otieno@example.com',      '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345019', '2025-04-02 12:00:00'),
(20, 'Anne Mukami',       'anne.mukami@example.com',       '$2y$12$BV4L6Q/l4vcwUcaw15/QR.ltbo42i/n1phArkne1cyIdiUqVGwV56', '0712345020', '2025-04-15 14:30:00');

-- ---------------------------------------------------------------
-- mechanics (20: 14 approved, 6 pending)
-- ---------------------------------------------------------------
INSERT INTO mechanics (id, name, email, password, mobile, town, address, licence_no, business_name, service_description, availability, status, created_at) VALUES
(1,  'Stephen Karanja',  'steve.karanja@autohub.co.ke',  '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000101', 'Nairobi',  'Industrial Area, Lusaka Rd',     'MEC-NBO-001', 'AutoHub Garage',           'Towing,Engine Repairs,Brake Repairs',           '24/7',           'approved', '2024-06-01 09:00:00'),
(2,  'Catherine Mumbi',  'cathy.mumbi@quickfix.co.ke',   '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000102', 'Nairobi',  'Westlands, Waiyaki Way',         'MEC-NBO-002', 'QuickFix Auto',            'Tire Services,Battery Services,Engine Repairs', 'Mon-Sat 7am-7pm','approved', '2024-06-10 10:00:00'),
(3,  'George Ouma',      'george.ouma@coastalmech.co.ke','$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000103', 'Mombasa',  'Nyali, Links Road',              'MEC-MSA-003', 'Coastal Mechanics',        'Towing,Tire Services,Brake Repairs',            '24/7',           'approved', '2024-06-15 11:00:00'),
(4,  'Mercy Nyambura',   'mercy.n@nakurugarage.co.ke',   '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000104', 'Nakuru',   'Kenyatta Avenue',                'MEC-NKR-004', 'Nakuru Auto Garage',       'Engine Repairs,Brake Repairs,Battery Services', 'Mon-Fri 8am-6pm','approved', '2024-07-01 09:30:00'),
(5,  'Brian Cheruiyot',  'brian.c@eldoretmotors.co.ke',  '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000105', 'Eldoret',  'Uganda Road',                    'MEC-ELD-005', 'Eldoret Motors',           'Towing,Battery Services',                       '24/7',           'approved', '2024-07-10 12:00:00'),
(6,  'Nancy Wairimu',    'nancy.w@thikatires.co.ke',     '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000106', 'Thika',    'Garissa Road',                   'MEC-THK-006', 'Thika Tire Centre',        'Tire Services,Brake Repairs',                   'Mon-Sun 6am-9pm','approved', '2024-07-22 13:30:00'),
(7,  'Tom Odhiambo',     'tom.o@kisumuauto.co.ke',       '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000107', 'Kisumu',   'Oginga Odinga Street',           'MEC-KSM-007', 'Kisumu Auto Solutions',    'Towing,Engine Repairs',                         '24/7',           'approved', '2024-08-05 09:00:00'),
(8,  'Esther Mutindi',   'esther.m@autocare.co.ke',      '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000108', 'Nairobi',  'Karen Plains Road',              'MEC-NBO-008', 'AutoCare Karen',           'Engine Repairs,Battery Services,Tire Services', 'Mon-Sat 8am-6pm','approved', '2024-08-19 10:00:00'),
(9,  'James Kiplangat',  'james.k@rapidresponse.co.ke',  '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000109', 'Nairobi',  'Mombasa Road, South B',          'MEC-NBO-009', 'Rapid Response Towing',    'Towing,Battery Services',                       '24/7',           'approved', '2024-09-02 11:00:00'),
(10, 'Pauline Atieno',   'pauline.a@mombasamotors.co.ke','$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000110', 'Mombasa',  'Mama Ngina Drive',               'MEC-MSA-010', 'Mombasa Motors',           'Tire Services,Brake Repairs,Engine Repairs',    'Mon-Sat 7am-8pm','approved', '2024-09-15 12:00:00'),
(11, 'Frank Wekesa',     'frank.w@kitalegarage.co.ke',   '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000111', 'Kitale',   'Kenyatta Street',                'MEC-KTL-011', 'Kitale Garage',            'Engine Repairs,Towing',                         'Mon-Sun 7am-7pm','approved', '2024-10-04 09:30:00'),
(12, 'Rose Wanjiku',     'rose.w@nyerimotorcare.co.ke',  '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000112', 'Nyeri',    'Kimathi Way',                    'MEC-NYR-012', 'Nyeri Motor Care',         'Battery Services,Tire Services',                '24/7',           'approved', '2024-10-20 10:30:00'),
(13, 'Peter Kioko',      'peter.k@machakosauto.co.ke',   '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000113', 'Machakos', 'Machakos Town',                  'MEC-MCK-013', 'Machakos Auto',            'Towing,Engine Repairs,Brake Repairs',           'Mon-Sat 8am-6pm','approved', '2024-11-08 11:30:00'),
(14, 'Joyce Naliaka',    'joyce.n@bungomafleet.co.ke',   '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000114', 'Bungoma',  'Moi Avenue',                     'MEC-BNG-014', 'Bungoma Fleet Services',   'Engine Repairs,Battery Services',               'Mon-Fri 8am-6pm','approved', '2024-12-01 12:30:00'),
(15, 'Antony Mwendwa',   'antony.m@kituiautocenter.co.ke','$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000115', 'Kitui',    'Kalundu Market',                 'MEC-KTU-015', 'Kitui Auto Centre',        'Tire Services,Brake Repairs',                   'Mon-Sat 7am-7pm','pending',  '2025-02-05 09:00:00'),
(16, 'Lilian Cherono',   'lilian.c@kerichoautos.co.ke',  '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000116', 'Kericho',  'Tea Avenue',                     'MEC-KRC-016', 'Kericho Autos',            'Engine Repairs,Towing',                         '24/7',           'pending',  '2025-02-20 10:00:00'),
(17, 'Charles Munyua',   'charles.m@embufleet.co.ke',    '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000117', 'Embu',     'Stadium Road',                   'MEC-EMB-017', 'Embu Fleet Garage',        'Battery Services,Brake Repairs',                'Mon-Sat 8am-6pm','pending',  '2025-03-04 11:00:00'),
(18, 'Maureen Adhiambo', 'maureen.a@homabaymech.co.ke',  '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000118', 'Homa Bay', 'Lake Road',                      'MEC-HBY-018', 'Homa Bay Mechanics',       'Tire Services,Engine Repairs',                  'Mon-Sun 7am-8pm','pending',  '2025-03-18 12:00:00'),
(19, 'Eric Kibet',       'eric.k@narokoffroad.co.ke',    '$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000119', 'Narok',    'Narok Town Centre',              'MEC-NRK-019', 'Narok Off-Road Auto',      'Towing,Tire Services',                          '24/7',           'pending',  '2025-04-02 13:00:00'),
(20, 'Brenda Wairimu',   'brenda.w@nyandaruagarage.co.ke','$2y$12$zdh/qDHc.jFDlNOcO3e5gu4VcKamM7BJTJDMLKw2pq4NcPadwQR1.', '0722000120', 'Nyandarua','Ol Kalou',                       'MEC-NYD-020', 'Nyandarua Garage',         'Battery Services,Engine Repairs',               'Mon-Sat 8am-6pm','pending',  '2025-04-15 14:00:00');

-- ---------------------------------------------------------------
-- bookings (30) — varied across last 12 months for trend chart
-- ---------------------------------------------------------------
INSERT INTO bookings (id, booking_number, user_id, mechanic_id, vehicle_plate, vehicle_type, breakdown_cause, breakdown_location, severity, status, repair_method, downtime_reason, spare_parts_used, repair_time_minutes, amount, driver_lat, driver_lng, created_at, updated_at) VALUES
(1,  'BK-2024-0001', 1,  1,  'KCA 123A', 'Trucks',      'Engine Failure',     'Highway',     'Major',    'completed',   'Towed then Repaired',  'Repair Time',         'Tires,Filters',       210, 15500.00, -1.3192, 36.8528, '2024-08-05 09:30:00', '2024-08-05 13:30:00'),
(2,  'BK-2024-0002', 2,  2,  'KCB 456B', 'Cars',        'Battery Problems',   'City Roads',  'Minor',    'completed',   'On-site Repair',       'Repair Time',         'Batteries',            60,  5200.00, -1.2640, 36.8030, '2024-08-22 11:00:00', '2024-08-22 12:30:00'),
(3,  'BK-2024-0003', 3,  3,  'KCC 789C', 'Vans',        'Tire Punctures',     'Rural Roads', 'Moderate', 'completed',   'On-site Repair',       'Repair Time',         'Tires',                45,  3500.00, -4.0435, 39.6682, '2024-09-03 14:15:00', '2024-09-03 15:30:00'),
(4,  'BK-2024-0004', 4,  4,  'KCD 012D', 'Cars',        'Electrical Faults',  'Workshop',    'Moderate', 'completed',   'Workshop Repair',      'Waiting for Parts',   'Alternators,Filters',  180, 12800.00, -0.3031, 36.0800, '2024-09-15 09:00:00', '2024-09-15 12:30:00'),
(5,  'BK-2024-0005', 5,  5,  'KCE 345E', 'Buses',       'Fuel System Issues', 'Highway',     'Critical', 'completed',   'Towed then Repaired',  'Tow Delays',          'Filters',             300, 24500.00,  0.5143, 35.2698, '2024-10-02 08:30:00', '2024-10-02 14:00:00'),
(6,  'BK-2024-0006', 6,  6,  'KCF 678F', 'Cars',        'Tire Punctures',     'City Roads',  'Minor',    'completed',   'On-site Repair',       'Repair Time',         'Tires',                30,  2800.00, -1.0395, 37.0691, '2024-10-18 12:00:00', '2024-10-18 13:00:00'),
(7,  'BK-2024-0007', 7,  7,  'KCG 901G', 'Trucks',      'Engine Failure',     'Highway',     'Major',    'completed',   'Workshop Repair',      'Approval Delays',     'Filters,Tires',       240, 19200.00, -0.0917, 34.7680, '2024-11-04 10:30:00', '2024-11-04 15:00:00'),
(8,  'BK-2024-0008', 8,  8,  'KCH 234H', 'Motorcycles', 'Brake Repairs',      'City Roads',  'Moderate', 'completed',   'On-site Repair',       'Repair Time',         'Brake Pads',           75,  4500.00, -1.3192, 36.7077, '2024-11-19 13:00:00', '2024-11-19 14:30:00'),
(9,  'BK-2024-0009', 9,  9,  'KCJ 567J', 'Vans',        'Battery Problems',   'Parking Yard','Minor',    'completed',   'On-site Repair',       'Repair Time',         'Batteries',            40,  6500.00, -1.3232, 36.8500, '2024-12-02 09:30:00', '2024-12-02 10:30:00'),
(10, 'BK-2024-0010', 10, 10, 'KCK 890K', 'Cars',        'Electrical Faults',  'City Roads',  'Moderate', 'completed',   'Workshop Repair',      'Waiting for Parts',   'Alternators',         150, 11500.00, -4.0570, 39.6695, '2024-12-15 11:00:00', '2024-12-15 14:00:00'),
(11, 'BK-2024-0011', 1,  1,  'KCA 123A', 'Trucks',      'Fuel System Issues', 'Highway',     'Critical', 'completed',   'Towed then Repaired',  'Tow Delays',          'Filters,Tires',       330, 28500.00, -1.3192, 36.8528, '2024-12-28 08:00:00', '2024-12-29 13:30:00'),
(12, 'BK-2025-0012', 2,  2,  'KCB 456B', 'Buses',       'Tire Punctures',     'Rural Roads', 'Moderate', 'completed',   'On-site Repair',       'Repair Time',         'Tires',                90,  8200.00, -1.2640, 36.8030, '2025-01-08 12:30:00', '2025-01-08 14:30:00'),
(13, 'BK-2025-0013', 13, 11, 'KCN 789N', 'Cars',        'Engine Failure',     'Workshop',    'Major',    'completed',   'Workshop Repair',      'Approval Delays',     'Filters,Alternators', 270, 22500.00,  1.0157, 35.0024, '2025-01-22 09:30:00', '2025-01-22 14:30:00'),
(14, 'BK-2025-0014', 4,  12, 'KCD 012D', 'Motorcycles', 'Brake Repairs',      'City Roads',  'Minor',    'completed',   'On-site Repair',       'Repair Time',         'Brake Pads',           50,  3800.00, -0.4201, 36.9476, '2025-02-04 13:00:00', '2025-02-04 14:30:00'),
(15, 'BK-2025-0015', 5,  13, 'KCE 345E', 'Vans',        'Battery Problems',   'Parking Yard','Minor',    'completed',   'On-site Repair',       'Repair Time',         'Batteries',            55,  5800.00, -1.5176, 37.2634, '2025-02-15 10:00:00', '2025-02-15 11:30:00'),
(16, 'BK-2025-0016', 6,  14, 'KCF 678F', 'Trucks',      'Electrical Faults',  'Highway',     'Major',    'completed',   'Towed then Repaired',  'Waiting for Parts',   'Alternators,Filters', 215, 18200.00,  0.5635, 34.5606, '2025-02-26 11:30:00', '2025-02-26 15:30:00'),
(17, 'BK-2025-0017', 1,  1,  'KCA 123A', 'Cars',        'Tire Punctures',     'City Roads',  'Minor',    'completed',   'On-site Repair',       'Repair Time',         'Tires',                35,  3000.00, -1.3192, 36.8528, '2025-03-03 09:30:00', '2025-03-03 10:30:00'),
(18, 'BK-2025-0018', 2,  3,  'KCB 456B', 'Buses',       'Fuel System Issues', 'Highway',     'Major',    'completed',   'Workshop Repair',      'Repair Time',         'Filters',             190, 16500.00, -4.0435, 39.6682, '2025-03-18 10:00:00', '2025-03-18 13:30:00'),
(19, 'BK-2025-0019', 3,  4,  'KCC 789C', 'Vans',        'Engine Failure',     'Workshop',    'Critical', 'completed',   'Vehicle Replacement',  'Approval Delays',     'Filters,Tires',       420, 45000.00, -0.3031, 36.0800, '2025-04-01 08:30:00', '2025-04-01 16:00:00'),
(20, 'BK-2025-0020', 4,  5,  'KCD 012D', 'Cars',        'Battery Problems',   'Parking Yard','Minor',    'completed',   'On-site Repair',       'Repair Time',         'Batteries',            45,  5500.00,  0.5143, 35.2698, '2025-04-08 13:30:00', '2025-04-08 14:30:00'),
(21, 'BK-2025-0021', 1,  6,  'KCW 123W', 'Cars',        'Brake Repairs',      'City Roads',  'Moderate', 'in_progress', NULL,                   NULL,                  NULL,                  NULL,  6500.00, -1.0395, 37.0691, '2025-04-19 09:30:00', '2025-04-19 09:45:00'),
(22, 'BK-2025-0022', 2,  7,  'KCX 456X', 'Trucks',      'Engine Failure',     'Highway',     'Critical', 'in_progress', NULL,                   NULL,                  NULL,                  NULL, 18000.00, -0.0917, 34.7680, '2025-04-20 11:00:00', '2025-04-20 11:30:00'),
(23, 'BK-2025-0023', 3,  8,  'KCY 789Y', 'Vans',        'Tire Punctures',     'Rural Roads', 'Minor',    'in_progress', NULL,                   NULL,                  NULL,                  NULL,  3500.00, -1.3192, 36.7077, '2025-04-21 14:00:00', '2025-04-21 14:15:00'),
(24, 'BK-2025-0024', 1,  9,  'KCA 123A', 'Cars',        'Electrical Faults',  'City Roads',  'Moderate', 'new',         NULL,                   NULL,                  NULL,                  NULL,  9500.00, -1.3232, 36.8500, '2025-04-22 10:00:00', '2025-04-22 10:00:00'),
(25, 'BK-2025-0025', 2,  10, 'KCB 456B', 'Motorcycles', 'Battery Problems',   'Parking Yard','Minor',    'new',         NULL,                   NULL,                  NULL,                  NULL,  4200.00, -4.0570, 39.6695, '2025-04-23 12:00:00', '2025-04-23 12:00:00'),
(26, 'BK-2025-0026', 8,  11, 'KCH 234H', 'Trucks',      'Fuel System Issues', 'Highway',     'Major',    'new',         NULL,                   NULL,                  NULL,                  NULL, 21000.00,  1.0157, 35.0024, '2025-04-24 08:30:00', '2025-04-24 08:30:00'),
(27, 'BK-2025-0027', 7,  12, 'KCG 901G', 'Buses',       'Engine Failure',     'Workshop',    'Major',    'new',         NULL,                   NULL,                  NULL,                  NULL, 23500.00, -0.4201, 36.9476, '2025-04-24 13:00:00', '2025-04-24 13:00:00'),
(28, 'BK-2025-0028', 1,  NULL,'KCA 123A','Cars',        'Tire Punctures',     'City Roads',  'Minor',    'new',         NULL,                   NULL,                  NULL,                  NULL,  3000.00, -1.2921, 36.8219, '2025-04-25 07:30:00', '2025-04-25 07:30:00'),
(29, 'BK-2024-0029', 11, 13, 'KDE 567E', 'Vans',        'Brake Repairs',      'City Roads',  'Moderate', 'rejected',    NULL,                   NULL,                  NULL,                  NULL,  6800.00, -1.5176, 37.2634, '2024-11-25 10:30:00', '2024-11-25 10:45:00'),
(30, 'BK-2025-0030', 3,  14, 'KCC 789C', 'Cars',        'Electrical Faults',  'Highway',     'Major',    'rejected',    NULL,                   NULL,                  NULL,                  NULL, 11000.00,  0.5635, 34.5606, '2025-03-12 11:00:00', '2025-03-12 11:30:00');

-- ---------------------------------------------------------------
-- incident_reports (12)
-- ---------------------------------------------------------------
INSERT INTO incident_reports (booking_id, cause, description, created_at) VALUES
(1,  'poor_vehicle_checks', 'Driver had skipped scheduled service.',                 '2024-08-05 13:00:00'),
(4,  'driver_handling',     'Aggressive shifting may have stressed the alternator.', '2024-09-15 12:30:00'),
(5,  'road_conditions',     'Diesel contaminated at remote fuel station.',           '2024-10-02 13:00:00'),
(7,  'driver_handling',     'Overloading exceeded recommended GVW.',                 '2024-11-04 14:30:00'),
(8,  'road_conditions',     'Pothole caused front-disk pad damage.',                 '2024-11-19 14:00:00'),
(11, 'other',               'Suspected fuel adulteration; sample taken.',            '2024-12-29 13:00:00'),
(13, 'poor_vehicle_checks', 'No oil top-up since last quarter.',                     '2025-01-22 14:00:00'),
(14, 'road_conditions',     'Wet road; emergency stop wore pads.',                   '2025-02-04 14:00:00'),
(16, 'driver_handling',     'Frequent night driving with auxiliary lights.',         '2025-02-26 15:00:00'),
(18, 'other',                'Sediment in fuel filter; replace at next service.',    '2025-03-18 13:00:00'),
(19, 'poor_vehicle_checks', 'Engine seized — long-term low oil.',                    '2025-04-01 15:30:00'),
(7,  'road_conditions',     'Highway stretch under construction; rough surface.',    '2024-11-04 15:00:00');

-- ---------------------------------------------------------------
-- ratings (15 — completed bookings)
-- ---------------------------------------------------------------
INSERT INTO ratings (booking_id, user_id, mechanic_id, rating, comment, created_at) VALUES
(1,  1,  1,  5, 'Towed quickly and got me back on the road same day. Top notch.', '2024-08-05 14:00:00'),
(2,  2,  2,  4, 'Professional service, fair price.',                              '2024-08-22 13:00:00'),
(3,  3,  3,  5, 'Arrived in 20 minutes — saved my trip.',                         '2024-09-03 16:00:00'),
(4,  4,  4,  4, 'Good diagnosis, parts took a bit long.',                         '2024-09-15 13:00:00'),
(5,  5,  5,  5, 'Best towing experience on the highway.',                         '2024-10-02 14:30:00'),
(6,  6,  6,  4, 'Quick tire change, polite mechanic.',                            '2024-10-18 13:30:00'),
(7,  7,  7,  3, 'Repair was solid but waiting period was long.',                  '2024-11-04 16:00:00'),
(8,  8,  8,  5, 'Excellent brake job. Very thorough.',                            '2024-11-19 15:00:00'),
(10, 10, 10, 4, 'Helpful explanations and clean workshop.',                       '2024-12-15 14:30:00'),
(12, 2,  2,  5, 'Came out to the rural road and fixed it on-site.',               '2025-01-08 15:00:00'),
(13, 13, 11, 4, 'Quality engine work, will return.',                              '2025-01-22 15:00:00'),
(14, 4,  12, 5, 'Very fast brake replacement.',                                   '2025-02-04 15:00:00'),
(15, 5,  13, 4, 'Good price on the new battery.',                                 '2025-02-15 12:00:00'),
(18, 2,  3,  3, 'Decent repair but a bit pricey.',                                '2025-03-18 14:00:00'),
(20, 4,  5,  5, 'Friendly and prompt — recommended!',                             '2025-04-08 15:00:00');

-- ---------------------------------------------------------------
-- locations (1 per mechanic — Kenyan city centres)
-- ---------------------------------------------------------------
INSERT INTO locations (mechanic_id, latitude, longitude, updated_at) VALUES
(1,  -1.3192, 36.8528, '2025-04-25 07:00:00'),
(2,  -1.2640, 36.8030, '2025-04-25 07:05:00'),
(3,  -4.0435, 39.6682, '2025-04-25 07:10:00'),
(4,  -0.3031, 36.0800, '2025-04-25 07:15:00'),
(5,   0.5143, 35.2698, '2025-04-25 07:20:00'),
(6,  -1.0395, 37.0691, '2025-04-25 07:25:00'),
(7,  -0.0917, 34.7680, '2025-04-25 07:30:00'),
(8,  -1.3192, 36.7077, '2025-04-25 07:35:00'),
(9,  -1.3232, 36.8500, '2025-04-25 07:40:00'),
(10, -4.0570, 39.6695, '2025-04-25 07:45:00'),
(11,  1.0157, 35.0024, '2025-04-25 07:50:00'),
(12, -0.4201, 36.9476, '2025-04-25 07:55:00'),
(13, -1.5176, 37.2634, '2025-04-25 08:00:00'),
(14,  0.5635, 34.5606, '2025-04-25 08:05:00'),
(15, -1.3760, 38.0107, '2025-04-25 08:10:00'),
(16, -0.3672, 35.2839, '2025-04-25 08:15:00'),
(17, -0.5380, 37.4593, '2025-04-25 08:20:00'),
(18, -0.5273, 34.4569, '2025-04-25 08:25:00'),
(19, -1.0867, 35.8769, '2025-04-25 08:30:00'),
(20, -0.4347, 36.4307, '2025-04-25 08:35:00');

-- ---------------------------------------------------------------
-- payments (18 — mix of methods/statuses)
-- ---------------------------------------------------------------
INSERT INTO payments (booking_id, user_id, amount, method, status, transaction_ref, detail_masked, created_at) VALUES
(1,  1,  15500.00, 'mpesa', 'completed', 'TXN-A1B2C3D4E5F60001', '0712345001',           '2024-08-05 14:30:00'),
(2,  2,   5200.00, 'mpesa', 'completed', 'TXN-A1B2C3D4E5F60002', '0712345002',           '2024-08-22 13:30:00'),
(3,  3,   3500.00, 'card',  'completed', 'TXN-A1B2C3D4E5F60003', '**** **** **** 1234',  '2024-09-03 16:30:00'),
(4,  4,  12800.00, 'bank',  'completed', 'TXN-A1B2C3D4E5F60004', 'KCB ****4567',         '2024-09-15 13:30:00'),
(5,  5,  24500.00, 'mpesa', 'completed', 'TXN-A1B2C3D4E5F60005', '0712345005',           '2024-10-02 15:00:00'),
(6,  6,   2800.00, 'mpesa', 'completed', 'TXN-A1B2C3D4E5F60006', '0712345006',           '2024-10-18 14:00:00'),
(7,  7,  19200.00, 'card',  'completed', 'TXN-A1B2C3D4E5F60007', '**** **** **** 9876',  '2024-11-04 16:30:00'),
(8,  8,   4500.00, 'mpesa', 'completed', 'TXN-A1B2C3D4E5F60008', '0112345008',           '2024-11-19 15:30:00'),
(9,  9,   6500.00, 'mpesa', 'failed',    'TXN-A1B2C3D4E5F60009', '0712345009',           '2024-12-02 11:00:00'),
(10, 10, 11500.00, 'bank',  'completed', 'TXN-A1B2C3D4E5F60010', 'EQB ****1122',         '2024-12-15 15:00:00'),
(11, 1,  28500.00, 'card',  'completed', 'TXN-A1B2C3D4E5F60011', '**** **** **** 5544',  '2024-12-29 14:00:00'),
(13, 13, 22500.00, 'mpesa', 'completed', 'TXN-A1B2C3D4E5F60012', '0712345013',           '2025-01-22 15:30:00'),
(15, 5,   5800.00, 'mpesa', 'completed', 'TXN-A1B2C3D4E5F60013', '0712345005',           '2025-02-15 12:30:00'),
(18, 2,  16500.00, 'bank',  'completed', 'TXN-A1B2C3D4E5F60014', 'COOP ****7788',        '2025-03-18 14:30:00'),
(19, 3,  45000.00, 'card',  'failed',    'TXN-A1B2C3D4E5F60015', '**** **** **** 3322',  '2025-04-01 16:30:00'),
(20, 4,   5500.00, 'mpesa', 'completed', 'TXN-A1B2C3D4E5F60016', '0712345004',           '2025-04-08 15:30:00'),
(21, 1,   6500.00, 'mpesa', 'pending',   'TXN-A1B2C3D4E5F60017', '0712345001',           '2025-04-19 10:00:00'),
(22, 2,  18000.00, 'mpesa', 'pending',   'TXN-A1B2C3D4E5F60018', '0712345002',           '2025-04-20 12:00:00');

-- ---------------------------------------------------------------
-- messages (40 — across 6 active/completed bookings)
-- ---------------------------------------------------------------
INSERT INTO messages (booking_id, sender_type, sender_id, message, sent_at) VALUES
(21, 'user',     1, 'Hi, my brakes are squealing badly. I''m near Thika town centre.', '2025-04-19 09:31:00'),
(21, 'mechanic', 6, 'Hello James, I''m on my way. ETA ~15 minutes.',                  '2025-04-19 09:32:00'),
(21, 'user',     1, 'Thanks. The car is the white Toyota by the petrol station.',     '2025-04-19 09:33:00'),
(21, 'mechanic', 6, 'Got it. I have brake pads in stock — should be quick.',          '2025-04-19 09:34:00'),
(21, 'user',     1, 'Awesome, I''ll wait.',                                           '2025-04-19 09:35:00'),
(21, 'mechanic', 6, 'Pulling up now. Hazards flashing on a blue van.',                '2025-04-19 09:46:00'),
(21, 'user',     1, 'I see you, waving.',                                             '2025-04-19 09:46:30'),

(22, 'user',     2, 'Truck won''t start. Highway near Kisumu turn-off.',              '2025-04-20 11:01:00'),
(22, 'mechanic', 7, 'On it. Sending tow now. ETA 25 minutes.',                        '2025-04-20 11:02:00'),
(22, 'user',     2, 'Hazards on, parked on the shoulder.',                            '2025-04-20 11:03:00'),
(22, 'mechanic', 7, 'Got your location pin. Stay inside the cab.',                    '2025-04-20 11:04:00'),
(22, 'user',     2, 'Will do. Thanks.',                                               '2025-04-20 11:05:00'),
(22, 'mechanic', 7, 'Almost there. 10 mins.',                                         '2025-04-20 11:20:00'),

(23, 'user',     3, 'Got a flat near the village junction.',                          '2025-04-21 14:01:00'),
(23, 'mechanic', 8, 'Hi Peter, coming with a portable jack and patch kit.',           '2025-04-21 14:02:00'),
(23, 'user',     3, 'Spare is also flat unfortunately.',                              '2025-04-21 14:03:00'),
(23, 'mechanic', 8, 'No problem, I''ll bring a tube as well.',                        '2025-04-21 14:04:00'),

(1,  'user',     1, 'Engine smoking, can''t move.',                                   '2024-08-05 09:31:00'),
(1,  'mechanic', 1, 'I''m dispatching the tow truck now.',                            '2024-08-05 09:33:00'),
(1,  'user',     1, 'Thank you!',                                                     '2024-08-05 09:34:00'),
(1,  'mechanic', 1, 'On the way. ETA 15 mins.',                                       '2024-08-05 09:35:00'),
(1,  'user',     1, 'Ok.',                                                            '2024-08-05 09:36:00'),
(1,  'mechanic', 1, 'Loaded the truck. Heading to workshop.',                         '2024-08-05 10:00:00'),
(1,  'user',     1, 'Will I make it back today?',                                     '2024-08-05 11:00:00'),
(1,  'mechanic', 1, 'Yes — by 1:30pm.',                                               '2024-08-05 11:01:00'),
(1,  'mechanic', 1, 'Repair done. Test driven.',                                      '2024-08-05 13:25:00'),
(1,  'user',     1, 'Excellent! Coming to pick it up.',                               '2024-08-05 13:30:00'),

(7,  'user',     7, 'Truck overheating on the bypass.',                               '2024-11-04 10:31:00'),
(7,  'mechanic', 7, 'Tow on the way. 30 minutes.',                                    '2024-11-04 10:33:00'),
(7,  'user',     7, 'Coolant gone empty.',                                            '2024-11-04 10:35:00'),
(7,  'mechanic', 7, 'Bringing coolant. Don''t restart engine.',                       '2024-11-04 10:36:00'),
(7,  'user',     7, 'Understood.',                                                    '2024-11-04 10:37:00'),
(7,  'mechanic', 7, 'Reached you. Inspecting now.',                                   '2024-11-04 11:05:00'),

(13, 'user',     13, 'Engine misfiring badly on cold start.',                         '2025-01-22 09:31:00'),
(13, 'mechanic', 11, 'Bring it to the workshop, sounds like alternator.',             '2025-01-22 09:32:00'),
(13, 'user',     13, 'Towed. Arriving in an hour.',                                   '2025-01-22 09:35:00'),
(13, 'mechanic', 11, 'Alternator replaced. Test driving now.',                        '2025-01-22 13:30:00'),
(13, 'user',     13, 'Great, on my way over.',                                        '2025-01-22 13:45:00'),
(13, 'mechanic', 11, 'Done. Ready for collection.',                                   '2025-01-22 14:25:00');
