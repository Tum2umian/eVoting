<?php
session_start();
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'voter') {
    header("Location: ../auth/login.php");
    exit();
}

$session_id = $_GET['session_id'] ?? 0;

// Get session details with time validation
$stmt = $pdo->prepare("
    SELECT *, 
    CONCAT(Date, ' ', StartTime) AS StartDateTime, 
    CONCAT(Date, ' ', EndTime) AS EndDateTime
    FROM VoteSession 
    WHERE VoteSessionID = ? AND Status = 'active'
");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    // Check why session isn't available
    $stmt = $pdo->prepare("SELECT * FROM VoteSession WHERE VoteSessionID = ?");
    $stmt->execute([$session_id]);
    $session_info = $stmt->fetch();
    
    if ($session_info) {
        if ($session_info['Status'] === 'pending') {
            $_SESSION['error'] = "This election hasn't started yet.";
        } elseif ($session_info['Status'] === 'ended') {
            $_SESSION['error'] = "This election has ended.";
        } else {
            $now = new DateTime('now', new DateTimeZone($session_info['Timezone']));
            $start = new DateTime($session_info['Date'] . ' ' . $session_info['StartTime'], new DateTimeZone($session_info['Timezone']));
            $end = new DateTime($session_info['Date'] . ' ' . $session_info['EndTime'], new DateTimeZone($session_info['Timezone']));
            
            if ($now < $start) {
                $_SESSION['error'] = "Voting begins at " . $start->format('g:i A') . " on " . $start->format('F j, Y');
            } elseif ($now > $end) {
                $_SESSION['error'] = "Voting ended at " . $end->format('g:i A') . " on " . $end->format('F j, Y');
            }
        }
    } else {
        $_SESSION['error'] = "Invalid election session.";
    }
    header("Location: dashboard.php");
    exit();
}

// Verify voting window is open
$now = new DateTime('now', new DateTimeZone($session['Timezone']));
$start = new DateTime($session['StartDateTime'], new DateTimeZone($session['Timezone']));
$end = new DateTime($session['EndDateTime'], new DateTimeZone($session['Timezone']));

if ($now < $start || $now > $end) {
    header("Location: dashboard.php");
    exit();
}

// Get all positions for this session
$positions_stmt = $pdo->prepare("SELECT * FROM Post WHERE VoteSessionID = ?");
$positions_stmt->execute([$session_id]);
$positions = $positions_stmt->fetchAll();

// Check if voter has already voted
$voted_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM Vote v
    JOIN Post p ON v.PositionID = p.PositionID
    WHERE v.VoterID = ? AND p.VoteSessionID = ?
");
$voted_stmt->execute([$_SESSION['user_id'], $session_id]);
$has_voted = $voted_stmt->fetchColumn() > 0;

if ($has_voted) {
    $_SESSION['error'] = "You have already voted in this election.";
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST['vote'] as $position_id => $candidate_id) {
            if ($candidate_id !== 'abstain') {
                $stmt = $pdo->prepare("INSERT INTO Vote (VoterID, CandidateID, PositionID, VoteSessionID) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $candidate_id, $position_id, $session_id]);
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Your vote has been cast successfully!";
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Failed to cast your vote: " . $e->getMessage();
    }
}

// Calculate time remaining
$time_left = $now->diff($end);
?>

<h2>Ballot: <?php echo htmlspecialchars($session['Name']); ?></h2>
<div class="alert alert-info">
    <i class="bi bi-clock"></i> Time remaining: <?php 
    echo $time_left->format('%h hours %i minutes');
    ?> (Closes at <?php echo $end->format('g:i A'); ?>)
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">
    <?php foreach ($positions as $position): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4><?php echo htmlspecialchars($position['PositionName']); ?></h4>
            </div>
            <div class="card-body">
                <?php
                $candidates_stmt = $pdo->prepare("
                    SELECT c.CandidateID, v.Name, c.Party 
                    FROM Candidate c
                    JOIN Voter v ON c.VoterID = v.VoterID
                    WHERE c.PositionID = ?
                    ORDER BY c.Party, v.Name
                ");
                $candidates_stmt->execute([$position['PositionID']]);
                $candidates = $candidates_stmt->fetchAll();
                ?>
                
                <?php if (count($candidates) > 0): ?>
                    <div class="list-group">
                        <label class="list-group-item">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" 
                                       name="vote[<?php echo $position['PositionID']; ?>]" 
                                       value="abstain" checked>
                                <div class="form-check-label">
                                    <h5>Abstain (No Vote)</h5>
                                </div>
                            </div>
                        </label>
                        <?php foreach ($candidates as $candidate): ?>
                            <label class="list-group-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                           name="vote[<?php echo $position['PositionID']; ?>]" 
                                           value="<?php echo $candidate['CandidateID']; ?>">
                                    <div class="form-check-label">
                                        <h5><?php echo htmlspecialchars($candidate['Name']); ?></h5>
                                        <?php if ($candidate['Party']): ?>
                                            <p class="mb-0"><small>Party: <?php echo htmlspecialchars($candidate['Party']); ?></small></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">No candidates for this position.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (count($positions) > 0): ?>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-check-circle"></i> Submit Ballot
            </button>
            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No positions available for voting in this election.</div>
    <?php endif; ?>
</form>

<script>
// Auto-submit warning when time is almost up
const endTime = new Date("<?php echo $end->format('c'); ?>");
const warningTime = 5 * 60 * 1000; // 5 minutes warning

function checkTime() {
    const now = new Date();
    const timeLeft = endTime - now;
    
    if (timeLeft < warningTime && timeLeft > 0) {
        alert(`Warning: Voting will close in ${Math.ceil(timeLeft/60000)} minutes. Please submit your ballot soon!`);
    }
}

// Check every minute
setInterval(checkTime, 60000);
</script>

<?php require_once '../includes/footer.php'; ?>