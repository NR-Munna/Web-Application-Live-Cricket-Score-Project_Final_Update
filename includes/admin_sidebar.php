<?php
// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <h4 class="text-white">Cricket Admin</h4>
            <?php if(isset($_SESSION['admin_email'])): ?>
            <small class="text-white"><?php echo $_SESSION['admin_email']; ?></small>
            <?php endif; ?>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'player-management.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/player-management.php">
                    <i class="bi bi-people"></i> Player Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'match-management.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/match-management.php">
                    <i class="bi bi-trophy"></i> Match Management
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>
