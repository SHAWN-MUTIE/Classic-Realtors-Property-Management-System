<?php
session_start();
require_once("../config/db.php");

$success = $error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $conn->real_escape_string($_POST['phone']);
    $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check for an APPROVED request
    $check = $conn->query("
        SELECT sr.request_id, t.tenant_id 
        FROM service_requests sr 
        JOIN tenants t ON sr.tenant_id = t.tenant_id 
        WHERE t.phone = '$phone' 
        AND sr.request_type = 'Password Reset' 
        AND sr.status = 'Approved'
        ORDER BY sr.request_date DESC LIMIT 1
    ");
    
    if ($check->num_rows > 0) {
        $data = $check->fetch_assoc();
        $t_id = $data['tenant_id'];
        $req_id = $data['request_id'];
        
        $conn->begin_transaction();
        try {
            // Update Password
            $conn->query("UPDATE tenants SET password = '$new_pass' WHERE tenant_id = $t_id");
            // Mark as Resolved
            $conn->query("UPDATE service_requests SET status = 'Resolved' WHERE request_id = $req_id");
            // Audit Log
            $conn->query("INSERT INTO audit_logs (user_type, tenant_id, action) VALUES ('System', $t_id, 'Password updated via authorized reset.')");
            
            $conn->commit();
            $success = "Success! Password changed. <a href='login.php'>Login Now</a>";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "System error. Please try again later.";
        }
    } else {
        $error = "Reset NOT authorized. Please ask your Landlord to approve your request first.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Set New Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{ background: #f8f9fa; }</style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow border-0 p-3">
                    <div class="card-body">
                        <h4 class="fw-bold mb-4 text-center">New Password</h4>
                        
                        <?php if($success) echo "<div class='alert alert-success small'>$success</div>"; ?>
                        <?php if($error) echo "<div class='alert alert-danger small'>$error</div>"; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Confirm Phone Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="2547..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">New Password</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 fw-bold">Update Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>