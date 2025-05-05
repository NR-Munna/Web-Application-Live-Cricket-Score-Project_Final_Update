<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/custom.css">
</head>
<body>
<?php if(!isset($no_navbar) || !$no_navbar): ?>
    <!-- Navbar -->
    <nav class="navbar navbar-expand" aria-label="Seventh navbar example">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="<?php echo SITE_URL; ?>/index.php">Live Cricket Score</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExampleXxl"
                aria-controls="navbarsExampleXxl" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarsExampleXxl">
                <ul class="navbar-nav me-auto mb-2 mb-xl-0">
                    <li class="nav-item">
                        <a class="nav-link active text-white" aria-current="page" href="<?php echo SITE_URL; ?>/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/live-score.php">Live Score</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/schedules.php">Schedules</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/players.php">Players</a>
                    </li> -->
                </ul>
                <div class="d-flex align-items-center justify-content-between">
                    <?php if(isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline-primary me-2">Dashboard</a>
                        <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline-danger">Logout</a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-primary">Admin Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>
<div class="container-fluid">
