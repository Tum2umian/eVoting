<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

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

// Get all voters
$voters_stmt = $pdo->prepare("SELECT * FROM Voter ORDER BY Name");
$voters_stmt->execute();
$voters = $voters_stmt->fetchAll();

// Get positions for this session
$positions_stmt = $pdo->prepare("SELECT * FROM Post WHERE VoteSessionID = ?");
$positions_stmt->execute([$session_id]);
$positions = $positions_stmt->fetchAll();

// Get current candidates
$candidates_stmt = $pdo->prepare("
    SELECT c.CandidateID, v.Name AS VoterName, p.PositionName, c.Party 
    FROM Candidate c
    JOIN Voter v ON c.VoterID = v.VoterID
    JOIN Post p ON c.PositionID = p.PositionID
    WHERE p.VoteSessionID = ?
");
$candidates_stmt->execute([$session_id]);
$candidates = $candidates_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voter_id = $_POST['voter_id'];
    $position_id = $_POST['position_id'];
    $party = trim($_POST['party']);
    
    try {
        // Check if this voter is already a candidate for this position
        $check_stmt = $pdo->prepare("SELECT * FROM Candidate WHERE VoterID = ? AND PositionID = ?");
        $check_stmt->execute([$voter_id, $position_id]);
        
        if ($check_stmt->fetch()) {
            $error = "This voter is already a candidate for this position.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO Candidate (VoterID, PositionID, Party) VALUES (?, ?, ?)");
            $stmt->execute([$voter_id, $position_id, $party]);
            
            $_SESSION['success'] = "Candidate added successfully!";
            header("Location: manage_candidates.php?session_id=$session_id");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Failed to add candidate. Please try again.";
    }
}
?>

<div class="row">
    <div class="col-md-8">
        <h2>Manage Candidates for <?php echo htmlspecialchars($session['Name']); ?></h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h4>Add New Candidate</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="voter_id" class="form-label">Select Voter</label>
                        <select class="form-select" id="voter_id" name="voter_id" required>
                            <option value="">-- Select Voter --</option>
                            <?php foreach ($voters as $voter): ?>
                                <option value="<?php echo $voter['VoterID']; ?>">
                                    <?php echo htmlspecialchars($voter['Name'] . ' (' . $voter['Regno'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="position_id" class="form-label">Select Position</label>
                        <select class="form-select" id="position_id" name="position_id" required>
                            <option value="">-- Select Position --</option>
                            <?php foreach ($positions as $position): ?>
                                <option value="<?php echo $position['PositionID']; ?>">
                                    <?php echo htmlspecialchars($position['PositionName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="party" class="form-label">Party/Affiliation (Optional)</label>
                        <input type="text" class="form-control" id="party" name="party">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Candidate</button>
                </form>
            </div>
        </div>
        
        <h3>Current Candidates</h3>
        <div class="list-group">
            <?php foreach ($candidates as $candidate): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5><?php echo htmlspecialchars($candidate['VoterName']); ?></h5>
                            <p class="mb-1">
                                <strong>Position:</strong> <?php echo htmlspecialchars($candidate['PositionName']); ?><br>
                                <?php if ($candidate['Party']): ?>
                                    <strong>Party:</strong> <?php echo htmlspecialchars($candidate['Party']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <a href="#" class="btn btn-sm btn-danger">Remove</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Quick Actions</h4>
                <a href="manage_session.php?id=<?php echo $session_id; ?>" class="btn btn-secondary w-100 mb-2">Back to Session</a>
                <a href="add_position.php?session_id=<?php echo $session_id; ?>" class="btn btn-success w-100 mb-2">Add New Position</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>