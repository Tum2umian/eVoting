<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<?php
session_start();
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'enumerator') {
    header("Location: ../auth/login.php");
    exit();
}

// Get all vote sessions created by this enumerator
$stmt = $pdo->prepare("SELECT * FROM VoteSession WHERE EnumeratorID = ? ORDER BY Date DESC");
$stmt->execute([$_SESSION['user_id']]);
$sessions = $stmt->fetchAll();
?>

<h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
<p class="lead">Manage your voting sessions below</p>

<div class="mb-4">
    <a href="create_session.php" class="btn btn-success">Create New Vote Session</a>
</div>

<div class="row">
    <?php foreach ($sessions as $session): ?>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($session['Name']); ?></h5>
                    <p class="card-text">
                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($session['Date'])); ?><br>
                        <strong>Session ID:</strong> <?php echo $session['VoteSessionID']; ?>
                    </p>
                    <div class="d-flex justify-content-between">
                        <a href="manage_session.php?id=<?php echo $session['VoteSessionID']; ?>" class="btn btn-primary btn-sm">Manage</a>
                        <a href="#" class="btn btn-info btn-sm copy-link" data-id="<?php echo $session['VoteSessionID']; ?>">Copy Registration Link</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.copy-link').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const sessionId = this.getAttribute('data-id');
        const link = `${window.location.origin}/votems/auth/register_voter.php?session=${sessionId}`;
        
        navigator.clipboard.writeText(link).then(() => {
            alert('Registration link copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>