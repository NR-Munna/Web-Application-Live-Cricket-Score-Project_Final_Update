<?php
require_once 'config/config.php';

// Check if match ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(SITE_URL . '/index.php');
}

$match_id = (int)$_GET['id'];

// Get match information
$sql = "SELECT * FROM matches WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $match_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect(SITE_URL . '/index.php');
}

$match = $result->fetch_assoc();

// Get match score information
$sql = "SELECT ms.*, 
        b.name as batsman_name, 
        bo.name as bowler_name, 
        f.name as fielder_name 
        FROM match_scores ms
        LEFT JOIN players b ON ms.batsman_id = b.id
        LEFT JOIN players bo ON ms.bowler_id = bo.id
        LEFT JOIN players f ON ms.fielder_id = f.id
        WHERE ms.match_id = ? 
        ORDER BY ms.innings ASC, ms.over_number ASC, ms.ball_number ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $match_id);
$stmt->execute();
$score_result = $stmt->get_result();

// Prepare data for display
$innings_data = [];
$batsmen_stats = [];
$bowler_stats = [];

if ($score_result && $score_result->num_rows > 0) {
    // Process each ball
    while ($ball = $score_result->fetch_assoc()) {
        $innings = $ball['innings'];
        $batsman_id = $ball['batsman_id'];
        $bowler_id = $ball['bowler_id'];
        
        // Initialize innings data if not exists
        if (!isset($innings_data[$innings])) {
            $innings_data[$innings] = [
                'team' => ($innings == 1) ? $match['team1'] : $match['team2'],
                'total_runs' => 0,
                'total_wickets' => 0,
                'total_overs' => 0,
                'total_balls' => 0,
                'extras' => 0,
                'balls' => []
            ];
        }
        
        // Add ball to innings
        $innings_data[$innings]['balls'][] = $ball;
        
        // Update innings totals
        $innings_data[$innings]['total_runs'] += $ball['runs'] + $ball['extras'];
        if ($ball['wicket'] == 1) {
            $innings_data[$innings]['total_wickets']++;
        }
        $innings_data[$innings]['extras'] += $ball['extras'];
        
        // Update overs count
        if ($ball['ball_number'] == 6) {
            $innings_data[$innings]['total_overs']++;
            $innings_data[$innings]['total_balls'] = 0;
        } else {
            $innings_data[$innings]['total_balls'] = $ball['ball_number'];
        }
        
        // Initialize batsman stats if not exists
        if (!isset($batsmen_stats[$innings][$batsman_id])) {
            $batsmen_stats[$innings][$batsman_id] = [
                'name' => $ball['batsman_name'],
                'runs' => 0,
                'balls' => 0,
                'fours' => 0,
                'sixes' => 0,
                'out' => false,
                'out_type' => '',
                'out_bowler' => '',
                'out_fielder' => ''
            ];
        }
        
        // Update batsman stats
        $batsmen_stats[$innings][$batsman_id]['runs'] += $ball['runs'];
        $batsmen_stats[$innings][$batsman_id]['balls']++;
        if ($ball['runs'] == 4) {
            $batsmen_stats[$innings][$batsman_id]['fours']++;
        }
        if ($ball['runs'] == 6) {
            $batsmen_stats[$innings][$batsman_id]['sixes']++;
        }
        
        // Update batsman dismissal info
        if ($ball['wicket'] == 1) {
            $batsmen_stats[$innings][$batsman_id]['out'] = true;
            $batsmen_stats[$innings][$batsman_id]['out_type'] = $ball['wicket_type'];
            $batsmen_stats[$innings][$batsman_id]['out_bowler'] = $ball['bowler_name'];
            $batsmen_stats[$innings][$batsman_id]['out_fielder'] = $ball['fielder_name'];
        }
        
        // Initialize bowler stats if not exists
        if (!isset($bowler_stats[$innings][$bowler_id])) {
            $bowler_stats[$innings][$bowler_id] = [
                'name' => $ball['bowler_name'],
                'overs' => 0,
                'balls' => 0,
                'maidens' => 0,
                'runs' => 0,
                'wickets' => 0,
                'economy' => 0,
                'current_over_runs' => 0
            ];
        }
        
        // Update bowler stats
        $bowler_stats[$innings][$bowler_id]['balls']++;
        $bowler_stats[$innings][$bowler_id]['runs'] += $ball['runs'] + $ball['extras'];
        $bowler_stats[$innings][$bowler_id]['current_over_runs'] += $ball['runs'] + $ball['extras'];
        
        if ($ball['wicket'] == 1) {
            $bowler_stats[$innings][$bowler_id]['wickets']++;
        }
        
        // Update overs and maidens
        if ($ball['ball_number'] == 6) {
            $bowler_stats[$innings][$bowler_id]['overs']++;
            if ($bowler_stats[$innings][$bowler_id]['current_over_runs'] == 0) {
                $bowler_stats[$innings][$bowler_id]['maidens']++;
            }
            $bowler_stats[$innings][$bowler_id]['current_over_runs'] = 0;
        }
        
        // Calculate economy rate
        $total_overs = $bowler_stats[$innings][$bowler_id]['overs'] + ($bowler_stats[$innings][$bowler_id]['balls'] % 6) / 6;
        if ($total_overs > 0) {
            $bowler_stats[$innings][$bowler_id]['economy'] = $bowler_stats[$innings][$bowler_id]['runs'] / $total_overs;
        }
    }
}

