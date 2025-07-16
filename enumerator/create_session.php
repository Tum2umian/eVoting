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
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $timezone = $_POST['timezone'];
    
    // Validate time inputs
    if (strtotime($end_time) <= strtotime($start_time)) {
        $error = "End time must be after start time";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO VoteSession 
                (Name, Date, StartTime, EndTime, Timezone, EnumeratorID, Status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $name, 
                $date, 
                $start_time, 
                $end_time, 
                $timezone, 
                $_SESSION['user_id']
            ]);
            
            $_SESSION['success'] = "Vote session created successfully!";
            header("Location: dashboard.php");
            exit();
        } catch (PDOException $e) {
            $error = "Failed to create vote session: " . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h2>Create New Vote Session</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Session Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="date" class="form-label">Voting Date</label>
                    <input type="date" class="form-control" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="timezone" class="form-label">Timezone</label>
                    <select class="form-select" id="timezone" name="timezone" required>
                        <option value="UTC">UTC</option>
                        <option value="Africa/Kampala" selected>East Africa Time (EAT)</option>
                        <option value="America/New_York">Eastern Time (ET)</option>
                        <option value="Europe/London">British Summer Time (BST)</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="start_time" class="form-label">Start Time</label>
                    <input type="time" class="form-control" id="start_time" name="start_time" value="08:00" required>
                </div>
                <div class="col-md-6">
                    <label for="end_time" class="form-label">End Time</label>
                    <input type="time" class="form-control" id="end_time" name="end_time" value="17:00" required>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex">
                <button type="submit" class="btn btn-primary">Create Session</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
// Set minimum time for end time based on start time
document.getElementById('start_time').addEventListener('change', function() {
    const endTime = document.getElementById('end_time');
    if (this.value >= endTime.value) {
        // Add 1 hour to start time as default end time
        const [hours, minutes] = this.value.split(':');
        const newHours = parseInt(hours) + 1;
        endTime.value = `${newHours.toString().padStart(2, '0')}:${minutes}`;
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>