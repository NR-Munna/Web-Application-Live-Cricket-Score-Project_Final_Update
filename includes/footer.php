    </div> <!-- End container-fluid -->
    
    </div>

<!-- Footer -->
<footer class="mt-5 py-4 bg-dark text-white">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3">
                <h5>Live Cricket Score</h5>
                <p class="text-muted">Your one-stop solution for live cricket scores, player statistics, and match details.</p>
            </div>
            <div class="col-md-2 mb-3">
                <h5>Quick Links</h5>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="<?php echo SITE_URL; ?>/index.php" class="nav-link p-0 text-muted">Home</a></li>
                    <li class="nav-item mb-2"><a href="<?php echo SITE_URL; ?>/live-score.php" class="nav-link p-0 text-muted">Live Scores</a></li>
                    <li class="nav-item mb-2"><a href="<?php echo SITE_URL; ?>/schedules.php" class="nav-link p-0 text-muted">Schedules</a></li>
                    <li class="nav-item mb-2"><a href="<?php echo SITE_URL; ?>/players.php" class="nav-link p-0 text-muted">Players</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-3">
                <h5>Admin</h5>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="<?php echo SITE_URL; ?>/login.php" class="nav-link p-0 text-muted">Admin Login</a></li>
                    <li class="nav-item mb-2"><a href="<?php echo SITE_URL; ?>/admin/player-management.php" class="nav-link p-0 text-muted">Player Management</a></li>
                    <li class="nav-item mb-2"><a href="<?php echo SITE_URL; ?>/admin/match-management.php" class="nav-link p-0 text-muted">Match Management</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-3">
                <h5>Connect With Us</h5>
                <ul class="list-unstyled d-flex">
                    <li class="ms-3"><a class="text-muted" href="#"><i class="bi bi-twitter fs-5"></i></a></li>
                    <li class="ms-3"><a class="text-muted" href="#"><i class="bi bi-instagram fs-5"></i></a></li>
                    <li class="ms-3"><a class="text-muted" href="#"><i class="bi bi-facebook fs-5"></i></a></li>
                </ul>
            </div>
        </div>
        <div class="d-flex justify-content-between py-4 my-4 border-top">
            <p>&copy; <?php echo date('Y'); ?> Live Cricket Score. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
