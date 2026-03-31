<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['tenant_id'])) {
    header("Location: login.php");
    exit();
}
$tenant_id = $_SESSION['tenant_id'];

// --- 1. FETCH LEASE & FIXED COST DATA ---
$t_query = $conn->query("
    SELECT t.lease_start, t.lease_end, t.wifi_cost, t.garbage_fee, u.rent_amount 
    FROM tenants t
    JOIN units u ON t.unit_id = u.unit_id
    WHERE t.tenant_id = $tenant_id
");
$t_data = $t_query->fetch_assoc();

// --- 2. THE LEASE MATH ENGINE ---
$start_date = new DateTime($t_data['lease_start']);
$end_date = new DateTime($t_data['lease_end']);

// Calculate total months of the lease (rounding up for partial months)
$diff = $start_date->diff($end_date);
$lease_months = ($diff->y * 12) + $diff->m;
if ($diff->d > 0) { $lease_months++; }
if ($lease_months == 0) { $lease_months = 1; } // Failsafe for < 1 month leases

// Calculate Fixed Monthly Liability
$monthly_fixed = $t_data['rent_amount'] + $t_data['wifi_cost'] + $t_data['garbage_fee'];

// Calculate Total Expected Contract Value
$expected_total = $lease_months * $monthly_fixed;

// --- 3. FETCH TOTAL PAID SO FAR ---
$p_query = $conn->query("
    SELECT SUM(amount) as total 
    FROM payments 
    WHERE tenant_id = $tenant_id AND status IN ('Paid', 'success')
");
$total_paid = $p_query->fetch_assoc()['total'] ?? 0;

// Calculate remaining balance for the chart
$remaining_expected = $expected_total - $total_paid;
if ($remaining_expected < 0) { $remaining_expected = 0; } // In case they overpaid

// Calculate Completion Percentage
$completion_pct = ($expected_total > 0) ? round(($total_paid / $expected_total) * 100, 1) : 0;
if ($completion_pct > 100) { $completion_pct = 100; }

include "../config/header.php"; 
?>

<div class="container-fluid">
    <div class="row">

        <div class="col-2 bg-success text-white vh-100 p-3 position-fixed shadow">
            <h5 class="text-center mb-4 fw-bold tracking-wide border-bottom pb-3">TENANT PORTAL</h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">📊 My Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="payments.php">💳 Make Payment</a></li>
                <li class="nav-item"><a class="nav-link text-white active bg-dark" href="receipts.php">🧾 Receipts & History</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="requests.php">🛠 Service Requests</a></li>
                <li class="nav-item"><a class="nav-link text-white mt-4" href="logout.php">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="col-10 offset-2 p-4 bg-light min-vh-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark mb-0">🧾 Payment History & Receipts</h3>
                <span class="text-muted fw-bold">Track your lease fulfillment</span>
            </div>

            <div class="row mb-4">
                <div class="col-md-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white fw-bold">📈 Lease Fulfillment Progress</div>
                        <div class="card-body text-center">
                            <div style="position: relative; height: 200px; width: 200px; margin: 0 auto;">
                                <canvas id="leaseProgressChart"></canvas>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                    <h4 class="fw-bold text-success mb-0"><?= $completion_pct ?>%</h4>
                                    <small class="text-muted">Paid</small>
                                </div>
                            </div>
                            
                            <div class="row mt-4 text-start">
                                <div class="col-6 border-end">
                                    <small class="text-muted fw-bold text-uppercase d-block mb-1">Expected Total</small>
                                    <span class="fw-bold fs-5">KES <?= number_format($expected_total) ?></span>
                                    <small class="d-block text-muted mt-1">(<?= $lease_months ?> Months)</small>
                                </div>
                                <div class="col-6 ps-3">
                                    <small class="text-success fw-bold text-uppercase d-block mb-1">Total Paid</small>
                                    <span class="fw-bold fs-5 text-success">KES <?= number_format($total_paid) ?></span>
                                    <small class="d-block text-muted mt-1">Life of Lease</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                            <span>🗂️ Complete Payment Records</span>
                            <span class="badge bg-success"><?= $conn->query("SELECT COUNT(*) as c FROM payments WHERE tenant_id = $tenant_id AND status IN ('Paid', 'success')")->fetch_assoc()['c'] ?> Transactions</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light position-sticky top-0 shadow-sm">
                                        <tr>
                                            <th class="ps-4">Date</th>
                                            <th>Ref Code</th>
                                            <th>Amount (KES)</th>
                                            <th>Status</th>
                                            <th class="text-end pe-4">Receipt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $payments = $conn->query("
                                            SELECT payment_id, payment_date, amount, mpesa_receipt, status 
                                            FROM payments 
                                            WHERE tenant_id = $tenant_id 
                                            ORDER BY payment_date DESC
                                        ");
                                        
                                        if ($payments->num_rows > 0) {
                                            while($pay = $payments->fetch_assoc()) {
                                                $date = date('M d, Y', strtotime($pay['payment_date']));
                                                $time = date('h:i A', strtotime($pay['payment_date']));
                                                
                                                // Status formatting
                                                $s_badge = ($pay['status'] == 'Paid' || $pay['status'] == 'success') ? 'success' : 'warning text-dark';
                                                $ref_code = $pay['mpesa_receipt'] ? $pay['mpesa_receipt'] : 'PENDING';
                                                
                                                echo "<tr>
                                                        <td class='ps-4'>
                                                            <div class='fw-bold text-dark'>{$date}</div>
                                                            <small class='text-muted'>{$time}</small>
                                                        </td>
                                                        <td><span class='badge bg-light text-dark border'>{$ref_code}</span></td>
                                                        <td class='fw-bold'>". number_format($pay['amount'], 2) ."</td>
                                                        <td><span class='badge bg-{$s_badge}'>{$pay['status']}</span></td>
                                                        <td class='text-end pe-4'>";
                                                        
                                                // Only show download button if payment is successful
                                                if ($pay['status'] == 'Paid' || $pay['status'] == 'success') {
                                                    echo "<button class='btn btn-sm btn-outline-success fw-bold' onclick='window.print()'>
                                                            <i class='bi bi-download'></i> Print
                                                          </button>";
                                                } else {
                                                    echo "<span class='text-muted small'>N/A</span>";
                                                }
                                                
                                                echo "  </td>
                                                      </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='5' class='text-center text-muted py-5'>No payment records found yet.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('leaseProgressChart').getContext('2d');
    
    // PHP variables injected into JS
    const totalPaid = <?= $total_paid ?>;
    const remainingExpected = <?= $remaining_expected ?>;
    
    // If they haven't paid anything and expected is 0 (rare edge case), prevent blank chart
    const renderData = (totalPaid === 0 && remainingExpected === 0) ? [0, 1] : [totalPaid, remainingExpected];

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Total Paid', 'Remaining Lease Value'],
            datasets: [{
                data: renderData,
                backgroundColor: [
                    '#198754', // Bootstrap Success Green (Paid)
                    '#e9ecef'  // Light Gray (Remaining)
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%', // Makes it a thin, modern ring
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) { label += ': KES '; }
                            label += context.raw.toLocaleString();
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include "../config/footer.php"; ?>