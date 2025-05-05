<?php
require_once 'config/config.php';

// Check if player ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: players.php");
    exit;
}

$player_id = (int)$_GET['id'];

// Get player details
$player_sql = "SELECT * FROM players WHERE id = ?";
$stmt = $conn->prepare($player_sql);
$stmt->bind_param("i", $player_id);
$stmt->execute();
$player_result = $stmt->get_result();

if ($player_result->num_rows === 0) {
    header("Location: players.php");
    exit;
}

$player = $player_result->fetch_assoc();

// Get player batting stats
$batting_sql = "SELECT 
    ms.match_id,
    m.team1,
    m.team2,
    m.venue,
    m.match_date,
    m.match_type,
    SUM(ms.runs) as runs_scored,
    COUNT(CASE WHEN ms.wicket = 1 AND ms.batsman_id = ? THEN 1 ELSE NULL END) as out_status,
    COUNT(*) as balls_faced,
    SUM(CASE WHEN ms.runs = 4 THEN 1 ELSE 0 END) as fours,
    SUM(CASE WHEN ms.runs = 6 THEN 1 ELSE 0 END) as sixes
    FROM match_scores ms
    JOIN matches m ON ms.match_id = m.id
    WHERE ms.batsman_id = ?
    GROUP BY ms.match_id
    ORDER BY m.match_date DESC";

$stmt = $conn->prepare($batting_sql);
$stmt->bind_param("ii", $player_id, $player_id);
$stmt->execute();
$batting_result = $stmt->get_result();

// Get player bowling stats
$bowling_sql = "SELECT 
    ms.match_id,
    m.team1,
    m.team2,
    m.venue,
    m.match_date,
    m.match_type,
    SUM(ms.wicket) as wickets_taken,
    SUM(ms.runs) as runs_conceded,
    COUNT(*) as balls_bowled,
    COUNT(DISTINCT CONCAT(ms.over_number, '.', ms.ball_number)) as overs_bowled
    FROM match_scores ms
    JOIN matches m ON ms.match_id = m.id
    WHERE ms.bowler_id = ?
    GROUP BY ms.match_id
    ORDER BY m.match_date DESC";

$stmt = $conn->prepare($bowling_sql);
$stmt->bind_param("i", $player_id);
$stmt->execute();
$bowling_result = $stmt->get_result();

// Calculate career stats
$career_batting_sql = "SELECT 
    COUNT(DISTINCT match_id) as matches_played,
    SUM(runs) as total_runs,
    ROUND(SUM(runs) / NULLIF(COUNT(DISTINCT match_id), 0), 2) as average,
    MAX(runs) as highest_score,
    SUM(CASE WHEN runs >= 50 AND runs < 100 THEN 1 ELSE 0 END) as fifties,
    SUM(CASE WHEN runs >= 100 THEN 1 ELSE 0 END) as hundreds,
    COUNT(*) as balls_faced,
    ROUND((SUM(runs) * 100.0) / NULLIF(COUNT(*), 0), 2) as strike_rate,
    SUM(CASE WHEN runs = 4 THEN 1 ELSE 0 END) as fours,
    SUM(CASE WHEN runs = 6 THEN 1 ELSE 0 END) as sixes
    FROM match_scores 
    WHERE batsman_id = ?";

$stmt = $conn->prepare($career_batting_sql);
$stmt->bind_param("i", $player_id);
$stmt->execute();
$career_batting = $stmt->get_result()->fetch_assoc();

$career_bowling_sql = "SELECT 
    COUNT(DISTINCT match_id) as matches_bowled,
    SUM(wicket) as total_wickets,
    ROUND(SUM(runs) / NULLIF(SUM(wicket), 0), 2) as bowling_average,
    SUM(runs) as runs_conceded,
    COUNT(*) as balls_bowled,
    ROUND((SUM(runs) * 6.0) / NULLIF(COUNT(*), 0), 2) as economy,
    MAX(SUM(wicket)) OVER (PARTITION BY match_id) as best_bowling
    FROM match_scores 
    WHERE bowler_id = ?
    GROUP BY match_id";

$stmt = $conn->prepare($career_bowling_sql);
$stmt->bind_param("i", $player_id);
$stmt->execute();
$bowling_stats_result = $stmt->get_result();

$career_bowling = [
    'matches_bowled' => 0,
    'total_wickets' => 0,
    'bowling_average' => 0,
    'economy' => 0,
    'best_bowling' => 0
];

