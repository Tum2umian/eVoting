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

// Get positions for this session
$positions_stmt = $pdo->prepare("SELECT * FROM Post WHERE VoteSessionID = ?");
$positions_stmt->execute([$session_id]);
$positions = $positions_stmt->fetchAll();

// Get voters count
$voters_stmt = $pdo->prepare("SELECT COUNT(*) FROM Voter");
$voters_stmt->execute();
$voters_count = $voters_stmt->fetchColumn();

// Get candidates count for this session
$candidates_stmt = $pdo->prepare("SELECT COUNT(*) FROM Candidate c JOIN Post p ON c.PositionID = p.PositionID WHERE p.VoteSessionID = ?");
$candidates_stmt->execute([$session_id]);
$candidates_count = $candidates_stmt->fetchColumn();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Session: <?php echo htmlspecialchars($session['Name']); ?></h2>
    <div>
        <a href="add_position.php?session_id=<?php echo $session_id; ?>" class="btn btn-success">Add Position</a>
        <a href="manage_candidates.php?session_id=<?php echo $session_id; ?>" class="btn btn-info">Manage Candidates</a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Voters</h5>
                <p class="card-text display-4"><?php echo $voters_count; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Positions</h5>
                <p class="card-text display-4"><?php echo count($positions); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Candidates</h5>
                <p class="card-text display-4"><?php echo $candidates_count; ?></p>
            </div>
        </div>
    </div>
</div>

<h3>Positions</h3>
<div class="list-group mb-4">
    <?php foreach ($positions as $position): ?>
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <?php echo htmlspecialchars($position['PositionName']); ?>
            <div>
                <a href="#" class="btn btn-sm btn-danger">Delete</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-body">
        <h4 class="card-title">Session Actions</h4>
        <div class="d-grid gap-2">
            <button class="btn btn-warning">Start Voting</button>
            <button class="btn btn-danger">End Voting</button>
            <button class="btn btn-primary">View Results</button>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>