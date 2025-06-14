<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

try {
    if (empty($_POST['booking_id']) || empty($_POST['status'])) {
        throw new Exception('Missing required parameters');
    }

    // Verify the booking belongs to this tutor
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND tutor_id = ?");
    $stmt->execute([$_POST['booking_id'], $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Invalid booking');
    }

    // Update the booking status
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ? AND tutor_id = ?");
    $success = $stmt->execute([
        $_POST['status'],
        $_POST['booking_id'],
        $_SESSION['user_id']
    ]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update booking');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}