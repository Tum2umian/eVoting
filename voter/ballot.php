<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'voter') {
    header("Location: ../auth/login.php");
    exit();
}

$session_id = $_GET['session_id'] ?? 0;

// Check if this session is active (today)
$current_date = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM VoteSession WHERE VoteSessionID = ? AND Date = ?");
$stmt->execute([$session_id, $current_date]);
$session = $stmt->fetch();

if (!$session) {
    $_SESSION['error'] = "This election is not active or doesn't exist.";
    header("Location: dashboard.php");
    exit();
}

// Get all positions for this session
$positions_stmt = $pdo->prepare("SELECT * FROM Post WHERE VoteSessionID = ?");
$positions_stmt->execute([$session_id]);
$positions = $positions_stmt->fetchAll();

// Check if voter has already voted in this session
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
            $stmt = $pdo->prepare("INSERT INTO Vote (VoterID, CandidateID, PositionID, VoteSessionID) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $candidate_id, $position_id, $session_id]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Your vote has been cast successfully!";
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Failed to cast your vote. Please try again.";
    }
}
?>

<h2>Ballot: <?php echo htmlspecialchars($session['Name']); ?></h2>
<p class="lead">Please select your preferred candidate for each position</p>

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
                // Get candidates for this position
                $candidates_stmt = $pdo->prepare("
                    SELECT c.CandidateID, v.Name, c.Party 
                    FROM Candidate c
                    JOIN Voter v ON c.VoterID = v.VoterID
                    WHERE c.PositionID = ?
                ");
                $candidates_stmt->execute([$position['PositionID']]);
                $candidates = $candidates_stmt->fetchAll();
                ?>
                
                <?php if (count($candidates) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($candidates as $candidate): ?>
                            <label class="list-group-item">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                           name="vote[<?php echo $position['PositionID']; ?>]" 
                                           value="<?php echo $candidate['CandidateID']; ?>" required>
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
        <div class="d-grid">
            <button type="submit" class="btn btn-success btn-lg">Submit Ballot</button>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No positions available for voting in this election.</div>
    <?php endif; ?>
</form>

<?php require_once '../../includes/footer.php'; ?>