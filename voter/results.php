<?php
session_start();
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'voter') {
    header("Location: ../auth/login.php");
    exit();
}

$session_id = $_GET['session_id'] ?? 0;

// Get session details with timezone info
$stmt = $pdo->prepare("
    SELECT *, 
    CONCAT(Date, ' ', StartTime) AS StartDateTime,
    CONCAT(Date, ' ', EndTime) AS EndDateTime
    FROM VoteSession 
    WHERE VoteSessionID = ?
");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    $_SESSION['error'] = "Invalid election session.";
    header("Location: dashboard.php");
    exit();
}

// Convert to proper datetime objects with timezone
$now = new DateTime('now', new DateTimeZone($session['Timezone']));
$end = new DateTime($session['EndDateTime'], new DateTimeZone($session['Timezone']));

// Check if results should be visible (session must be ended or past end time)
if ($session['Status'] !== 'ended' && $now < $end) {
    $timeLeft = $now->diff($end);
    $_SESSION['error'] = "Results will be available after " . $end->format('F j, Y g:i A') . 
                         " (" . $timeLeft->format('%h hours %i minutes') . " remaining)";
    header("Location: dashboard.php");
    exit();
}

// Get positions for this session
$positions_stmt = $pdo->prepare("SELECT * FROM Post WHERE VoteSessionID = ?");
$positions_stmt->execute([$session_id]);
$positions = $positions_stmt->fetchAll();
?>

<h2>Results: <?php echo htmlspecialchars($session['Name']); ?></h2>
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="lead mb-0">
        <i class="bi bi-calendar"></i> <?php echo $end->format('F j, Y'); ?> | 
        <i class="bi bi-clock"></i> <?php echo $end->format('g:i A'); ?> |
        <i class="bi bi-globe"></i> <?php echo $session['Timezone']; ?>
    </p>
    <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php foreach ($positions as $position): ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4><?php echo htmlspecialchars($position['PositionName']); ?></h4>
                <?php
                // Get winner for this position
                $winner_stmt = $pdo->prepare("
                    SELECT v.Name, c.Party, COUNT(vt.VoteID) AS VoteCount
                    FROM Candidate c
                    JOIN Voter v ON c.VoterID = v.VoterID
                    LEFT JOIN Vote vt ON c.CandidateID = vt.CandidateID
                    WHERE c.PositionID = ?
                    GROUP BY c.CandidateID
                    ORDER BY VoteCount DESC
                    LIMIT 1
                ");
                $winner_stmt->execute([$position['PositionID']]);
                $winner = $winner_stmt->fetch();
                ?>
                <?php if ($winner): ?>
                    <span class="badge bg-success">
                        <i class="bi bi-trophy"></i> Winner: <?php echo htmlspecialchars($winner['Name']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php
            // Get candidates and their vote counts
            $results_stmt = $pdo->prepare("
                SELECT c.CandidateID, v.Name, c.Party, COUNT(vt.VoteID) AS VoteCount
                FROM Candidate c
                JOIN Voter v ON c.VoterID = v.VoterID
                LEFT JOIN Vote vt ON c.CandidateID = vt.CandidateID
                WHERE c.PositionID = ?
                GROUP BY c.CandidateID
                ORDER BY VoteCount DESC
            ");
            $results_stmt->execute([$position['PositionID']]);
            $results = $results_stmt->fetchAll();
            
            // Get total votes for this position
            $total_stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM Vote v
                JOIN Post p ON v.PositionID = p.PositionID
                WHERE p.PositionID = ?
            ");
            $total_stmt->execute([$position['PositionID']]);
            $total_votes = $total_stmt->fetchColumn();
            ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body py-2">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-people"></i> Total Votes: <?php echo $total_votes; ?>
                            </h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body py-2">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-person-check"></i> Voter Turnout: 
                                <?php 
                                $total_voters_stmt = $pdo->prepare("SELECT COUNT(*) FROM Voter");
                                $total_voters_stmt->execute();
                                $total_voters = $total_voters_stmt->fetchColumn();
                                $turnout = $total_voters > 0 ? ($total_votes / $total_voters) * 100 : 0;
                                echo number_format($turnout, 1) . '%';
                                ?>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (count($results) > 0): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Rank</th>
                                <th>Candidate</th>
                                <th>Party</th>
                                <th>Votes</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $index => $result): ?>
                                <tr class="<?php echo $index === 0 ? 'table-success' : ''; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($result['Name']); ?>
                                        <?php if ($index === 0): ?>
                                            <span class="badge bg-success ms-2">Winner</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($result['Party'] ?? 'Independent'); ?></td>
                                    <td><?php echo $result['VoteCount']; ?></td>
                                    <td>
                                        <?php 
                                        $percentage = $total_votes > 0 ? ($result['VoteCount'] / $total_votes) * 100 : 0;
                                        echo number_format($percentage, 2) . '%';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="bi bi-bar-chart"></i> Vote Distribution</h5>
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="chart-bar-<?php echo $position['PositionID']; ?>"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="bi bi-pie-chart"></i> Vote Percentage</h5>
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="chart-pie-<?php echo $position['PositionID']; ?>"></canvas>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> No candidates for this position.
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($positions as $position): ?>
        <?php if (count($results) > 0): ?>
            // Bar Chart
            const barCtx<?php echo $position['PositionID']; ?> = document.getElementById('chart-bar-<?php echo $position['PositionID']; ?>');
            
            new Chart(barCtx<?php echo $position['PositionID']; ?>, {
                type: 'bar',
                data: {
                    labels: [<?php 
                        foreach ($results as $result) {
                            echo "'" . addslashes($result['Name']) . "',";
                        }
                    ?>],
                    datasets: [{
                        label: 'Votes',
                        data: [<?php 
                            foreach ($results as $result) {
                                echo $result['VoteCount'] . ",";
                            }
                        ?>],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Pie Chart
            const pieCtx<?php echo $position['PositionID']; ?> = document.getElementById('chart-pie-<?php echo $position['PositionID']; ?>');
            
            new Chart(pieCtx<?php echo $position['PositionID']; ?>, {
                type: 'pie',
                data: {
                    labels: [<?php 
                        foreach ($results as $result) {
                            echo "'" . addslashes($result['Name']) . "',";
                        }
                    ?>],
                    datasets: [{
                        data: [<?php 
                            foreach ($results as $result) {
                                echo $result['VoteCount'] . ",";
                            }
                        ?>],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)'
                        ],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} votes (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    <?php endforeach; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>