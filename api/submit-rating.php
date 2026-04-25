<?php
require_once __DIR__ . '/../config/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'Invalid request.');
    redirect('user/my-requests.php');
}
verify_csrf($_POST['csrf'] ?? '');
require_role('user');
$u = current_user();
$pdo = db();

$bid = (int)($_POST['booking_id'] ?? 0);
$mid = (int)($_POST['mechanic_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$repair_minutes = isset($_POST['repair_time_minutes']) && $_POST['repair_time_minutes'] !== '' ? (int)$_POST['repair_time_minutes'] : null;

if ($bid <= 0 || $mid <= 0 || $rating < 1 || $rating > 5) {
    flash('error', 'Invalid rating data.');
    redirect('user/my-requests.php');
}

try {
    // Verify booking belongs to user, status completed, mechanic matches.
    $st = $pdo->prepare("SELECT id FROM bookings WHERE id=:id AND user_id=:u AND mechanic_id=:m AND status='completed'");
    $st->execute([':id' => $bid, ':u' => $u['uid'], ':m' => $mid]);
    if (!$st->fetchColumn()) {
        flash('error', 'You can only rate completed bookings.');
        redirect('user/my-requests.php');
    }

    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO ratings (booking_id, user_id, mechanic_id, rating, comment) VALUES (:b,:u,:m,:r,:c)')
        ->execute([':b'=>$bid, ':u'=>$u['uid'], ':m'=>$mid, ':r'=>$rating, ':c'=>$comment]);

    if ($repair_minutes !== null && $repair_minutes >= 0 && $repair_minutes <= 100000) {
        $pdo->prepare('UPDATE bookings SET repair_time_minutes = :rt WHERE id = :id')
            ->execute([':rt' => $repair_minutes, ':id' => $bid]);
    }
    $pdo->commit();
    flash('success', 'Thanks for your feedback.');
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ((int)$ex->getCode() === 23000) {
        flash('error', 'You have already rated this booking.');
    } else {
        error_log('submit-rating: ' . $ex->getMessage());
        flash('error', 'Could not save rating.');
    }
}
redirect('user/my-requests.php');
