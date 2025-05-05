<?php
require_once 'config/config.php';

// Get all upcoming matches
$upcoming_matches_sql = "SELECT * FROM matches WHERE status = 'Upcoming' ORDER BY match_date ASC";
$upcoming_matches_result = $conn->query($upcoming_matches_sql);

// Page title
$page_title = "Match Schedules - Cricket Score";
?>

<?php include_once 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Upcoming Cricket Matches</h1>
            
            <?php if ($upcoming_matches_result && $upcoming_matches_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Match</th>
                                <th>Type</th>
                                <th>Venue</th>
                                <th>Time Remaining</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($match = $upcoming_matches_result->fetch_assoc()): ?>
                                <?php
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
                                <tr>
                                    <td><?php echo date('F d, Y', strtotime($match['match_date'])); ?></td>
                                    <td><strong><?php echo $match['team1'] . ' vs ' . $match['team2']; ?></strong></td>
                                    <td><?php echo $match['match_type']; ?></td>
                                    <td><?php echo $match['venue']; ?></td>
                                    <td><?php echo $time_remaining; ?></td>
                                    <td>
                                        <a href="match-details.php?id=<?php echo $match['id']; ?>" class="btn btn-sm btn-primary">Details</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">No Upcoming Matches</h4>
                    <p>There are currently no upcoming cricket matches scheduled. Please check back later.</p>
                    <hr>
                    <p class="mb-0">You can view live matches on the <a href="live-score.php" class="alert-link">live scores page</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Calendar View -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Match Calendar</h4>
                </div>
                <div class="card-body">
                    <?php
                    // Reset the result pointer
                    $conn->query($upcoming_matches_sql);
                    $calendar_matches = $conn->query($upcoming_matches_sql);
                    
                    if ($calendar_matches && $calendar_matches->num_rows > 0) {
                        // Group matches by month
                        $matches_by_month = [];
                        
                        while ($match = $calendar_matches->fetch_assoc()) {
                            $month = date('F Y', strtotime($match['match_date']));
                            if (!isset($matches_by_month[$month])) {
                                $matches_by_month[$month] = [];
                            }
                            $matches_by_month[$month][] = $match;
                        }
                        
                        // Display matches by month
                        foreach ($matches_by_month as $month => $matches) {
                            echo '<h5 class="mt-3 mb-3">' . $month . '</h5>';
                            echo '<div class="list-group mb-4">';
                            
                            foreach ($matches as $match) {
                                echo '<div class="list-group-item list-group-item-action">';
                                echo '<div class="d-flex w-100 justify-content-between">';
                                echo '<h5 class="mb-1">' . $match['team1'] . ' vs ' . $match['team2'] . '</h5>';
                                echo '<small>' . date('d M', strtotime($match['match_date'])) . '</small>';
                                echo '</div>';
                                echo '<p class="mb-1">' . $match['venue'] . '</p>';
                                echo '<small>' . $match['match_type'] . ' Match</small>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-center">No upcoming matches to display in the calendar.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
