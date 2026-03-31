<?php
session_start();

if (!isset($_SESSION['landlord_id'])) {
    header("Location: login.php");
    exit();
}

require_once("../config/db.php");
$landlord_id = $_SESSION['landlord_id'];

// --- FETCH DASHBOARD METRICS ---
$prop_query = $conn->query("SELECT COUNT(*) as total FROM properties WHERE landlord_id = $landlord_id");
$total_properties = $prop_query->fetch_assoc()['total'];

$tenant_query = $conn->query("
    SELECT COUNT(*) as total FROM tenants t 
    JOIN units u ON t.unit_id = u.unit_id
    JOIN properties p ON u.property_id = p.property_id 
    WHERE p.landlord_id = $landlord_id AND t.status = 'active'
");
$total_tenants = $tenant_query->fetch_assoc()['total'];

$req_query = $conn->query("
    SELECT COUNT(*) as total FROM service_requests sr
    JOIN tenants t ON sr.tenant_id = t.tenant_id
    JOIN units u ON t.unit_id = u.unit_id
    JOIN properties p ON u.property_id = p.property_id
    WHERE p.landlord_id = $landlord_id AND sr.status = 'pending'
");
$pending_requests = $req_query->fetch_assoc()['total'];

$income_query = $conn->query("
    SELECT SUM(amount) as total FROM payments p
    JOIN tenants t ON p.tenant_id = t.tenant_id
    JOIN units u ON t.unit_id = u.unit_id
    JOIN properties prop ON u.property_id = prop.property_id
    WHERE prop.landlord_id = $landlord_id AND p.status IN ('Paid', 'success')
");
$total_income = $income_query->fetch_assoc()['total'] ?? 0;

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$chart_data_array = array_fill(1, 12, 0); 
$monthly_query = $conn->query("
    SELECT MONTH(payment_date) as m, SUM(amount) as tot 
    FROM payments p 
    JOIN tenants t ON p.tenant_id = t.tenant_id 
    JOIN units u ON t.unit_id = u.unit_id
    JOIN properties prop ON u.property_id = prop.property_id 
    WHERE prop.landlord_id = $landlord_id AND YEAR(payment_date) = $year AND p.status IN ('Paid', 'success') 
    GROUP BY MONTH(payment_date)
");
while($row = $monthly_query->fetch_assoc()) {
    $chart_data_array[$row['m']] = $row['tot'];
}
$chart_values = implode(",", $chart_data_array);

$current_month = date('m');
$current_month_income = $chart_data_array[(int)$current_month];
$estimated_tax = $current_month_income * 0.075; 

$today = new DateTime();
$tax_deadline = new DateTime(date('Y-m-20')); 
if ($today > $tax_deadline) {
    $tax_deadline->modify('+1 month');
}
$days_to_tax = $today->diff($tax_deadline)->days;

include "../config/header.php"; 
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 bg-dark text-white vh-100 p-3 position-fixed">
            <h5 class="text-center mb-4 fw-bold text-success">LANDLORD HUB</h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link active bg-success text-white" href="dashboard.php">📊 Dashboard</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="properties.php">🏢 Properties</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="tenants.php">👥 Tenants</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="payments.php">💰 Financials</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="requests.php">🛠 Requests</a>
                </li>
                <li class="nav-item mt-5">
                    <a class="nav-link text-danger fw-bold" href="logout.php">🚪 Logout</a>
                </li>
            </ul>
        </div>

        <div class="col-md-10 offset-md-2 p-4 bg-light min-vh-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'Landlord') ?> 👋</h3>
                <span class="text-muted fw-bold"><?= date('l, F j, Y') ?></span>
            </div>

            <?php if($pending_requests > 0): ?>
            <div class="alert alert-warning border-start border-warning border-4 shadow-sm" role="alert">
                <strong>🚨 Action Required:</strong> You have <b><?= $pending_requests ?></b> pending request(s). 
                <a href="requests.php" class="alert-link">Review now</a>.
            </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 border-start border-primary border-4 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Properties</div>
                            <div class="h3 mb-0 fw-bold"><?= $total_properties ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 border-start border-success border-4 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Tenants</div>
                            <div class="h3 mb-0 fw-bold"><?= $total_tenants ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 border-start border-info border-4 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Total Income</div>
                            <div class="h4 mb-0 fw-bold">KES <?= number_format($total_income) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 border-start border-danger border-4 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">Pending</div>
                            <div class="h3 mb-0 fw-bold text-danger"><?= $pending_requests ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                            <span>📈 Monthly Analytics (<?= $year ?>)</span>
                            <form method="GET" class="d-inline">
                                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="2026" <?= $year == 2026 ? 'selected' : '' ?>>2026</option>
                                    <option value="2025" <?= $year == 2025 ? 'selected' : '' ?>>2025</option>
                                </select>
                            </form>
                        </div>
                        <div class="card-body"><canvas id="incomeChart" height="110"></canvas></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100 bg-dark text-white text-center">
                        <div class="card-header bg-dark border-secondary fw-bold text-warning">🧾 KRA MRI Tax</div>
                        <div class="card-body d-flex flex-column justify-content-center">
                            <h5 class="text-muted mb-3 small">Due in <span class="text-danger fw-bold"><?= $days_to_tax ?> days</span></h5>
                            <p class="mb-0 small">Est. Payable:</p>
                            <h3 class="text-success fw-bold">KES <?= number_format($estimated_tax) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold text-danger">⚠️ Arrears Watchlist</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 small">
                        <thead><tr><th>Tenant</th><th>Unit</th><th>Arrears</th><th>Risk</th></tr></thead>
                        <tbody>
                            <?php
                            $arrears = $conn->query("SELECT t.tenant_name, u.unit_number, t.balance FROM tenants t JOIN units u ON t.unit_id = u.unit_id JOIN properties p ON u.property_id = p.property_id WHERE p.landlord_id = $landlord_id AND t.balance > 0 ORDER BY t.balance DESC LIMIT 5");
                            while($arr = $arrears->fetch_assoc()) {
                                echo "<tr><td>{$arr['tenant_name']}</td><td>{$arr['unit_number']}</td><td class='text-danger fw-bold'>KES ".number_format($arr['balance'])."</td><td><span class='badge bg-warning text-dark'>Watchlist</span></td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('incomeChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Collection',
                data: [<?= $chart_values ?>],
                backgroundColor: 'rgba(25, 135, 84, 0.6)',
                borderColor: '#198754',
                borderWidth: 1
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
</script>

<?php include "../config/footer.php"; ?>