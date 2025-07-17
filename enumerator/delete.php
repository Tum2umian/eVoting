<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'enumerator') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['candidate_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$candidate_id = $_POST['candidate_id'];

try {
    $stmt = $pdo->prepare("DELETE FROM Candidate WHERE CandidateID = ?");
    $stmt->execute([$candidate_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Delete candidate failed: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'debug' => $e->getMessage() // remove in production
    ]);
}
?>
