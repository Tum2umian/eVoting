<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'enumerator') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position_id = $_POST['position_id'] ?? 0;
    
    try {
        $pdo->beginTransaction();
        
        // First delete votes for candidates in this position
        $pdo->prepare("
            DELETE v FROM Vote v
            JOIN Candidate c ON v.CandidateID = c.CandidateID
            WHERE c.PositionID = ?
        ")->execute([$position_id]);
        
        // Then delete the candidates
        $pdo->prepare("DELETE FROM Candidate WHERE PositionID = ?")->execute([$position_id]);
        
        // Finally delete the position
        $pdo->prepare("DELETE FROM Post WHERE PositionID = ?")->execute([$position_id]);
        
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Deletion error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred',
            'debug' => $e->getMessage() // Remove this in production
        ]);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>