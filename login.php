<?php
// Set no navbar for login page
$no_navbar = true;
require_once 'config/config.php';

// Check if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = "Email and password are required";
    } else {
        // Check user credentials
        $sql = "SELECT id, email FROM admins WHERE email = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            
            // Redirect to dashboard
            redirect(SITE_URL . '/admin/dashboard.php');
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
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
                </ul>
                <div class="d-flex align-items-center justify-content-between">
                    <a href="<?php echo SITE_URL; ?>/player/login.php" class="btn btn-outline-primary me-2">Player Login</a>
                    <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-primary">Admin Login</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="login-container">
            <h2>Admin Login</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group mb-3">
                    <label for="email">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                </div>
                <div class="form-group mb-3">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
