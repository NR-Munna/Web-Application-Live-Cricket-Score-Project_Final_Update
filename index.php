<?php
require_once 'config/config.php';

// Get live matches
$live_matches_sql = "SELECT * FROM matches WHERE status = 'Live' ORDER BY match_date DESC LIMIT 3";
$live_matches_result = $conn->query($live_matches_sql);

// Get upcoming matches
$upcoming_matches_sql = "SELECT * FROM matches WHERE status = 'Upcoming' ORDER BY match_date ASC LIMIT 3";
$upcoming_matches_result = $conn->query($upcoming_matches_sql);

// Get recent matches
$recent_matches_sql = "SELECT * FROM matches WHERE status = 'Completed' ORDER BY match_date DESC LIMIT 3";
$recent_matches_result = $conn->query($recent_matches_sql);

// Page title
$page_title = "Home - Live Cricket Score";
?>

<?php include_once 'includes/header.php'; ?>

<!-- Hero Section -->
<div class="container-fluid hero-section py-5 mb-4">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-md-8">
                <h1 class="display-4 fw-bold text-white mb-3">Live Cricket Score</h1>
                <p class="lead text-white mb-4">Get real-time updates on cricket matches happening around the world.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="live-score.php" class="btn btn-primary btn-lg"><i class="bi bi-broadcast"></i> Live Scores</a>
                    <a href="schedules.php" class="btn btn-outline-light btn-lg"><i class="bi bi-calendar-event"></i> Schedules</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Live Matches -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="bi bi-broadcast text-danger"></i> Live Matches</h3>
        <a href="live-score.php" class="btn btn-sm btn-outline-primary">View All Live Matches</a>
    </div>
    
    <div class="row">
        <?php 
        // Get all live matches
        $live_matches_sql = "SELECT * FROM matches WHERE status = 'Live' ORDER BY match_date DESC";
        $live_matches_result = $conn->query($live_matches_sql);
        
        if ($live_matches_result && $live_matches_result->num_rows > 0) {
            while ($match = $live_matches_result->fetch_assoc()) {
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
                        ORDER BY ms.id DESC LIMIT 3";
                $stmt = $conn->prepare($commentary_sql);
                $stmt->bind_param("i", $match['id']);
                $stmt->execute();
                $commentary_result = $stmt->get_result();
        ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-danger text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="live-indicator"></span>
                                <strong><?php echo $match['team1'] . ' vs ' . $match['team2']; ?></strong>
                            </div>
                            <span class="badge bg-light text-dark"><?php echo $match['match_type']; ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="match-info mb-2">
                            <small class="text-muted"><i class="bi bi-geo-alt"></i> <?php echo $match['venue']; ?> | <i class="bi bi-calendar3"></i> <?php echo date('d M Y', strtotime($match['match_date'])); ?></small>
                        </div>
                    
                        
                        <?php if ($commentary_result && $commentary_result->num_rows > 0): ?>
                        <div class="recent-commentary">
                            <h6 class="border-bottom pb-2">Recent Balls</h6>
                            <div class="commentary-list">
                                <?php while ($ball = $commentary_result->fetch_assoc()): ?>
                                    <div class="commentary-item p-2 small">
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
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="match-details.php?id=<?php echo $match['id']; ?>" class="btn btn-sm btn-primary w-100">View Full Scorecard</a>
                    </div>
                </div>
            </div>
        <?php 
            }
        } else {
        ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <h5 class="alert-heading">No Live Matches</h5>
                    <p>There are currently no live cricket matches in progress. Please check back later or view upcoming matches below.</p>
                </div>
            </div>
        <?php 
        }
        ?>
    </div>
</div>

<!-- Upcoming Matches -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="bi bi-calendar-event"></i> Upcoming Matches</h3>
        <a href="schedules.php" class="btn btn-sm btn-outline-secondary">View All Schedules</a>
    </div>
    
    <div class="row">
        <?php 
        // Reset and get upcoming matches
        $upcoming_matches_sql = "SELECT * FROM matches WHERE status = 'Upcoming' ORDER BY match_date ASC LIMIT 3";
        $upcoming_matches_result = $conn->query($upcoming_matches_sql);
        
        if ($upcoming_matches_result && $upcoming_matches_result->num_rows > 0) {
            while ($match = $upcoming_matches_result->fetch_assoc()) {
                // Calculate time remaining
                $match_date = new DateTime($match['match_date']);
                $now = new DateTime();
                $interval = $now->diff($match_date);
                
                if ($interval->days > 0) {
                    $time_remaining = $interval->days . ' days';
                } elseif ($interval->h > 0) {
                    $time_remaining = $interval->h . ' hours';
                } else {
                    $time_remaining = 'Starting soon';
                }
        ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong><?php echo $match['match_type']; ?> Match</strong>
                            <span class="badge bg-light text-dark">Upcoming</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-center"><?php echo $match['team1'] . ' vs ' . $match['team2']; ?></h5>
                        <div class="text-center mb-3">
                            <span class="badge bg-light text-dark p-2">
                                <i class="bi bi-clock"></i> Starts in <?php echo $time_remaining; ?>
                            </span>
                        </div>
                        <div class="match-info">
                            <p class="mb-1"><i class="bi bi-geo-alt"></i> <strong>Venue:</strong> <?php echo $match['venue']; ?></p>
                            <p class="mb-1"><i class="bi bi-calendar3"></i> <strong>Date:</strong> <?php echo date('F d, Y', strtotime($match['match_date'])); ?></p>
                            <p><i class="bi bi-stopwatch"></i> <strong>Total Overs:</strong> <?php echo $match['total_overs']; ?></p>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a href="match-details.php?id=<?php echo $match['id']; ?>" class="btn btn-sm btn-secondary">View Details</a>
                    </div>
                </div>
            </div>
        <?php 
            }
        } else {
        ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <h5 class="alert-heading">No Upcoming Matches</h5>
                    <p>There are currently no upcoming cricket matches scheduled. Please check back later.</p>
                </div>
            </div>
        <?php 
        }
        ?>
    </div>
