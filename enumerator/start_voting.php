<?php
session_start();
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'enumerator') {
    header("Location: ../auth/login.php");
    exit();
}

$session_id = $_GET['session_id'] ?? 0;

// Verify this enumerator owns this session
$stmt = $pdo->prepare("SELECT * FROM VoteSession WHERE VoteSessionID = ? AND EnumeratorID = ?");
$stmt->execute([$session_id, $_SESSION['user_id']]);
$session = $stmt->fetch();

if (!$session) {
    $_SESSION['error'] = "Invalid vote session or access denied.";
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update the status to 'active' and set the start time to now
        $stmt = $pdo->prepare("UPDATE VoteSession SET Status = 'active', Date = CURDATE() WHERE VoteSessionID = ?");
        $stmt->execute([$session_id]);
        
        $_SESSION['success'] = "Voting has been started successfully!";
        header("Location: manage_session.php?id=$session_id");
        exit();
    } catch (PDOException $e) {
        $error = "Failed to start voting: " . $e->getMessage();
    }
}

// Calculate days until scheduled date
$scheduled_date = new DateTime($session['Date']);
$current_date = new DateTime();
$days_until = $current_date->diff($scheduled_date)->days;
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2>Start Voting Session</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-body">
                <h4><?php echo htmlspecialchars($session['Name']); ?></h4>
                <p>
                    <strong>Originally Scheduled for:</strong> <?php echo date('F j, Y', strtotime($session['Date'])); ?>
                    (<?php echo $days_until > 0 ? "in $days_until days" : "today"; ?>)
                </p>
                
                <?php if ($days_until > 0): ?>
                    <div class="alert alert-warning">
                        <strong>Note:</strong> You're starting this session <?php echo $days_until; ?> days before the scheduled date.
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <strong>Voting will begin immediately</strong> after confirmation and will continue until you manually end the session.
                </div>
            </div>
        </div>
        
        <form method="POST">
            <div class="mb-3">
                <label for="confirm" class="form-label">
                    <input type="checkbox" id="confirm" name="confirm" required>
                    I understand that I'm starting voting before the scheduled date
                </label>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-warning">Confirm Start Voting Now</button>
                <a href="manage_session.php?id=<?php echo $session_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>