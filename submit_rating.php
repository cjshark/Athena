<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

if (!isset($_POST['booking_id']) || !isset($_POST['rating'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data.']);
    exit();
}

$student_id = $_SESSION['user_id'];
$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
$feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : null;
$feedback_to_save = ($feedback === '') ? null : $feedback;


if ($booking_id === false || $rating === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    exit();
}

try {
    $checkSql = "SELECT id FROM bookings
                 WHERE id = ?
                   AND student_id = ?
                   AND (status = 'completed' OR (status = 'accepted' AND session_date <= CURDATE()))
                   AND rating IS NULL";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$booking_id, $student_id]);

    if ($checkStmt->rowCount() > 0) {
        $updateSql = "UPDATE bookings SET rating = ?, feedback = ?, status = 'completed' WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);

        if ($updateStmt->execute([$rating, $feedback_to_save, $booking_id])) {
            echo json_encode(['success' => true, 'message' => 'Rating and feedback submitted successfully.']);
        } else {
            error_log("Failed to update booking ID: " . $booking_id . " for student ID: " . $student_id);
            echo json_encode(['success' => false, 'message' => 'Failed to save rating and feedback.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Cannot rate this session or it has already been rated.']);
    }

} catch (PDOException $e) {
    error_log("Database error in submit_rating.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}

$conn = null;
?>