</div>

<!-- Cricket News -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="bi bi-newspaper"></i> Latest Cricket News</h3>
    </div>
    <div class="row">
        <div class="col-md-12 news mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 image-container">
                            <img class="news-image img-fluid rounded" src="assets/images/news1.jpg" alt="Photo of cricket match" onerror="this.onerror=null; this.src='https://placehold.co/400x300?text=Cricket+News';">
                        </div>
                        <div class="col-md-9">
                            <h4>India Clinches World Cup in Thrilling Final</h4>
                            <p class="news-description">India defeated New Zealand by 4 wickets in a nail-biting World Cup final at Narendra Modi Stadium. Virat Kohli's 89 runs earned him the Player of the Match award, while Hardik Pandya and Ravindra Jadeja's unbeaten partnership sealed the victory for India.</p>
                            <a href="#" class="btn btn-sm btn-outline-primary">Read More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 news mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 image-container">
                            <img class="news-image img-fluid rounded" src="assets/images/news2.jpg" alt="Photo of cricket match" onerror="this.onerror=null; this.src='https://placehold.co/400x300?text=Cricket+News';">
                        </div>
                        <div class="col-md-9">
                            <h4>BCB Men's Contracts for 2025: Taskin Only Player in Grade A+</h4>
                            <p class="news-description">Pakistan are likely to tour Bangladesh for a series of white-ball matches in mid-2025, according to BCB president Faruque Ahmed. The tour is expected to include three ODIs and three T20Is in July and August.</p>
                            <a href="#" class="btn btn-sm btn-outline-primary">Read More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 news mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 image-container">
                            <img class="news-image img-fluid rounded" src="assets/images/news3.jpg" alt="Photo of cricket match" onerror="this.onerror=null; this.src='https://placehold.co/400x300?text=Cricket+News';">
                        </div>
                        <div class="col-md-9">
                            <h4>Australia Announces Squad for Upcoming Ashes Series</h4>
                            <p class="news-description">Cricket Australia has announced a 16-member squad for the upcoming Ashes series against England. The series is scheduled to begin in November 2025, with the first Test to be played at the Gabba in Brisbane.</p>
                            <a href="#" class="btn btn-sm btn-outline-primary">Read More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Matches -->
<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="bi bi-trophy"></i> Recent Matches</h3>
    </div>
    <div class="row">
        <?php 
        if ($recent_matches_result && $recent_matches_result->num_rows > 0) {
            while ($match = $recent_matches_result->fetch_assoc()) {
                // Get match score information
                $score_sql = "SELECT 
                    SUM(CASE WHEN innings = 1 THEN runs + extras ELSE 0 END) as team1_score,
                    SUM(CASE WHEN innings = 1 THEN wicket ELSE 0 END) as team1_wickets,
                    SUM(CASE WHEN innings = 2 THEN runs + extras ELSE 0 END) as team2_score,
                    SUM(CASE WHEN innings = 2 THEN wicket ELSE 0 END) as team2_wickets
                    FROM match_scores WHERE match_id = ?";
                $stmt = $conn->prepare($score_sql);
                $stmt->bind_param("i", $match['id']);
                $stmt->execute();
                $score_result = $stmt->get_result();
                $score = $score_result->fetch_assoc();
                
                // Format scores
                $team1_score = ($score && $score['team1_score']) ? $score['team1_score'] . '/' . $score['team1_wickets'] : 'Did not bat';
                $team2_score = ($score && $score['team2_score']) ? $score['team2_score'] . '/' . $score['team2_wickets'] : 'Did not bat';
                
                // Determine winner
                $result = '';
                if ($score && $score['team1_score'] && $score['team2_score']) {
                    if ($score['team1_score'] > $score['team2_score']) {
                        $result = $match['team1'] . ' won by ' . ($score['team1_score'] - $score['team2_score']) . ' runs';
                    } elseif ($score['team2_score'] > $score['team1_score']) {
                        $result = $match['team2'] . ' won by ' . (10 - $score['team2_wickets']) . ' wickets';
                    } else {
                        $result = 'Match tied';
                    }
                } else {
                    $result = 'Match completed';
                }
        ?>
            <div class="col-md-4 mb-3">
                <div class="card text-center h-100">
                    <div class="card-header bg-success text-white">
                        <strong><?php echo $match['match_type']; ?> Match</strong> - Completed
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $match['team1'] . ' vs ' . $match['team2']; ?></h5>
                        <p class="card-text"><?php echo date('F d, Y', strtotime($match['match_date'])); ?></p>
                        <p class="card-text"><?php echo $match['venue']; ?></p>
                        <hr>
                        <div class="team-score">
                            <p><strong><?php echo $match['team1']; ?>:</strong> <?php echo $team1_score; ?></p>
                            <p><strong><?php echo $match['team2']; ?>:</strong> <?php echo $team2_score; ?></p>
                            <p class="text-success"><strong><?php echo $result; ?></strong></p>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="match-details.php?id=<?php echo $match['id']; ?>" class="btn btn-success">View Details</a>
                    </div>
                </div>
            </div>
        <?php 
            }
        } else {
        ?>
            <div class="col-12">
                <div class="alert alert-info">No recent matches available.</div>
            </div>
        <?php 
        }
        ?>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
