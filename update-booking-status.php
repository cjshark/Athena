<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$tutor_id = $_SESSION['user_id'];
$booking_id = $_POST['booking_id'] ?? null;
$action = $_POST['action'] ?? null; 

if (!$booking_id || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters.']);
    exit();
}

$allowed_actions = ['accept', 'decline', 'complete']; 
if (!in_array($action, $allowed_actions)) {
    echo json_encode(['success' => false, 'error' => 'Invalid action.']);
    exit();
}

$new_status = '';
$success_message = '';
$current_status_check = null;

switch ($action) {
    case 'accept':
        $new_status = 'accepted';
        $current_status_check = 'pending';
        $success_message = 'Booking accepted successfully.';
        break;
    case 'decline':
        $new_status = 'declined';
        $success_message = 'Booking declined successfully.';
        break;
    case 'complete':
        $new_status = 'completed';
        $success_message = 'Session marked as completed.';
        break;
}

if (empty($new_status)) {
    echo json_encode(['success' => false, 'error' => 'Invalid action.']);
    exit();
}

try {
    $stmt_check = $conn->prepare("SELECT status FROM bookings WHERE id = ? AND tutor_id = ?");
    $stmt_check->execute([$booking_id, $tutor_id]);
    $booking = $stmt_check->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'error' => 'Booking not found or access denied.']);
        exit();
    }

    if ($current_status_check !== null) {
        if (is_array($current_status_check)) {
            if (!in_array($booking['status'], $current_status_check)) {
                echo json_encode(['success' => false, 'error' => 'Action "' . $action . '" not allowed for current booking status ("' . $booking['status'] . '"). Expected: ' . implode(' or ', $current_status_check)]);
                exit();
            }
        } else {
            if ($booking['status'] !== $current_status_check) {
                echo json_encode(['success' => false, 'error' => 'Action "' . $action . '" not allowed for current booking status ("' . $booking['status'] . '"). Expected: ' . $current_status_check]);
                exit();
            }
        }
    }
    
    if ($action === 'complete' && !in_array($booking['status'], ['confirmed', 'accepted'])) {
         echo json_encode(['success' => false, 'error' => 'Only confirmed or accepted sessions can be marked as completed. Current status: ' . $booking['status']]);
         exit();
    }

    $stmt_update = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ? AND tutor_id = ?");

    $stmt_update->execute([$new_status, $booking_id, $tutor_id]);

    if ($stmt_update->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => $success_message, 'new_status' => $new_status]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Could not update booking status. It might already be in the desired state, the current status was unexpected, or an error occurred.']);
    }

} catch (PDOException $e) {
    error_log("Update Booking Status Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred. Please check server logs.']);
}
?>