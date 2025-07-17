<?php
session_start();
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'enumerator') {
    header("Location: ../auth/login.php");
    exit();
}

$session_id = $_GET['session_id'] ?? 0;

// Verify this enumerator owns this session with time info
$stmt = $pdo->prepare("
    SELECT vs.*,
           CONCAT(vs.Date, ' ', vs.StartTime) AS StartDateTime,
           CONCAT(vs.Date, ' ', vs.EndTime) AS EndDateTime
    FROM VoteSession vs
    WHERE vs.VoteSessionID = ? AND vs.EnumeratorID = ?
");
$stmt->execute([$session_id, $_SESSION['user_id']]);
$session = $stmt->fetch();

if (!$session) {
    $_SESSION['error'] = "Invalid vote session or access denied.";
    header("Location: dashboard.php");
    exit();
}

// Get current datetime in session's timezone
$now = new DateTime('now', new DateTimeZone($session['Timezone']));
$end = new DateTime($session['EndDateTime'], new DateTimeZone($session['Timezone']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update the status to 'ended' and set actual end time
        $stmt = $pdo->prepare("
            UPDATE VoteSession 
            SET Status = 'ended', 
                ActualEndDate = NOW() 
            WHERE VoteSessionID = ?
        ");
        $stmt->execute([$session_id]);
        
        // Log this action
        $log_stmt = $pdo->prepare("
            INSERT INTO AdminLogs 
            (EnumeratorID, Action, Timestamp, SessionID) 
            VALUES (?, ?, NOW(), ?)
        ");
        $log_stmt->execute([
            $_SESSION['user_id'],
            "Ended voting session '{$session['Name']}'",
            $session_id
        ]);
        
        $_SESSION['success'] = "Voting has been ended successfully! Results are now available.";
        header("Location: manage_session.php?id=$session_id");
        exit();
    } catch (PDOException $e) {
        $error = "Failed to end voting: " . $e->getMessage();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h2>End Voting Session</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-body">
                <h4><?php echo htmlspecialchars($session['Name']); ?></h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <p>
                            <strong>Scheduled Date:</strong><br>
                            <?php echo date('F j, Y', strtotime($session['Date'])); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p>
                            <strong>Voting Period:</strong><br>
                            <?php 
                            $start = new DateTime($session['StartDateTime'], new DateTimeZone($session['Timezone']));
                            $end = new DateTime($session['EndDateTime'], new DateTimeZone($session['Timezone']));
                            echo $start->format('g:i A') . ' - ' . $end->format('g:i A');
                            ?>
                        </p>
                    </div>
                </div>
                
                <div class="alert alert-danger">
                    <h5><i class="bi bi-exclamation-triangle"></i> Important Notice</h5>
                    <ul>
                        <li>This action cannot be undone</li>
                        <li>All voting will be stopped immediately</li>
                        <li>Results will be finalized and made public</li>
                        <li>Current time: <?php echo $now->format('F j, Y g:i A'); ?></li>
                    </ul>
                </div>
                
                <?php if ($now < $end): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i> 
                        You're ending this session <?php 
                        $timeEarly = $now->diff($end);
                        echo $timeEarly->format('%h hours and %i minutes');
                        ?> before its scheduled end time.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <form method="POST">
            <div class="mb-3">
                <label for="confirm" class="form-label">
                    <input type="checkbox" id="confirm" name="confirm" required class="form-check-input">
                    I understand this action is permanent and will stop all voting immediately
                </label>
            </div>
            
            <div class="d-grid gap-2 d-md-flex">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-stop-circle"></i> Confirm End Voting
                </button>
                <a href="manage_session.php?id=<?php echo $session_id; ?>" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>