if ($bowling_stats_result->num_rows > 0) {
    $total_wickets = 0;
    $total_runs = 0;
    $total_balls = 0;
    $best_bowling = 0;
    $matches_bowled = 0;
    
    while ($row = $bowling_stats_result->fetch_assoc()) {
        $total_wickets += $row['total_wickets'];
        $total_runs += $row['runs_conceded'];
        $total_balls += $row['balls_bowled'];
        $matches_bowled++;
        
        if ($row['total_wickets'] > $best_bowling) {
            $best_bowling = $row['total_wickets'];
        }
    }
    
    $career_bowling = [
        'matches_bowled' => $matches_bowled,
        'total_wickets' => $total_wickets,
        'bowling_average' => $total_wickets > 0 ? round($total_runs / $total_wickets, 2) : 0,
        'economy' => $total_balls > 0 ? round(($total_runs * 6) / $total_balls, 2) : 0,
        'best_bowling' => $best_bowling
    ];
}

// Get recent performances
$recent_performances_sql = "SELECT 
    ms.id,
    ms.match_id,
    m.team1,
    m.team2,
    m.match_date,
    ms.innings,
    ms.over_number,
    ms.ball_number,
    ms.runs,
    ms.extras,
    ms.extra_type,
    ms.wicket,
    ms.wicket_type,
    b.name as bowler_name,
    f.name as fielder_name
    FROM match_scores ms
    JOIN matches m ON ms.match_id = m.id
    LEFT JOIN players b ON ms.bowler_id = b.id
    LEFT JOIN players f ON ms.fielder_id = f.id
    WHERE ms.batsman_id = ? AND ms.wicket = 1
    ORDER BY m.match_date DESC, ms.id DESC
    LIMIT 5";

$stmt = $conn->prepare($recent_performances_sql);
$stmt->bind_param("i", $player_id);
$stmt->execute();
$recent_dismissals = $stmt->get_result();

// Page title
$page_title = $player['name'] . " - Player Profile";
?>

