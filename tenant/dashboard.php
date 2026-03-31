<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['tenant_id'])) {
    header("Location: login.php");
    exit();
}

$tenant_id = $_SESSION['tenant_id'];

// Fetch live data
$query = $conn->query("
    SELECT t.*, u.unit_number, u.rent_amount, p.property_name 
    FROM tenants t
    JOIN units u ON t.unit_id = u.unit_id
    JOIN properties p ON u.property_id = p.property_id
    WHERE t.tenant_id = $tenant_id
");
$tenant_data = $query->fetch_assoc();

// Ensure phone is in session for the STK script
$_SESSION['phone'] = $tenant_data['phone'];

$fixed_total = $tenant_data['rent_amount'] + $tenant_data['wifi_cost'] + $tenant_data['garbage_fee'];

include "../config/header.php"; 
?>

<div class="container-fluid">
    <div class="row">

        <div class="col-2 bg-success text-white vh-100 p-3 position-fixed shadow">
            <h5 class="text-center mb-4 fw-bold tracking-wide border-bottom pb-3">TENANT PORTAL</h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item"><a class="nav-link text-white active bg-dark" href="dashboard.php">📊 My Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="payments.php">💳 Make Payment</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="receipts.php">🧾 Receipts & History</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="requests.php">🛠 Service Requests</a></li>
                <li class="nav-item"><a class="nav-link text-white mt-4" href="logout.php">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="col-10 offset-2 p-4 bg-light min-vh-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-0">Welcome, <?= htmlspecialchars($tenant_data['tenant_name']) ?></h3>
                    <p class="text-muted mb-0"><?= $tenant_data['property_name'] ?> — Unit <?= $tenant_data['unit_number'] ?></p>
                </div>
                <span class="text-muted fw-bold bg-white px-3 py-2 rounded shadow-sm"><?= date('l, F j, Y') ?></span>
            </div>

            <div class="row">
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 mb-4 <?php echo ($tenant_data['balance'] > 0) ? 'border-start border-danger border-4' : 'border-start border-success border-4'; ?>">
                        <div class="card-body p-4 text-center">
                            <h6 class="text-uppercase text-muted fw-bold mb-2">Total Outstanding Balance</h6>
                            <h1 class="fw-bold <?php echo ($tenant_data['balance'] > 0) ? 'text-danger' : 'text-success'; ?> mb-3">
                                KES <?= number_format($tenant_data['balance'], 2) ?>
                            </h1>
                            
                            <div class="text-start mt-3">
                                <h6 class="fw-bold text-muted small border-bottom pb-2">Recent Account Adjustments</h6>
                                <div style="max-height: 150px; overflow-y: auto;">
                                    <?php
                                    $penalties = $conn->query("
                                        SELECT action, action_date 
                                        FROM audit_logs 
                                        WHERE tenant_id = $tenant_id 
                                        AND (action LIKE '%late fee%' OR action LIKE '%penalty%')
                                        ORDER BY action_date DESC LIMIT 3
                                    ");
                                    if ($penalties->num_rows > 0) {
                                        while($p = $penalties->fetch_assoc()) {
                                            $p_date = date('M d', strtotime($p['action_date']));
                                            echo "<div class='d-flex justify-content-between align-items-start mb-2'>
                                                    <small class='text-danger fw-bold'>⚠ {$p_date}</small>
                                                    <small class='text-muted' style='font-size: 0.75rem; text-align: right;'>{$p['action']}</small>
                                                  </div>";
                                        }
                                    } else {
                                        echo "<p class='text-muted small text-center my-3'>No penalties applied recently.</p>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 rounded-3 mb-4">
                        <div class="card-header bg-dark text-white fw-bold py-3">
                            💳 M-Pesa Quick Pay
                        </div>
                        <div class="card-body" id="payment-area">
                            <p class="text-muted small mb-3">Enter amount to pay via STK Push to <b><?= $tenant_data['phone'] ?></b></p>
                            
                            <form action="../mpesa/pay.php" method="POST">
                                <div class="input-group mb-3">
                                    <span class="input-group-text bg-white fw-bold">KES</span>
                                    <input type="number" name="amount" class="form-control form-control-lg fw-bold" placeholder="Amount" value="<?= (int)$tenant_data['balance'] ?>" required min="1">
                                    <button type="submit" class="btn btn-success fw-bold px-4">PAY NOW</button>
                                </div>
                            </form>
                            
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 bg-dark text-white mb-4">
                        <div class="card-body p-4 text-center">
                            <h6 class="text-uppercase text-muted fw-bold mb-3">Lease Expiry</h6>
                            <h4 class="fw-bold text-warning mb-0"><?= date('F j, Y', strtotime($tenant_data['lease_end'])) ?></h4>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white pt-4 pb-0">
                            <h5 class="fw-bold text-success">📋 Monthly Billing Structure</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>🏠 Rent Amount</span>
                                    <span class="fw-bold">KES <?= number_format($tenant_data['rent_amount'], 2) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>📶 Internet (<?= $tenant_data['wifi_plan'] ?>)</span>
                                    <span>KES <?= number_format($tenant_data['wifi_cost'], 2) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>🗑️ Garbage Collection</span>
                                    <span>KES <?= number_format($tenant_data['garbage_fee'], 2) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-light mt-2 fw-bold">
                                    <span class="fs-5">Standard Monthly Bill</span>
                                    <span class="text-success fs-5">KES <?= number_format($fixed_total, 2) ?></span>
                                </li>
                            </ul>
                            <div class="alert alert-info py-2 mt-3 mb-0" style="font-size: 0.85rem;">
                                <strong>Water Meter Status:</strong> Your last reading was <b><?= $tenant_data['water_meter_reading'] ?> Units</b>. Water is billed at KES 150/unit upon landlord inspection.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include "../config/footer.php"; ?>