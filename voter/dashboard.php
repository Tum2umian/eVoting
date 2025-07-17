<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<?php
session_start();
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'voter') {
    header("Location: ../auth/login.php");
    exit();
}

// Get current datetime in UTC
$now = new DateTime('now', new DateTimeZone('UTC'));

// Get active vote sessions (status = 'active' and within time window)
$stmt = $pdo->prepare("
    SELECT vs.* 
    FROM VoteSession vs
    WHERE vs.Status = 'active' 
    AND vs.Date <= CURDATE()
    AND CONCAT(vs.Date, ' ', vs.EndTime) >= NOW()
    ORDER BY vs.Date DESC, vs.StartTime ASC
");
$stmt->execute();
$active_sessions = $stmt->fetchAll();

// Get upcoming vote sessions (status = 'pending' or future date)
$stmt = $pdo->prepare("
    SELECT vs.* 
    FROM VoteSession vs
    WHERE vs.Status = 'pending' OR vs.Date > CURDATE()
    ORDER BY vs.Date ASC, vs.StartTime ASC
");
$stmt->execute();
$upcoming_sessions = $stmt->fetchAll();

// Get past vote sessions (status = 'ended' or past end time)
$stmt = $pdo->prepare("
    SELECT vs.* 
    FROM VoteSession vs
    WHERE vs.Status = 'ended' OR CONCAT(vs.Date, ' ', vs.EndTime) < NOW()
    ORDER BY vs.Date DESC, vs.EndTime DESC
");
$stmt->execute();
$past_sessions = $stmt->fetchAll();
?>

<h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
<p class="lead">Your voting dashboard</p>

<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Active Elections</h5>
                <p class="card-text display-4"><?php echo count($active_sessions); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <h5 class="card-title">Upcoming Elections</h5>
                <p class="card-text display-4"><?php echo count($upcoming_sessions); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-secondary mb-3">
            <div class="card-body">
                <h5 class="card-title">Past Elections</h5>
                <p class="card-text display-4"><?php echo count($past_sessions); ?></p>
            </div>
        </div>
    </div>
</div>

<h3>Active Elections</h3>
<?php if (count($active_sessions) > 0): ?>
    <div class="list-group mb-4">
        <?php foreach ($active_sessions as $session): 
            $start = new DateTime($session['Date'] . ' ' . $session['StartTime'], new DateTimeZone($session['Timezone']));
            $end = new DateTime($session['Date'] . ' ' . $session['EndTime'], new DateTimeZone($session['Timezone']));
            $time_left = $now->diff($end);
        ?>
            <a href="ballot.php?session_id=<?php echo $session['VoteSessionID']; ?>" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5><?php echo htmlspecialchars($session['Name']); ?></h5>
                        <p class="mb-0">
                            <?php echo $start->format('g:i A') . ' - ' . $end->format('g:i A'); ?>
                            <span class="badge bg-light text-dark ms-2">
                                <?php echo $time_left->format('%h hr %i min left'); ?>
                            </span>
                        </p>
                    </div>
                    <span class="badge bg-success">Vote Now</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">No active elections at this time.</div>
<?php endif; ?>

<h3>Upcoming Elections</h3>
<?php if (count($upcoming_sessions) > 0): ?>
    <div class="list-group mb-4">
        <?php foreach ($upcoming_sessions as $session): 
            $start = new DateTime($session['Date'] . ' ' . $session['StartTime'], new DateTimeZone($session['Timezone']));
            $time_until = $now->diff($start);
        ?>
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5><?php echo htmlspecialchars($session['Name']); ?></h5>
                        <p class="mb-0">
                            <?php echo $start->format('F j, Y g:i A'); ?>
                            <span class="badge bg-light text-dark ms-2">
                                Starts in <?php echo $time_until->format('%a days %h hours'); ?>
                            </span>
                        </p>
                    </div>
                    <span class="badge bg-info">Coming Soon</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">No upcoming elections scheduled.</div>
<?php endif; ?>

<h3>Past Elections</h3>
<?php if (count($past_sessions) > 0): ?>
    <div class="list-group">
        <?php foreach ($past_sessions as $session): 
            $end = new DateTime($session['Date'] . ' ' . $session['EndTime'], new DateTimeZone($session['Timezone']));
        ?>
            <a href="results.php?session_id=<?php echo $session['VoteSessionID']; ?>" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5><?php echo htmlspecialchars($session['Name']); ?></h5>
                        <p class="mb-0">
                            <?php echo $end->format('F j, Y g:i A'); ?>
                            <span class="badge bg-light text-dark">Closed</span>
                        </p>
                    </div>
                    <span class="badge bg-secondary">View Results</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">No past elections to display.</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>