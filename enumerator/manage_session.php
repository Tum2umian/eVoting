<?php
session_start();
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'enumerator') {
    header("Location: ../auth/login.php");
    exit();
}

$session_id = $_GET['id'] ?? 0;

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
$start = new DateTime($session['StartDateTime'], new DateTimeZone($session['Timezone']));
$end = new DateTime($session['EndDateTime'], new DateTimeZone($session['Timezone']));

// Handle session deletion
if (isset($_POST['delete_session'])) {
    try {
        $pdo->beginTransaction();
        
        // Delete all related data (votes, candidates, positions)
        $pdo->prepare("DELETE FROM Vote WHERE VoteSessionID = ?")->execute([$session_id]);
        $pdo->prepare("DELETE FROM Candidate WHERE PositionID IN (SELECT PositionID FROM Post WHERE VoteSessionID = ?)")->execute([$session_id]);
        $pdo->prepare("DELETE FROM Post WHERE VoteSessionID = ?")->execute([$session_id]);
        $pdo->prepare("DELETE FROM VoteSession WHERE VoteSessionID = ?")->execute([$session_id]);
        
        $pdo->commit();
        $_SESSION['success'] = "Vote session deleted successfully!";
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Failed to delete session: " . $e->getMessage();
    }
}

// Get positions for this session
$positions_stmt = $pdo->prepare("SELECT * FROM Post WHERE VoteSessionID = ?");
$positions_stmt->execute([$session_id]);
$positions = $positions_stmt->fetchAll();

// Get voters count
$voters_stmt = $pdo->prepare("SELECT COUNT(*) FROM Voter");
$voters_stmt->execute();
$voters_count = $voters_stmt->fetchColumn();

// Get candidates count for this session
$candidates_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM Candidate c 
    JOIN Post p ON c.PositionID = p.PositionID 
    WHERE p.VoteSessionID = ?
");
$candidates_stmt->execute([$session_id]);
$candidates_count = $candidates_stmt->fetchColumn();

// Get votes count for this session
$votes_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM Vote 
    WHERE VoteSessionID = ?
");
$votes_stmt->execute([$session_id]);
$votes_count = $votes_stmt->fetchColumn();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Manage Session: <?php echo htmlspecialchars($session['Name']); ?></h2>
        <p class="mb-0 text-muted">
            <i class="bi bi-calendar"></i> <?php echo $start->format('F j, Y'); ?> | 
            <i class="bi bi-clock"></i> <?php echo $start->format('g:i A') . ' - ' . $end->format('g:i A'); ?> | 
            <i class="bi bi-globe"></i> <?php echo $session['Timezone']; ?>
        </p>
    </div>
    <div>
        <a href="add_position.php?session_id=<?php echo $session_id; ?>" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Add Position
        </a>
        <a href="manage_candidates.php?session_id=<?php echo $session_id; ?>" class="btn btn-info">
            <i class="bi bi-people"></i> Manage Candidates
        </a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Voters</h5>
                <p class="card-text display-4"><?php echo $voters_count; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Positions</h5>
                <p class="card-text display-4"><?php echo count($positions); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Candidates</h5>
                <p class="card-text display-4"><?php echo $candidates_count; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-secondary">
            <div class="card-body">
                <h5 class="card-title">Votes Cast</h5>
                <p class="card-text display-4"><?php echo $votes_count; ?></p>
            </div>
        </div>
    </div>
</div>

<h3>Positions</h3>
<?php if (count($positions) > 0): ?>
    <div class="list-group mb-4">
        <?php foreach ($positions as $position): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($position['PositionName']); ?></h5>
                    <?php 
                    // Get candidates count for this position
                    $pos_candidates = $pdo->prepare("SELECT COUNT(*) FROM Candidate WHERE PositionID = ?");
                    $pos_candidates->execute([$position['PositionID']]);
                    $pos_count = $pos_candidates->fetchColumn();
                    ?>
                    <small class="text-muted"><?php echo $pos_count; ?> candidates</small>
                </div>
                <div>
                    <a href="manage_candidates.php?session_id=<?php echo $session_id; ?>&position_id=<?php echo $position['PositionID']; ?>" 
                       class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-people"></i> Candidates
                    </a>
                    <button class="btn btn-sm btn-outline-danger delete-position" 
                            data-id="<?php echo $position['PositionID']; ?>">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">No positions added yet. Add positions to enable voting.</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">Session Actions</h4>
                <div class="d-grid gap-2">
                    <?php if ($session['Status'] === 'pending'): ?>
                        <a href="start_voting.php?session_id=<?php echo $session_id; ?>" 
                           class="btn btn-warning">
                            <i class="bi bi-play-circle"></i> Start Voting
                        </a>
                    <?php elseif ($session['Status'] === 'active'): ?>
                        <a href="end_voting.php?session_id=<?php echo $session_id; ?>" 
                           class="btn btn-danger">
                            <i class="bi bi-stop-circle"></i> End Voting
                        </a>
                        <div class="alert alert-info mt-2">
                            <i class="bi bi-info-circle"></i> 
                            <?php 
                            $timeLeft = $now->diff($end);
                            echo "Voting ends in " . $timeLeft->format('%h hours %i minutes');
                            ?>
                        </div>
                    <?php else: ?>
                        <a href="view_results.php?session_id=<?php echo $session_id; ?>" 
                           class="btn btn-primary">
                            <i class="bi bi-bar-chart"></i> View Results
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Danger Zone</h4>
                <div class="alert alert-danger">
                    <h5><i class="bi bi-exclamation-triangle"></i> Delete This Session</h5>
                    <p>This will permanently delete the session and all associated data.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this session? This cannot be undone!')">
                        <button type="submit" name="delete_session" class="btn btn-danger w-100">
                            <i class="bi bi-trash"></i> Delete Session
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Position Modal -->
<div class="modal fade" id="deletePositionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Position</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this position? This will also remove all candidates for this position.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deletePositionForm" method="POST">
                    <input type="hidden" name="position_id" id="deletePositionId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Position deletion handling
document.querySelectorAll('.delete-position').forEach(button => {
    button.addEventListener('click', function() {
        const positionId = this.getAttribute('data-id');
        document.getElementById('deletePositionId').value = positionId;
        const modal = new bootstrap.Modal(document.getElementById('deletePositionModal'));
        modal.show();
    });
});

// Handle position deletion form submission
document.getElementById('deletePositionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('delete_position.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the position');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>