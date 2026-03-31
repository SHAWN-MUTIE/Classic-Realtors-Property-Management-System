<?php
session_start();
require_once("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $conn->real_escape_string($_POST['phone']);
    
    // Check if tenant exists
    $res = $conn->query("SELECT tenant_id FROM tenants WHERE phone = '$phone' AND status = 'active'");
    
    if ($res->num_rows > 0) {
        $tenant = $res->fetch_assoc();
        $t_id = $tenant['tenant_id'];
        
        // Create a special Service Request for Password Reset
        $msg = "I have forgotten my password. Please authorize a reset.";
        $stmt = $conn->prepare("INSERT INTO service_requests (tenant_id, request_type, message, status) VALUES (?, 'Password Reset', ?, 'pending')");
        $stmt->bind_param("is", $t_id, $msg);
        
        if ($stmt->execute()) {
            $success = "Request sent! Please contact your landlord to approve the reset.";
        }
    } else {
        $error = "Phone number not found or account inactive.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow border-0">
                    <div class="card-body p-4">
                        <h4 class="fw-bold text-center mb-4">Reset Access</h4>
                        <?php if(isset($success)) echo "<div class='alert alert-success small'>$success</div>"; ?>
                        <?php if(isset($error)) echo "<div class='alert alert-danger small'>$error</div>"; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Enter Registered Phone Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="2547..." required>
                            </div>
                            <button type="submit" class="btn btn-success w-100 fw-bold">Send Reset Request</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="login.php" class="text-decoration-none small">Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>