<?php
require_once 'config/config.php';

// Get all players
$players_sql = "SELECT * FROM players ORDER BY name ASC";
$players_result = $conn->query($players_sql);

// Get player stats
function getPlayerStats($conn, $player_id) {
    // Batting stats
    $batting_sql = "SELECT 
        COUNT(DISTINCT match_id) as matches_played,
        SUM(runs) as total_runs,
        ROUND(SUM(runs) / COUNT(DISTINCT match_id), 2) as average,
        MAX(runs) as highest_score,
        SUM(CASE WHEN runs >= 50 AND runs < 100 THEN 1 ELSE 0 END) as fifties,
        SUM(CASE WHEN runs >= 100 THEN 1 ELSE 0 END) as hundreds
        FROM match_scores 
        WHERE batsman_id = ?";
    
    $stmt = $conn->prepare($batting_sql);
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    $batting_result = $stmt->get_result();
    $batting_stats = $batting_result->fetch_assoc();
    
    // Bowling stats
    $bowling_sql = "SELECT 
        COUNT(DISTINCT match_id) as matches_bowled,
        SUM(wicket) as total_wickets,
        ROUND(SUM(runs) / NULLIF(SUM(wicket), 0), 2) as bowling_average,
        SUM(runs) as runs_conceded,
        COUNT(*) as balls_bowled
        FROM match_scores 
        WHERE bowler_id = ?";
    
    $stmt = $conn->prepare($bowling_sql);
    $stmt->bind_param("i", $player_id);
    $stmt->execute();
    $bowling_result = $stmt->get_result();
    $bowling_stats = $bowling_result->fetch_assoc();
    
    // Calculate economy rate (runs per over)
    $economy = 0;
    if ($bowling_stats['balls_bowled'] > 0) {
        $overs = $bowling_stats['balls_bowled'] / 6;
        $economy = round($bowling_stats['runs_conceded'] / $overs, 2);
    }
    
    $bowling_stats['economy'] = $economy;
    
    return [
        'batting' => $batting_stats,
        'bowling' => $bowling_stats
    ];
}

// Page title
$page_title = "Players - Cricket Score";
?>

<?php include_once 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Cricket Players</h1>
            
            <!-- Search and filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-4">
                            <label for="searchName" class="form-label">Search by Name</label>
                            <input type="text" class="form-control" id="searchName" placeholder="Enter player name">
                        </div>
                        <div class="col-md-3">
                            <label for="filterCountry" class="form-label">Filter by Country</label>
                            <select class="form-select" id="filterCountry">
                                <option value="">All Countries</option>
                                <?php
                                // Get unique countries
                                $countries_sql = "SELECT DISTINCT country FROM players ORDER BY country";
                                $countries_result = $conn->query($countries_sql);
                                while ($country = $countries_result->fetch_assoc()) {
                                    echo '<option value="' . $country['country'] . '">' . $country['country'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filterRole" class="form-label">Filter by Role</label>
                            <select class="form-select" id="filterRole">
                                <option value="">All Roles</option>
                                <?php
                                // Get unique roles
                                $roles_sql = "SELECT DISTINCT role FROM players ORDER BY role";
                                $roles_result = $conn->query($roles_sql);
                                while ($role = $roles_result->fetch_assoc()) {
                                    echo '<option value="' . $role['role'] . '">' . $role['role'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" id="resetFilters" class="btn btn-secondary w-100">Reset Filters</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($players_result && $players_result->num_rows > 0): ?>
                <div class="row" id="playersContainer">
                    <?php while ($player = $players_result->fetch_assoc()): 
                        $stats = getPlayerStats($conn, $player['id']);
                    ?>
                        <div class="col-md-6 col-lg-4 mb-4 player-card" 
                             data-name="<?php echo strtolower($player['name']); ?>"
                             data-country="<?php echo $player['country']; ?>"
                             data-role="<?php echo $player['role']; ?>">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><?php echo $player['name']; ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="player-info mb-3">
                                        <p class="mb-1"><strong>Country:</strong> <?php echo $player['country']; ?></p>
                                        <p><strong>Role:</strong> <?php echo $player['role']; ?></p>
                                    </div>
                                    
                                    <div class="player-stats">
                                        <h6>Batting Stats</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Matches</th>
                                                        <th>Runs</th>
                                                        <th>Avg</th>
                                                        <th>HS</th>
                                                        <th>50s</th>
                                                        <th>100s</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?php echo $stats['batting']['matches_played'] ?? 0; ?></td>
                                                        <td><?php echo $stats['batting']['total_runs'] ?? 0; ?></td>
                                                        <td><?php echo $stats['batting']['average'] ?? 0; ?></td>
                                                        <td><?php echo $stats['batting']['highest_score'] ?? 0; ?></td>
                                                        <td><?php echo $stats['batting']['fifties'] ?? 0; ?></td>
                                                        <td><?php echo $stats['batting']['hundreds'] ?? 0; ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <h6 class="mt-3">Bowling Stats</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Matches</th>
                                                        <th>Wickets</th>
                                                        <th>Avg</th>
                                                        <th>Econ</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?php echo $stats['bowling']['matches_bowled'] ?? 0; ?></td>
                                                        <td><?php echo $stats['bowling']['total_wickets'] ?? 0; ?></td>
                                                        <td><?php echo $stats['bowling']['bowling_average'] ?? 0; ?></td>
                                                        <td><?php echo $stats['bowling']['economy'] ?? 0; ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a href="player-profile.php?id=<?php echo $player['id']; ?>" class="btn btn-sm btn-primary">View Full Profile</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div id="noResults" class="alert alert-info d-none">
                    <h4 class="alert-heading">No Players Found</h4>
                    <p>No players match your current filter criteria. Please try different filters.</p>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">No Players Found</h4>
                    <p>There are currently no players in the database.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchName = document.getElementById('searchName');
        const filterCountry = document.getElementById('filterCountry');
        const filterRole = document.getElementById('filterRole');
        const resetFilters = document.getElementById('resetFilters');
        const playersContainer = document.getElementById('playersContainer');
        const playerCards = document.querySelectorAll('.player-card');
        const noResults = document.getElementById('noResults');
        
        // Filter function
        function applyFilters() {
            const nameFilter = searchName.value.toLowerCase();
            const countryFilter = filterCountry.value;
            const roleFilter = filterRole.value;
            
            let visibleCount = 0;
            
            playerCards.forEach(card => {
                const name = card.dataset.name;
                const country = card.dataset.country;
                const role = card.dataset.role;
                
                const nameMatch = name.includes(nameFilter);
                const countryMatch = countryFilter === '' || country === countryFilter;
                const roleMatch = roleFilter === '' || role === roleFilter;
                
                if (nameMatch && countryMatch && roleMatch) {
                    card.classList.remove('d-none');
                    visibleCount++;
                } else {
                    card.classList.add('d-none');
                }
            });
            
            // Show/hide no results message
            if (visibleCount === 0) {
                noResults.classList.remove('d-none');
            } else {
                noResults.classList.add('d-none');
            }
        }
        
        // Event listeners
        searchName.addEventListener('input', applyFilters);
        filterCountry.addEventListener('change', applyFilters);
        filterRole.addEventListener('change', applyFilters);
        
        // Reset filters
        resetFilters.addEventListener('click', function() {
            searchName.value = '';
            filterCountry.value = '';
            filterRole.value = '';
            applyFilters();
        });
    });
</script>

<?php include_once 'includes/footer.php'; ?>