// Page title
$page_title = $match['team1'] . " vs " . $match['team2'] . " - Match Details";
?>

<?php include_once 'includes/header.php'; ?>

<div class="container mt-4">
    <!-- Match Header -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><?php echo $match['team1'] . " vs " . $match['team2']; ?></h4>
                <span class="badge bg-light text-dark"><?php echo $match['match_type']; ?> Match</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Venue:</strong> <?php echo $match['venue']; ?></p>
                    <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($match['match_date'])); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Status:</strong> 
                        <span class="badge 
                            <?php 
                            echo ($match['status'] == 'Live') ? 'bg-danger' : 
                                (($match['status'] == 'Completed') ? 'bg-success' : 'bg-secondary'); 
                            ?>">
                            <?php echo $match['status']; ?>
                        </span>
                    </p>
                    <p><strong>Total Overs:</strong> <?php echo $match['total_overs']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($innings_data)): ?>
        <div class="alert alert-info">No score information available for this match yet.</div>
    <?php else: ?>
        <!-- Match Summary -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Match Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($innings_data as $innings => $data): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0"><?php echo $data['team']; ?> Innings</h5>
                                </div>
                                <div class="card-body">
                                    <h3><?php echo $data['total_runs']; ?>/<?php echo $data['total_wickets']; ?></h3>
                                    <p><?php echo $data['total_overs'] . '.' . $data['total_balls']; ?> Overs</p>
                                    <p>Extras: <?php echo $data['extras']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($innings_data) == 2): ?>
                    <div class="mt-4 text-center">
                        <h4>
                            <?php
                            $team1_score = $innings_data[1]['total_runs'];
                            $team2_score = $innings_data[2]['total_runs'];
                            $team2_wickets = $innings_data[2]['total_wickets'];
                            
                            if ($team1_score > $team2_score) {
                                echo $match['team1'] . ' won by ' . ($team1_score - $team2_score) . ' runs';
                            } elseif ($team2_score > $team1_score) {
                                echo $match['team2'] . ' won by ' . (10 - $team2_wickets) . ' wickets';
                            } else {
                                echo 'Match tied';
                            }
                            ?>
                        </h4>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Innings Details -->
        <?php foreach ($innings_data as $innings => $data): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><?php echo $data['team']; ?> Innings - Batting</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Batsman</th>
                                    <th>Dismissal</th>
                                    <th>Runs</th>
                                    <th>Balls</th>
                                    <th>4s</th>
                                    <th>6s</th>
                                    <th>SR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($batsmen_stats[$innings])): ?>
                                    <?php foreach ($batsmen_stats[$innings] as $batsman_id => $batsman): ?>
                                        <tr>
                                            <td><?php echo $batsman['name']; ?></td>
                                            <td>
                                                <?php 
                                                if ($batsman['out']) {
                                                    $dismissal = ucfirst(str_replace('_', ' ', $batsman['out_type']));
                                                    if ($batsman['out_type'] == 'caught' || $batsman['out_type'] == 'run_out') {
                                                        $dismissal .= ' by ' . $batsman['out_fielder'];
                                                    }
                                                    if ($batsman['out_type'] != 'run_out') {
                                                        $dismissal .= ' b ' . $batsman['out_bowler'];
                                                    }
                                                    echo $dismissal;
                                                } else {
                                                    echo 'Not Out';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo $batsman['runs']; ?></td>
                                            <td><?php echo $batsman['balls']; ?></td>
                                            <td><?php echo $batsman['fours']; ?></td>
                                            <td><?php echo $batsman['sixes']; ?></td>
                                            <td>
                                                <?php 
                                                if ($batsman['balls'] > 0) {
                                                    echo number_format(($batsman['runs'] / $batsman['balls']) * 100, 2);
                                                } else {
                                                    echo '0.00';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No batting data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2">Total</th>
                                    <th><?php echo $data['total_runs']; ?>/<?php echo $data['total_wickets']; ?></th>
                                    <th colspan="4"><?php echo $data['total_overs'] . '.' . $data['total_balls']; ?> Overs</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <?php echo ($innings == 1) ? $match['team2'] : $match['team1']; ?> Bowling
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Bowler</th>
                                    <th>Overs</th>
                                    <th>Maidens</th>
                                    <th>Runs</th>
                                    <th>Wickets</th>
                                    <th>Economy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $opposing_innings = ($innings == 1) ? 2 : 1;
                                if (isset($bowler_stats[$innings])): 
                                ?>
                                    <?php foreach ($bowler_stats[$innings] as $bowler_id => $bowler): ?>
                                        <tr>
                                            <td><?php echo $bowler['name']; ?></td>
                                            <td><?php echo $bowler['overs'] . '.' . ($bowler['balls'] % 6); ?></td>
                                            <td><?php echo $bowler['maidens']; ?></td>
                                            <td><?php echo $bowler['runs']; ?></td>
                                            <td><?php echo $bowler['wickets']; ?></td>
                                            <td><?php echo number_format($bowler['economy'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No bowling data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Over-by-Over Analysis -->
        <?php if ($match['status'] == 'Live'): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Live Commentary</h5>
            </div>
            <div class="card-body">
                <div class="commentary-feed">
                    <?php
                    // Get the current innings
                    $current_innings = max(array_keys($innings_data));
                    $current_data = $innings_data[$current_innings];
                    
                    // Get the last 10 balls in reverse order
                    $last_balls = array_slice($current_data['balls'], -10);
                    $last_balls = array_reverse($last_balls);
                    
                    foreach ($last_balls as $ball) {
                        echo '<div class="commentary-item mb-3 p-2 border-bottom">';
                        echo '<strong>Over ' . ($ball['over_number'] + 1) . '.' . $ball['ball_number'] . '</strong>: ';
                        echo $ball['bowler_name'] . ' to ' . $ball['batsman_name'] . ', ';
                        
                        // Ball description
                        if ($ball['wicket'] == 1) {
                            echo '<span class="text-danger">WICKET!</span> ';
                            $dismissal = ucfirst(str_replace('_', ' ', $ball['wicket_type']));
                            if ($ball['wicket_type'] == 'caught' || $ball['wicket_type'] == 'run_out') {
                                $dismissal .= ' by ' . $ball['fielder_name'];
                            }
                            echo $dismissal . '. ';
                        }
                        
                        if ($ball['runs'] == 0) {
                            echo 'No run';
                        } else {
                            echo $ball['runs'] . ' run' . ($ball['runs'] > 1 ? 's' : '');
                        }
                        
                        if ($ball['extras'] > 0) {
                            echo ', ' . $ball['extras'] . ' ' . ucfirst(str_replace('_', ' ', $ball['extra_type']));
                        }
                        
                        echo '</div>';
                    }
                    ?>
                </div>
                
                <?php if ($match['status'] == 'Live'): ?>
                <div class="text-center mt-3">
                    <button id="refreshCommentary" class="btn btn-primary">Refresh Commentary</button>
                </div>
                <script>
                    document.getElementById('refreshCommentary').addEventListener('click', function() {
                        location.reload();
                    });
                </script>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
