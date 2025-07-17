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
    $position_name = trim($_POST['position_name']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO Post (PositionName, VoteSessionID) VALUES (?, ?)");
        $stmt->execute([$position_name, $session_id]);
        
        $_SESSION['success'] = "Position added successfully!";
        header("Location: manage_session.php?id=$session_id");
        exit();
    } catch (PDOException $e) {
        $error = "Failed to add position. Please try again.";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2>Add Position to <?php echo htmlspecialchars($session['Name']); ?></h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="position_name" class="form-label">Position Name</label>
                <input type="text" class="form-control" id="position_name" name="position_name" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Position</button>
            <a href="manage_session.php?id=<?php echo $session_id; ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>