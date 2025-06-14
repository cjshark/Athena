<?php
require 'db.php';
header('Content-Type: application/json');

$tutorIds = isset($_POST['tutor_ids']) ? $_POST['tutor_ids'] : [];
if (!is_array($tutorIds) || empty($tutorIds)) {
    echo json_encode(['success' => false, 'error' => 'No tutor IDs provided.']);
    exit();
}

$placeholders = implode(',', array_fill(0, count($tutorIds), '?'));

$sql = "SELECT tutor_id, AVG(rating) AS avg_rating, COUNT(rating) AS review_count FROM bookings WHERE rating IS NOT NULL AND tutor_id IN ($placeholders) GROUP BY tutor_id";
$stmt = $conn->prepare($sql);
$stmt->execute($tutorIds);
$ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql2 = "SELECT tutor_id, feedback, rating FROM bookings WHERE tutor_id IN ($placeholders) AND feedback IS NOT NULL AND feedback != '' ORDER BY id DESC";
$stmt2 = $conn->prepare($sql2);
$stmt2->execute($tutorIds);
$feedbacks = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$tutorFeedbacks = [];
foreach ($feedbacks as $fb) {
    $tid = $fb['tutor_id'];
    if (!isset($tutorFeedbacks[$tid])) $tutorFeedbacks[$tid] = [];
    if (count($tutorFeedbacks[$tid]) < 2) {
        $tutorFeedbacks[$tid][] = [
            'feedback' => $fb['feedback'],
            'rating' => $fb['rating']
        ];
    }
}

$ratingsByTutor = [];
foreach ($ratings as $r) {
    $ratingsByTutor[$r['tutor_id']] = [
        'avg_rating' => round($r['avg_rating'], 1),
        'review_count' => $r['review_count']
    ];
}

$response = [];
foreach ($tutorIds as $tid) {
    $response[$tid] = [
        'avg_rating' => isset($ratingsByTutor[$tid]) ? $ratingsByTutor[$tid]['avg_rating'] : null,
        'review_count' => isset($ratingsByTutor[$tid]) ? $ratingsByTutor[$tid]['review_count'] : 0,
        'feedbacks' => isset($tutorFeedbacks[$tid]) ? $tutorFeedbacks[$tid] : []
    ];
}

echo json_encode(['success' => true, 'data' => $response]);
