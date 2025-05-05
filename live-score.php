<?php
require_once 'config/config.php';

// Get all live matches
$live_matches_sql = "SELECT * FROM matches WHERE status = 'Live' ORDER BY match_date DESC";
$live_matches_result = $conn->query($live_matches_sql);

// Page title
$page_title = "Live Scores - Cricket Score";
?>

<?php include_once 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Live Cricket Scores</h1>
            
            <?php if ($live_matches_result && $live_matches_result->num_rows > 0): ?>
                <div class="row">
                    <?php while ($match = $live_matches_result->fetch_assoc()): ?>
                        <?php
                        // Get match score information
                        $score_sql = "SELECT 
                            SUM(CASE WHEN innings = 1 THEN runs + extras ELSE 0 END) as team1_score,
                            SUM(CASE WHEN innings = 1 THEN wicket ELSE 0 END) as team1_wickets,
                            SUM(CASE WHEN innings = 2 THEN runs + extras ELSE 0 END) as team2_score,
                            SUM(CASE WHEN innings = 2 THEN wicket ELSE 0 END) as team2_wickets,
                            MAX(CASE WHEN innings = 1 THEN over_number ELSE 0 END) as team1_overs,
                            MAX(CASE WHEN innings = 1 THEN ball_number ELSE 0 END) as team1_balls,
                            MAX(CASE WHEN innings = 2 THEN over_number ELSE 0 END) as team2_overs,
                            MAX(CASE WHEN innings = 2 THEN ball_number ELSE 0 END) as team2_balls
                            FROM match_scores WHERE match_id = ?";
                        $stmt = $conn->prepare($score_sql);
                        $stmt->bind_param("i", $match['id']);
                        $stmt->execute();
                        $score_result = $stmt->get_result();
                        $score = $score_result->fetch_assoc();
                        
                        // Format scores
                        $team1_score = ($score && $score['team1_score']) ? $score['team1_score'] . '/' . $score['team1_wickets'] : 'Yet to bat';
                        $team1_overs = ($score && $score['team1_overs']) ? '(' . $score['team1_overs'] . '.' . $score['team1_balls'] . ' overs)' : '';
                        
                        $team2_score = ($score && $score['team2_score']) ? $score['team2_score'] . '/' . $score['team2_wickets'] : 'Yet to bat';
                        $team2_overs = ($score && $score['team2_overs']) ? '(' . $score['team2_overs'] . '.' . $score['team2_balls'] . ' overs)' : '';
                        
                        // Get last few balls for commentary
                        $commentary_sql = "SELECT ms.*, 
                                b.name as batsman_name, 
                                bo.name as bowler_name
                                FROM match_scores ms
                                LEFT JOIN players b ON ms.batsman_id = b.id
                                LEFT JOIN players bo ON ms.bowler_id = bo.id
                                WHERE ms.match_id = ? 
                                ORDER BY ms.id DESC LIMIT 5";
                        $stmt = $conn->prepare($commentary_sql);
                        $stmt->bind_param("i", $match['id']);
                        $stmt->execute();
                        $commentary_result = $stmt->get_result();
                        ?>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><?php echo $match['team1'] . ' vs ' . $match['team2']; ?></h5>
                                        <span class="badge bg-light text-dark"><?php echo $match['match_type']; ?></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="match-info mb-3">
                                        <p class="mb-1"><strong>Venue:</strong> <?php echo $match['venue']; ?></p>
                                        <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($match['match_date'])); ?></p>
                                    </div>
                                    
                                    <?php if ($commentary_result && $commentary_result->num_rows > 0): ?>
                                    <div class="recent-commentary">
                                        <h6>Recent Commentary</h6>
                                        <div class="commentary-list">
                                            <?php while ($ball = $commentary_result->fetch_assoc()): ?>
                                                <div class="commentary-item p-2 border-bottom">
                                                    <small>
                                                        <strong>Over <?php echo ($ball['over_number'] + 1) . '.' . $ball['ball_number']; ?>:</strong>
                                                        <?php 
                                                        echo $ball['bowler_name'] . ' to ' . $ball['batsman_name'] . ', ';
                                                        
                                                        if ($ball['wicket'] == 1) {
                                                            echo '<span class="text-danger">WICKET!</span> ';
                                                        }
                                                        
                                                        if ($ball['runs'] == 0) {
                                                            echo 'No run';
                                                        } else {
                                                            echo $ball['runs'] . ' run' . ($ball['runs'] > 1 ? 's' : '');
                                                        }
                                                        
                                                        if ($ball['extras'] > 0) {
                                                            echo ', ' . $ball['extras'] . ' ' . ucfirst(str_replace('_', ' ', $ball['extra_type']));
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="text-center mt-3">
                                        <a href="match-details.php?id=<?php echo $match['id']; ?>" class="btn btn-primary">View Full Scorecard</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="text-center mt-3 mb-5">
                    <button id="refreshScores" class="btn btn-lg btn-success">
                        <i class="bi bi-arrow-clockwise"></i> Refresh Scores
                    </button>
                </div>
                
                <script>
                    document.getElementById('refreshScores').addEventListener('click', function() {
                        location.reload();
                    });
                </script>
            <?php else: ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">No Live Matches</h4>
                    <p>There are currently no live cricket matches in progress. Please check back later.</p>
                    <hr>
                    <p class="mb-0">You can view upcoming matches on the <a href="index.php" class="alert-link">home page</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
