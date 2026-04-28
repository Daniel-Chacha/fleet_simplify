<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/push.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'POST only.'], 405);
}
verify_csrf($_POST['csrf'] ?? '');
$u = require_any_role();
$pdo = db();

$action = $_POST['action'] ?? '';
$is_ajax = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
        || (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest');

function reply_ok(bool $is_ajax, string $redirect_to, string $msg): void {
    if ($is_ajax) json_response(['ok' => true]);
    flash('success', $msg);
    redirect($redirect_to);
}
function reply_err(bool $is_ajax, string $redirect_to, string $msg, int $code = 400): void {
    if ($is_ajax) json_response(['ok' => false, 'error' => $msg], $code);
    flash('error', $msg);
    redirect($redirect_to);
}

try {
    switch ($action) {

        case 'create': {
            if ($u['role'] !== 'user') reply_err($is_ajax, 'index.php', 'Not authorized.', 403);
            $mid = (int)($_POST['mechanic_id'] ?? 0);
            $plate = trim($_POST['vehicle_plate'] ?? '');
            $vtype = trim($_POST['vehicle_type'] ?? '');
            $cause = trim($_POST['breakdown_cause'] ?? '');
            $loc   = trim($_POST['breakdown_location'] ?? '');
            $sev   = trim($_POST['severity'] ?? 'Minor');
            $lat   = isset($_POST['driver_lat']) && $_POST['driver_lat'] !== '' ? (float)$_POST['driver_lat'] : null;
            $lng   = isset($_POST['driver_lng']) && $_POST['driver_lng'] !== '' ? (float)$_POST['driver_lng'] : null;

            if ($mid <= 0 || $plate === '' || $vtype === '' || $cause === '' || $loc === '') {
                reply_err($is_ajax, 'user/find-services.php', 'Missing required fields.');
            }
            // Verify mechanic is approved
            $st = $pdo->prepare("SELECT id FROM mechanics WHERE id = :id AND status='approved'");
            $st->execute([':id' => $mid]);
            if (!$st->fetchColumn()) reply_err($is_ajax, 'user/find-services.php', 'Mechanic unavailable.');

            $bn = gen_booking_number();
            // Default amount estimate per severity
            $amountMap = ['Minor'=>3500.00, 'Moderate'=>7500.00, 'Major'=>15000.00, 'Critical'=>25000.00];
            $amt = $amountMap[$sev] ?? 5000.00;

            $st = $pdo->prepare('INSERT INTO bookings (booking_number, user_id, mechanic_id, vehicle_plate, vehicle_type, breakdown_cause, breakdown_location, severity, status, amount, driver_lat, driver_lng)
                                 VALUES (:bn, :uid, :mid, :p, :vt, :c, :l, :sev, "new", :amt, :lat, :lng)');
            $st->execute([
                ':bn'=>$bn, ':uid'=>$u['uid'], ':mid'=>$mid, ':p'=>$plate, ':vt'=>$vtype,
                ':c'=>$cause, ':l'=>$loc, ':sev'=>$sev, ':amt'=>$amt, ':lat'=>$lat, ':lng'=>$lng
            ]);
            $bid = (int)$pdo->lastInsertId();

            // Background push to the mechanic's registered devices.
            // notify_mechanic returns silently if no subscriptions exist, so this
            // is safe even before the mechanic has clicked "Enable notifications".
            $driverName = current_user()['name'] ?? 'A driver';
            notify_mechanic($mid, [
                'title' => 'New booking — ' . $bn,
                'body'  => $driverName . ' needs help: ' . $cause . ' (' . $sev . ', ' . $loc . ')',
                'url'   => url('mechanic/chat.php?booking_id=' . $bid),
                'tag'   => 'fs-booking-' . $bid,
            ]);

            flash('success', 'Request sent. The mechanic has been notified.');
            redirect('user/chat.php?booking_id=' . $bid);
        }

        case 'accept': {
            if ($u['role'] !== 'mechanic') reply_err($is_ajax, 'index.php', 'Not authorized.', 403);
            $bid = (int)($_POST['booking_id'] ?? 0);
            $st = $pdo->prepare("UPDATE bookings SET status='in_progress' WHERE id=:b AND mechanic_id=:m AND status='new'");
            $st->execute([':b'=>$bid, ':m'=>$u['uid']]);
            if (!$st->rowCount()) reply_err($is_ajax, 'mechanic/dashboard.php', 'Already actioned.');
            reply_ok($is_ajax, 'mechanic/dashboard.php', 'Job accepted.');
        }

        case 'reject': {
            if ($u['role'] !== 'mechanic') reply_err($is_ajax, 'index.php', 'Not authorized.', 403);
            $bid = (int)($_POST['booking_id'] ?? 0);
            $st = $pdo->prepare("UPDATE bookings SET status='rejected' WHERE id=:b AND mechanic_id=:m AND status='new'");
            $st->execute([':b'=>$bid, ':m'=>$u['uid']]);
            if (!$st->rowCount()) reply_err($is_ajax, 'mechanic/dashboard.php', 'Already actioned.');
            reply_ok($is_ajax, 'mechanic/dashboard.php', 'Request rejected.');
        }

        case 'complete': {
            if ($u['role'] !== 'mechanic') reply_err($is_ajax, 'index.php', 'Not authorized.', 403);
            $bid = (int)($_POST['booking_id'] ?? 0);
            $st = $pdo->prepare("UPDATE bookings SET status='completed' WHERE id=:b AND mechanic_id=:m AND status='in_progress'");
            $st->execute([':b'=>$bid, ':m'=>$u['uid']]);
            if (!$st->rowCount()) reply_err($is_ajax, 'mechanic/dashboard.php', 'Could not mark complete.');
            reply_ok($is_ajax, 'mechanic/dashboard.php', 'Marked complete.');
        }

        case 'assign': {
            if ($u['role'] !== 'admin') reply_err($is_ajax, 'index.php', 'Not authorized.', 403);
            $bid = (int)($_POST['booking_id'] ?? 0);
            $mid = (int)($_POST['mechanic_id'] ?? 0);
            $st = $pdo->prepare('UPDATE bookings SET mechanic_id = :m WHERE id = :id');
            $st->execute([':m' => $mid, ':id' => $bid]);

            // Look up the booking number + cause so the push has useful content.
            $info = $pdo->prepare('SELECT booking_number, breakdown_cause, severity, breakdown_location FROM bookings WHERE id = :id');
            $info->execute([':id' => $bid]);
            $b = $info->fetch();
            if ($b) {
                notify_mechanic($mid, [
                    'title' => 'Booking assigned to you — ' . $b['booking_number'],
                    'body'  => $b['breakdown_cause'] . ' (' . $b['severity'] . ', ' . $b['breakdown_location'] . ')',
                    'url'   => url('mechanic/chat.php?booking_id=' . $bid),
                    'tag'   => 'fs-booking-' . $bid,
                ]);
            }

            reply_ok($is_ajax, 'admin/bookings.php', 'Mechanic assigned.');
        }

        case 'update_status': {
            if ($u['role'] !== 'admin') reply_err($is_ajax, 'index.php', 'Not authorized.', 403);
            $bid = (int)($_POST['booking_id'] ?? 0);
            $s = $_POST['status'] ?? '';
            if (!in_array($s, ['new','in_progress','completed','rejected'], true)) reply_err($is_ajax, 'admin/bookings.php', 'Invalid status.');
            $st = $pdo->prepare('UPDATE bookings SET status = :s WHERE id = :id');
            $st->execute([':s' => $s, ':id' => $bid]);
            reply_ok($is_ajax, 'admin/bookings.php', 'Status updated.');
        }

        default:
            reply_err($is_ajax, 'index.php', 'Unknown action.', 400);
    }
} catch (Throwable $ex) {
    error_log('booking-actions: ' . $ex->getMessage());
    reply_err($is_ajax, 'index.php', 'Server error.', 500);
}
