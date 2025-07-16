<?php
session_start();
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'enumerator') {
    header("Location: ../auth/login.php");
    exit();
}

// Get all vote sessions with status and time info
$stmt = $pdo->prepare("
    SELECT vs.*,
           CONCAT(vs.Date, ' ', vs.StartTime) AS StartDateTime,
           CONCAT(vs.Date, ' ', vs.EndTime) AS EndDateTime
    FROM VoteSession vs
    WHERE vs.EnumeratorID = ?
    ORDER BY 
        CASE 
            WHEN vs.Status = 'active' THEN 1
            WHEN vs.Status = 'pending' THEN 2
            ELSE 3
        END,
        vs.Date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$sessions = $stmt->fetchAll();

// Get current datetime in UTC
$now = new DateTime('now', new DateTimeZone('UTC'));
?>

<h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
<p class="lead">Manage your voting sessions</p>

<div class="mb-4">
    <a href="create_session.php" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Create New Session
    </a>
</div>

<div class="row">
    <?php foreach ($sessions as $session): 
        $start = new DateTime($session['StartDateTime'], new DateTimeZone($session['Timezone']));
        $end = new DateTime($session['EndDateTime'], new DateTimeZone($session['Timezone']));
    ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title"><?php echo htmlspecialchars($session['Name']); ?></h5>
                        <span class="badge bg-<?php 
                            echo $session['Status'] === 'active' ? 'success' : 
                                 ($session['Status'] === 'ended' ? 'secondary' : 'warning'); 
                        ?>">
                            <?php echo ucfirst($session['Status']); ?>
                        </span>
                    </div>
                    
                    <p class="card-text">
                        <i class="bi bi-calendar"></i> <?php echo $start->format('F j, Y'); ?><br>
                        <i class="bi bi-clock"></i> <?php echo $start->format('g:i A') . ' - ' . $end->format('g:i A'); ?><br>
                        <i class="bi bi-globe"></i> <?php echo $session['Timezone']; ?>
                    </p>
                    
                    <?php if ($session['Status'] === 'active'): ?>
                        <div class="alert alert-success py-1 mb-2">
                            <small>
                                <i class="bi bi-info-circle"></i> 
                                <?php 
                                $timeLeft = $now->diff($end);
                                echo $timeLeft->format('Voting ends in %h hours %i minutes');
                                ?>
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between mt-3">
                        <a href="manage_session.php?id=<?php echo $session['VoteSessionID']; ?>" 
                           class="btn btn-sm btn-primary">
                            <i class="bi bi-gear"></i> Manage
                        </a>
                        
                        <a href="#" class="btn btn-sm btn-info copy-link" 
                           data-id="<?php echo $session['VoteSessionID']; ?>">
                            <i class="bi bi-link-45deg"></i> Copy Link
                        </a>
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
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.innerHTML = `
                <div class="toast show" role="alert">
                    <div class="toast-body">
                        Registration link copied to clipboard!
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>