<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as changes
        FROM bookings 
        WHERE student_id = ? 
        AND status IN ('accepted', 'declined')
        AND updated_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    echo json_encode([
        'updated' => $result['changes'] > 0
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}