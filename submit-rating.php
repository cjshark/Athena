<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// 1. Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['user_id'];
$booking_id = $_POST['booking_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : null;

// 2. Validate Input
if (!$booking_id || !$rating || !is_numeric($rating) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid input. Please provide a booking ID and a rating between 1 and 5.']);
    exit();
}

// Ensure rating is an integer if your DB column is INT
$rating = intval($rating);

try {
    $conn->beginTransaction();

    // 3. Verify Booking: Check if it belongs to the student, is completed, and not already rated
    $stmt_check = $conn->prepare("SELECT tutor_id, rating FROM bookings WHERE id = ? AND student_id = ? AND status = 'completed'");
    $stmt_check->execute([$booking_id, $student_id]);
    $booking = $stmt_check->fetch();

    if (!$booking) {
        throw new Exception('Booking not found, not completed, or does not belong to you.');
    }

    if (!is_null($booking['rating'])) {
         throw new Exception('This session has already been rated.');
    }

    $tutor_id = $booking['tutor_id'];    // 4. Update Booking Rating
    $stmt_update_booking = $conn->prepare("UPDATE bookings SET rating = ?, feedback = ? WHERE id = ?");
    if (!$stmt_update_booking->execute([$rating, $feedback, $booking_id])) {
        throw new Exception('Failed to save rating for the session.');
    }

    // 5. Recalculate and Update Tutor's Average Rating
    // Fetch all non-null ratings for this tutor from the bookings table
    $stmt_avg = $conn->prepare("SELECT AVG(rating) as average_rating FROM bookings WHERE tutor_id = ? AND rating IS NOT NULL");
    $stmt_avg->execute([$tutor_id]);
    $avg_result = $stmt_avg->fetch();

    $new_average_rating = $avg_result ? round($avg_result['average_rating'], 1) : null; // Round to 1 decimal place

    // Update the tutor's rating in the tutors table
    $stmt_update_tutor = $conn->prepare("UPDATE tutors SET rating = ? WHERE id = ?");
    if (!$stmt_update_tutor->execute([$new_average_rating, $tutor_id])) {
        // Log error, but maybe don't fail the whole transaction? Or do? Depends on requirements.
        // For now, let's roll back if tutor update fails.
        throw new Exception('Failed to update tutor average rating.');
    }

    // 6. Commit Transaction
    $conn->commit();

    echo json_encode(['success' => true, 'rating' => $rating, 'average_rating' => $new_average_rating]);

} catch (PDOException $e) {
    $conn->rollBack();
    // Log error: error_log("Database error in submit-rating.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred. ' . $e->getMessage()]); // Consider generic message for users
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>