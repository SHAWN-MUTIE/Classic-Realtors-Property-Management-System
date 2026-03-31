<?php
session_start();
require_once("../config/db.php");

$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password'];

    $res = $conn->query("SELECT * FROM tenants WHERE phone = '$phone' AND status = 'active'");
    
    if ($res->num_rows > 0) {
        $tenant = $res->fetch_assoc();
        if (password_verify($password, $tenant['password'])) {
            $_SESSION['tenant_id'] = $tenant['tenant_id'];
            $_SESSION['tenant_name'] = $tenant['tenant_name'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Account not found or deactivated.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tenant Login | CRPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; height: 100vh; display: flex; align-items: center; }
        .login-card { border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card login-card p-4">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-success">Tenant Portal</h3>
                        <p class="text-muted small">Access your bills and requests</p>
                    </div>

                    <?php if($error) echo "<div class='alert alert-danger small'>$error</div>"; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="2547..." required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm">Login</button>
                    </form>

                    <div class="mt-4 text-center">
                        <p class="mb-1 small text-muted">Trouble logging in?</p>
                        <div class="d-flex justify-content-center align-items-center">
                            <a href="forgot_password.php" class="text-decoration-none small text-secondary px-2">Request Reset</a>
                            <span class="text-muted">|</span>
                            <a href="reset_password.php" class="text-decoration-none small text-primary px-2">Set New Password</a>
                        </div>
                    </div>

                </div>
            </div>
            <p class="text-center mt-4 small text-muted">&copy; 2026 Classic Realtors Property Management System</p>
        </div>
    </div>
</div>

</body>
</html>