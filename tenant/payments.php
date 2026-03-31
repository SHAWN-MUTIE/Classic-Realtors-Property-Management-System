<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['tenant_id'])) {
    header("Location: login.php");
    exit();
}
$tenant_id = $_SESSION['tenant_id'];

// --- 🛑 PRG PATTERN: GRAB FLASH MESSAGES ---
$success_msg = $error_msg = null;
if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']); 
}
if (isset($_SESSION['error_msg'])) {
    $error_msg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// Fetch Tenant Balance & Phone
$t_query = $conn->query("SELECT balance, phone, tenant_name FROM tenants WHERE tenant_id = $tenant_id");
$t_data = $t_query->fetch_assoc();
$current_balance = $t_data['balance'];
$registered_phone = $t_data['phone'];

// --- HANDLE FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $amount = (float)$_POST['amount'];
    
    // NOTE: STK Push is now handled entirely by ../mpesa/pay.php

    // 2. Handle Manual Reference Code Submission
    if (isset($_POST['submit_reference'])) {
        $ref_code = strtoupper($conn->real_escape_string($_POST['reference_code']));
        $payment_method = $conn->real_escape_string($_POST['payment_method']);
        
        // Check if reference already exists to prevent double-billing
        $check = $conn->query("SELECT payment_id FROM payments WHERE mpesa_receipt = '$ref_code'");
        if ($check->num_rows > 0) {
            $_SESSION['error_msg'] = "Error: This transaction reference ($ref_code) has already been submitted!";
        } else {
            // Insert as pending for landlord verification
            $stmt = $conn->prepare("INSERT INTO payments (tenant_id, amount, status, mpesa_receipt) VALUES (?, ?, 'pending_verification', ?)");
            $stmt->bind_param("ids", $tenant_id, $amount, $ref_code);
            
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Reference code $ref_code submitted successfully! It is pending landlord/bank validation.";
                $conn->query("INSERT INTO audit_logs (user_type, tenant_id, action) VALUES ('Tenant', $tenant_id, 'Submitted manual payment reference: $ref_code')");
            } else {
                $_SESSION['error_msg'] = "Failed to submit reference. Try again.";
            }
        }
        header("Location: payments.php");
        exit();
    }
}

include "../config/header.php"; 
?>

<div class="container-fluid">
    <div class="row">

        <div class="col-2 bg-success text-white vh-100 p-3 position-fixed shadow">
            <h5 class="text-center mb-4 fw-bold tracking-wide border-bottom pb-3">TENANT PORTAL</h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">📊 My Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white active bg-dark" href="payments.php">💳 Make Payment</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="receipts.php">🧾 Receipts & History</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="requests.php">🛠 Service Requests</a></li>
                <li class="nav-item"><a class="nav-link text-white mt-4" href="logout.php">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="col-10 offset-2 p-4 bg-light min-vh-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark mb-0">💳 Payment Center</h3>
                <span class="text-muted fw-bold">Clear your arrears securely</span>
            </div>

            <?php if($success_msg): ?>
                <div class="alert alert-success border-start border-success border-4 shadow-sm">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= $success_msg ?>
                </div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div class="alert alert-danger border-start border-danger border-4 shadow-sm">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-7">
                    
                    <div class="card bg-dark text-white shadow-sm border-0 mb-4 rounded-4 overflow-hidden">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted fw-bold mb-1">Total Amount Due</h6>
                                <h2 class="fw-bold text-warning mb-0">KES <?= number_format($current_balance, 2) ?></h2>
                            </div>
                            <i class="bi bi-wallet2 display-4 text-secondary opacity-50"></i>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white pt-3 pb-0 border-bottom-0">
                            <ul class="nav nav-tabs fw-bold" id="paymentTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active text-success" id="stk-tab" data-bs-toggle="tab" data-bs-target="#stk" type="button" role="tab">📱 M-Pesa Express</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link text-secondary" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">🏦 Submit Reference Code</button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body p-4">
                            <div class="tab-content" id="paymentTabsContent">
                                
                                <div class="tab-pane fade show active" id="stk" role="tabpanel">
                                    <div class="text-center mb-4">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/M-PESA_LOGO-01.svg/512px-M-PESA_LOGO-01.svg.png" alt="M-Pesa" height="50">
                                        <p class="text-muted mt-2 small">An STK prompt will appear on your phone automatically.</p>
                                    </div>
                                    <form method="POST" action="../mpesa/pay.php" id="stkForm">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Amount to Pay (KES)</label>
                                            <input type="number" name="amount" class="form-control form-control-lg fw-bold text-success" value="<?= ($current_balance > 0) ? $current_balance : '' ?>" required min="10">
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">M-Pesa Number</label>
                                            <input type="text" name="phone" class="form-control form-control-lg bg-light" value="<?= htmlspecialchars($registered_phone) ?>" required>
                                            <small class="text-muted">You can change this number if you are paying from another phone right now.</small>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" id="stkBtn">
                                            Pay via M-Pesa
                                        </button>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="manual" role="tabpanel">
                                    <div class="alert alert-info border-0 bg-light text-dark small mb-4">
                                        <i class="bi bi-info-circle-fill text-primary"></i> 
                                        If you paid via Bank Transfer or from an unregistered number, enter the transaction code here so we can validate it against our bank records.
                                    </div>
                                    <form method="POST" action="payments.php">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Payment Method</label>
                                                <select name="payment_method" class="form-select" required>
                                                    <option value="M-Pesa">M-Pesa (Send Money / Paybill)</option>
                                                    <option value="Bank Transfer">Direct Bank Transfer</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Amount Paid</label>
                                                <input type="number" name="amount" class="form-control fw-bold text-primary" required min="10">
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Transaction Reference Code</label>
                                            <input type="text" name="reference_code" class="form-control form-control-lg text-uppercase" placeholder="e.g., SAQ1234567" required>
                                        </div>
                                        <button type="submit" name="submit_reference" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                                            Submit for Verification
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white fw-bold">Recent Processing Activity</div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php
                                $history = $conn->query("SELECT amount, status, mpesa_receipt, payment_date FROM payments WHERE tenant_id = $tenant_id ORDER BY payment_date DESC LIMIT 5");
                                if ($history->num_rows > 0) {
                                    while($row = $history->fetch_assoc()) {
                                        $date = date('M d, g:i A', strtotime($row['payment_date']));
                                        
                                        $badge = 'warning text-dark';
                                        if ($row['status'] == 'Paid' || $row['status'] == 'success') $badge = 'success';
                                        elseif ($row['status'] == 'pending_verification') $badge = 'primary';
                                        
                                        $ref = $row['mpesa_receipt'] ? $row['mpesa_receipt'] : 'STK Push Pending';
                                        
                                        echo "<li class='list-group-item px-4 py-3'>
                                                <div class='d-flex justify-content-between align-items-center mb-1'>
                                                    <span class='fw-bold fs-6'>KES " . number_format($row['amount']) . "</span>
                                                    <span class='badge bg-{$badge}'>{$row['status']}</span>
                                                </div>
                                                <div class='d-flex justify-content-between align-items-center'>
                                                    <small class='text-muted'>{$date}</small>
                                                    <small class='fw-bold text-secondary'>{$ref}</small>
                                                </div>
                                              </li>";
                                    }
                                } else {
                                    echo "<li class='list-group-item text-center text-muted py-5'>No recent activity.</li>";
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('stkForm').addEventListener('submit', function() {
    let btn = document.getElementById('stkBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Waiting for PIN...';
    btn.classList.add('disabled');
});
</script>

<?php include "../config/footer.php"; ?>