<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'voter') {
    header("Location: ../auth/login.php");
    exit();
}

$session_id = $_GET['session_id'] ?? 0;

// Get session details
$stmt = $pdo->prepare("SELECT * FROM VoteSession WHERE VoteSessionID = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    $_SESSION['error'] = "Invalid election session.";
    header("Location: dashboard.php");
    exit();
}

// Get positions for this session
$positions_stmt = $pdo->prepare("SELECT * FROM Post WHERE VoteSessionID = ?");
$positions_stmt->execute([$session_id]);
$positions = $positions_stmt->fetchAll();

// Check if results should be visible (session date is in the past)
$current_date = date('Y-m-d');
if ($session['Date'] >= $current_date) {
    $_SESSION['error'] = "Results are not available until after the election date.";
    header("Location: dashboard.php");
    exit();
}
?>

<h2>Results: <?php echo htmlspecialchars($session['Name']); ?></h2>
<p class="lead">Election held on <?php echo date('F j, Y', strtotime($session['Date'])); ?></p>

<?php foreach ($positions as $position): ?>
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h4><?php echo htmlspecialchars($position['PositionName']); ?></h4>
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
            
            <h5>Total Votes: <?php echo $total_votes; ?></h5>
            
            <?php if (count($results) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Party</th>
                                <th>Votes</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['Name']); ?></td>
                                    <td><?php echo htmlspecialchars($result['Party'] ?? 'N/A'); ?></td>
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
                
                <div class="mt-4">
                    <h5>Vote Distribution</h5>
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="chart-<?php echo $position['PositionID']; ?>"></canvas>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">No candidates for this position.</div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($positions as $position): ?>
        <?php if (count($results) > 0): ?>
            const ctx<?php echo $position['PositionID']; ?> = document.getElementById('chart-<?php echo $position['PositionID']; ?>');
            
            new Chart(ctx<?php echo $position['PositionID']; ?>, {
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
                            'rgba(54, 162, 235, 0.5)',
                            'rgba(255, 99, 132, 0.5)',
                            'rgba(255, 206, 86, 0.5)',
                            'rgba(75, 192, 192, 0.5)',
                            'rgba(153, 102, 255, 0.5)',
                            'rgba(255, 159, 64, 0.5)'
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
                            beginAtZero: true
                        }
                    }
                }
            });
        <?php endif; ?>
    <?php endforeach; ?>
});
</script>

<?php require_once '../../includes/footer.php'; ?>