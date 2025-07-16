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

// Handle candidate removal
if (isset($_GET['delete_candidate'])) {
    $candidate_id = $_GET['delete_candidate'];
    
    try {
        $pdo->beginTransaction();
        
        // Verify the candidate belongs to this session
        $stmt = $pdo->prepare("
            SELECT c.CandidateID 
            FROM Candidate c
            JOIN Post p ON c.PositionID = p.PositionID
            WHERE c.CandidateID = ? AND p.VoteSessionID = ?
        ");
        $stmt->execute([$candidate_id, $session_id]);
        
        if (!$stmt->fetch()) {
            throw new Exception('Invalid candidate or access denied');
        }
        
        // First delete any votes for this candidate
        $pdo->prepare("DELETE FROM Vote WHERE CandidateID = ?")->execute([$candidate_id]);
        
        // Then delete the candidate
        $pdo->prepare("DELETE FROM Candidate WHERE CandidateID = ?")->execute([$candidate_id]);
        
        $pdo->commit();
        $_SESSION['success'] = "Candidate removed successfully!";
        header("Location: manage_candidates.php?session_id=$session_id");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to remove candidate: " . $e->getMessage();
        header("Location: manage_candidates.php?session_id=$session_id");
        exit();
    }
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
    SELECT c.CandidateID, v.VoterID, v.Name AS VoterName, p.PositionName, c.Party 
    FROM Candidate c
    JOIN Voter v ON c.VoterID = v.VoterID
    JOIN Post p ON c.PositionID = p.PositionID
    WHERE p.VoteSessionID = ?
    ORDER BY p.PositionName, v.Name
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

// Display success/error messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<div class="row">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Candidates for <?php echo htmlspecialchars($session['Name']); ?></h2>
            <a href="manage_session.php?id=<?php echo $session_id; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Session
            </a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-person-plus"></i> Add New Candidate</h4>
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
                        <input type="text" class="form-control" id="party" name="party" placeholder="Enter party name if applicable">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Add Candidate
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-people-fill"></i> Current Candidates</h4>
            </div>
            <div class="card-body p-0">
                <?php if (count($candidates) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php 
                        $current_position = '';
                        foreach ($candidates as $candidate): 
                            if ($current_position !== $candidate['PositionName']):
                                $current_position = $candidate['PositionName'];
                        ?>
                            <div class="list-group-item bg-light">
                                <h5 class="mb-0"><?php echo htmlspecialchars($current_position); ?></h5>
                            </div>
                        <?php endif; ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($candidate['VoterName']); ?></h6>
                                        <?php if ($candidate['Party']): ?>
                                            <small class="text-muted">Party: <?php echo htmlspecialchars($candidate['Party']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="manage_candidates.php?session_id=<?php echo $session_id; ?>&delete_candidate=<?php echo $candidate['CandidateID']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to remove <?php echo addslashes($candidate['VoterName']); ?> as a candidate for <?php echo addslashes($candidate['PositionName']); ?>? This will also delete any votes they received.');">
                                            <i class="bi bi-trash"></i> Remove
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-people" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">No candidates have been added yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-lightning-charge"></i> Quick Actions</h4>
            </div>
            <div class="card-body">
                <a href="add_position.php?session_id=<?php echo $session_id; ?>" class="btn btn-success w-100 mb-2">
                    <i class="bi bi-plus-circle"></i> Add New Position
                </a>
                <a href="manage_session.php?id=<?php echo $session_id; ?>" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-gear"></i> Session Settings
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-info-circle"></i> Session Info</h4>
            </div>
            <div class="card-body">
                <h6><?php echo htmlspecialchars($session['Name']); ?></h6>
                <p class="mb-1"><small>Status: 
                    <span class="badge bg-<?php 
                        echo $session['Status'] === 'active' ? 'success' : 
                             ($session['Status'] === 'ended' ? 'secondary' : 'warning'); 
                    ?>">
                        <?php echo ucfirst($session['Status']); ?>
                    </span>
                </small></p>
                <?php if (!empty($session['StartTime']) && !empty($session['EndTime'])): ?>
                    <p class="mb-1"><small>Voting Time: <?php echo date('g:i A', strtotime($session['StartTime'])); ?> - <?php echo date('g:i A', strtotime($session['EndTime'])); ?></small></p>
                <?php endif; ?>
                <p class="mb-0"><small>Total Candidates: <?php echo count($candidates); ?></small></p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>