<?php include_once 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?php echo $player['name']; ?></h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="player-avatar mb-3">
                            <img src="assets/images/player-placeholder.jpg" alt="<?php echo $player['name']; ?>" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                        </div>
                        <h5><?php echo $player['name']; ?></h5>
                        <p class="mb-0"><strong>Country:</strong> <?php echo $player['country']; ?></p>
                        <p><strong>Role:</strong> <?php echo $player['role']; ?></p>
                    </div>
                    
                    <div class="player-career-stats">
                        <h5 class="border-bottom pb-2">Career Summary</h5>
                        
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="stat-box p-2 rounded bg-light">
                                    <h6>Matches</h6>
                                    <p class="fs-4 mb-0 fw-bold"><?php echo $career_batting['matches_played'] ?? 0; ?></p>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-box p-2 rounded bg-light">
                                    <h6>Runs</h6>
                                    <p class="fs-4 mb-0 fw-bold"><?php echo $career_batting['total_runs'] ?? 0; ?></p>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-box p-2 rounded bg-light">
                                    <h6>Wickets</h6>
                                    <p class="fs-4 mb-0 fw-bold"><?php echo $career_bowling['total_wickets'] ?? 0; ?></p>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-box p-2 rounded bg-light">
                                    <h6>Batting Avg</h6>
                                    <p class="fs-4 mb-0 fw-bold"><?php echo $career_batting['average'] ?? 0; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="players.php" class="btn btn-secondary">Back to Players</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <ul class="nav nav-tabs" id="playerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="batting-tab" data-bs-toggle="tab" data-bs-target="#batting" type="button" role="tab" aria-controls="batting" aria-selected="true">Batting</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="bowling-tab" data-bs-toggle="tab" data-bs-target="#bowling" type="button" role="tab" aria-controls="bowling" aria-selected="false">Bowling</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="recent-tab" data-bs-toggle="tab" data-bs-target="#recent" type="button" role="tab" aria-controls="recent" aria-selected="false">Recent Form</button>
                </li>
            </ul>
            
            <div class="tab-content p-3 border border-top-0 mb-4" id="playerTabContent">
                <!-- Batting Tab -->
                <div class="tab-pane fade show active" id="batting" role="tabpanel" aria-labelledby="batting-tab">
                    <h4 class="mb-3">Batting Statistics</h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Career Batting</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <th>Matches</th>
                                                <td><?php echo $career_batting['matches_played'] ?? 0; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Runs</th>
                                                <td><?php echo $career_batting['total_runs'] ?? 0; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Highest Score</th>
                                                <td><?php echo $career_batting['highest_score'] ?? 0; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Average</th>
                                                <td><?php echo $career_batting['average'] ?? 0; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Strike Rate</th>
                                                <td><?php echo $career_batting['strike_rate'] ?? 0; ?></td>
                                            </tr>
                                            <tr>
                                                <th>50s / 100s</th>
                                                <td><?php echo ($career_batting['fifties'] ?? 0) . ' / ' . ($career_batting['hundreds'] ?? 0); ?></td>
                                            </tr>
                                            <tr>
                                                <th>4s / 6s</th>
                                                <td><?php echo ($career_batting['fours'] ?? 0) . ' / ' . ($career_batting['sixes'] ?? 0); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Batting Form</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="battingChart" width="400" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Batting Innings</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Match</th>
                                    <th>Runs</th>
                                    <th>Balls</th>
                                    <th>4s</th>
                                    <th>6s</th>
                                    <th>SR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($batting_result->num_rows > 0): ?>
                                    <?php while ($innings = $batting_result->fetch_assoc()): 
                                        $strike_rate = $innings['balls_faced'] > 0 ? round(($innings['runs_scored'] * 100) / $innings['balls_faced'], 2) : 0;
                                    ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($innings['match_date'])); ?></td>
                                            <td>
                                                <a href="match-details.php?id=<?php echo $innings['match_id']; ?>">
                                                    <?php echo $innings['team1'] . ' vs ' . $innings['team2']; ?>
                                                </a>
                                                <small class="d-block text-muted"><?php echo $innings['match_type']; ?></small>
                                            </td>
                                            <td><?php echo $innings['runs_scored']; ?></td>
                                            <td><?php echo $innings['balls_faced']; ?></td>
                                            <td><?php echo $innings['fours']; ?></td>
                                            <td><?php echo $innings['sixes']; ?></td>
                                            <td><?php echo $strike_rate; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No batting innings found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Bowling Tab -->
                <div class="tab-pane fade" id="bowling" role="tabpanel" aria-labelledby="bowling-tab">
                    <h4 class="mb-3">Bowling Statistics</h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Career Bowling</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <th>Matches</th>
                                                <td><?php echo $career_bowling['matches_bowled'] ?? 0; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Wickets</th>
                                                <td><?php echo $career_bowling['total_wickets'] ?? 0; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Best Bowling</th>
                                                <td><?php echo $career_bowling['best_bowling'] ?? 0; ?> wickets</td>
                                            </tr>
                                            <tr>
                                                <th>Average</th>
                                                <td><?php echo $career_bowling['bowling_average'] ?? 0; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Economy</th>
                                                <td><?php echo $career_bowling['economy'] ?? 0; ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Bowling Form</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="bowlingChart" width="400" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Bowling Performances</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Match</th>
                                    <th>Overs</th>
                                    <th>Wickets</th>
                                    <th>Runs</th>
                                    <th>Econ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($bowling_result->num_rows > 0): ?>
                                    <?php while ($bowling = $bowling_result->fetch_assoc()): 
                                        $overs = floor($bowling['balls_bowled'] / 6);
                                        $balls = $bowling['balls_bowled'] % 6;
                                        $economy = $bowling['balls_bowled'] > 0 ? round(($bowling['runs_conceded'] * 6) / $bowling['balls_bowled'], 2) : 0;
                                    ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($bowling['match_date'])); ?></td>
                                            <td>
                                                <a href="match-details.php?id=<?php echo $bowling['match_id']; ?>">
                                                    <?php echo $bowling['team1'] . ' vs ' . $bowling['team2']; ?>
                                                </a>
                                                <small class="d-block text-muted"><?php echo $bowling['match_type']; ?></small>
                                            </td>
                                            <td><?php echo $overs . '.' . $balls; ?></td>
                                            <td><?php echo $bowling['wickets_taken']; ?></td>
                                            <td><?php echo $bowling['runs_conceded']; ?></td>
                                            <td><?php echo $economy; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No bowling performances found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Recent Form Tab -->
                <div class="tab-pane fade" id="recent" role="tabpanel" aria-labelledby="recent-tab">
                    <h4 class="mb-3">Recent Form</h4>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Recent Dismissals</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($recent_dismissals->num_rows > 0): ?>
                                        <div class="list-group">
                                            <?php while ($dismissal = $recent_dismissals->fetch_assoc()): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1">
                                                            <?php echo $dismissal['team1'] . ' vs ' . $dismissal['team2']; ?>
                                                        </h6>
                                                        <small><?php echo date('d M Y', strtotime($dismissal['match_date'])); ?></small>
                                                    </div>
                                                    <p class="mb-1">
                                                        <strong>Dismissal:</strong> 
                                                        <?php 
                                                        echo ucfirst($dismissal['wicket_type']);
                                                        if ($dismissal['wicket_type'] == 'caught' || $dismissal['wicket_type'] == 'stumped' || $dismissal['wicket_type'] == 'run_out') {
                                                            echo ' by ' . $dismissal['fielder_name'];
                                                        }
                                                        echo ', bowled by ' . $dismissal['bowler_name'];
                                                        ?>
                                                    </p>
                                                    <small>
                                                        Over <?php echo ($dismissal['over_number'] + 1) . '.' . $dismissal['ball_number']; ?>, 
                                                        Innings <?php echo $dismissal['innings']; ?>
                                                    </small>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-center">No recent dismissals found</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Performance Trend</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="performanceTrendChart" width="400" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Match Contribution</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="contributionChart" width="400" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sample data for charts - in a real app, this would come from PHP
    <?php
    // Prepare data for batting chart
    $batting_labels = [];
    $batting_data = [];
    
    $stmt = $conn->prepare("SELECT 
        m.match_date,
        SUM(ms.runs) as runs_scored
        FROM match_scores ms
        JOIN matches m ON ms.match_id = m.id
        WHERE ms.batsman_id = ?
        GROUP BY ms.match_id
        ORDER BY m.match_date ASC
        LIMIT 10");
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    $chart_result = $stmt->get_result();
    
    while ($row = $chart_result->fetch_assoc()) {
        $batting_labels[] = date('d M', strtotime($row['match_date']));
        $batting_data[] = $row['runs_scored'];
    }
    
    // Prepare data for bowling chart
    $bowling_labels = [];
    $bowling_data = [];
    
    $stmt = $conn->prepare("SELECT 
        m.match_date,
        SUM(ms.wicket) as wickets_taken
        FROM match_scores ms
        JOIN matches m ON ms.match_id = m.id
        WHERE ms.bowler_id = ?
        GROUP BY ms.match_id
        ORDER BY m.match_date ASC
        LIMIT 10");
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    $chart_result = $stmt->get_result();
    
    while ($row = $chart_result->fetch_assoc()) {
        $bowling_labels[] = date('d M', strtotime($row['match_date']));
        $bowling_data[] = $row['wickets_taken'];
    }
    ?>
    
    // Batting Chart
    var battingCtx = document.getElementById('battingChart').getContext('2d');
    var battingChart = new Chart(battingCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($batting_labels); ?>,
            datasets: [{
                label: 'Runs Scored',
                data: <?php echo json_encode($batting_data); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Bowling Chart
    var bowlingCtx = document.getElementById('bowlingChart').getContext('2d');
    var bowlingChart = new Chart(bowlingCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($bowling_labels); ?>,
            datasets: [{
                label: 'Wickets Taken',
                data: <?php echo json_encode($bowling_data); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // Performance Trend Chart (placeholder with sample data)
    var performanceTrendCtx = document.getElementById('performanceTrendChart').getContext('2d');
    var performanceTrendChart = new Chart(performanceTrendCtx, {
        type: 'bar',
        data: {
            labels: ['T20', 'ODI', 'Test'],
            datasets: [{
                label: 'Batting Average',
                data: [
                    <?php echo rand(20, 40); ?>, 
                    <?php echo rand(30, 50); ?>, 
                    <?php echo rand(25, 45); ?>
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Contribution Chart (placeholder with sample data)
    var contributionCtx = document.getElementById('contributionChart').getContext('2d');
    var contributionChart = new Chart(contributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Batting', 'Bowling', 'Fielding'],
            datasets: [{
                label: 'Contribution',
                data: [
                    <?php echo $career_batting['total_runs'] ?? rand(100, 500); ?>,
                    <?php echo $career_bowling['total_wickets'] * 20 ?? rand(20, 200); ?>,
                    <?php echo rand(10, 100); ?>
                ],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        }
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>
