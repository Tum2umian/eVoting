<?php
session_start();
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'enumerator') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $date = $_POST['date'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO VoteSession (Name, Date, EnumeratorID) VALUES (?, ?, ?)");
        $stmt->execute([$name, $date, $_SESSION['user_id']]);
        
        $_SESSION['success'] = "Vote session created successfully!";
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        $error = "Failed to create vote session. Please try again.";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2>Create New Vote Session</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Session Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Voting Date</label>
                <input type="date" class="form-control" id="date" name="date" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Session</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>