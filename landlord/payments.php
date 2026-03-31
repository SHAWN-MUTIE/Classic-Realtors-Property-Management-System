<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['landlord_id'])) {
    header("Location: login.php");
    exit();
}
$landlord_id = $_SESSION['landlord_id'];

// --- HANDLE ADDING AN EXPENSE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expense'])) {
    $prop_id = (int)$_POST['property_id'];
    $type = $conn->real_escape_string($_POST['expense_type']);
    $amount = (float)$_POST['amount'];
    $date = $conn->real_escape_string($_POST['expense_date']);
    $desc = $conn->real_escape_string($_POST['description']);

    // Verify property belongs to landlord
    $check = $conn->query("SELECT property_id FROM properties WHERE property_id = $prop_id AND landlord_id = $landlord_id");
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO expenses (property_id, expense_type, amount, expense_date, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $prop_id, $type, $amount, $date, $desc);
        if ($stmt->execute()) {
            $success_msg = "Expense logged successfully!";
        } else {
            $error_msg = "Failed to log expense.";
        }
    }
}

// --- FETCH FINANCIAL METRICS (CURRENT MONTH) ---
$current_month = date('m');
$current_year = date('Y');

// 1. Total Rent This Month
$rent_query = $conn->query("
    SELECT SUM(amount) as total FROM payments p
    JOIN tenants t ON p.tenant_id = t.tenant_id
    JOIN units u ON t.unit_id = u.unit_id
    JOIN properties prop ON u.property_id = prop.property_id
    WHERE prop.landlord_id = $landlord_id AND p.status IN ('Paid', 'success')
    AND MONTH(p.payment_date) = $current_month AND YEAR(p.payment_date) = $current_year
");
$monthly_rent = $rent_query->fetch_assoc()['total'] ?? 0;

// 2. Total Expenses This Month
$exp_query = $conn->query("
    SELECT SUM(amount) as total FROM expenses e
    JOIN properties p ON e.property_id = p.property_id
    WHERE p.landlord_id = $landlord_id 
    AND MONTH(e.expense_date) = $current_month AND YEAR(e.expense_date) = $current_year
");
$monthly_expenses = $exp_query->fetch_assoc()['total'] ?? 0;

// 3. Net Profit (NOI)
$net_profit = $monthly_rent - $monthly_expenses;

// --- CHART DATA (YEARLY INCOME VS EXPENSES) ---
$chart_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$income_data = array_fill(1, 12, 0);
$expense_data = array_fill(1, 12, 0);

// Fetch Income Data
$inc_q = $conn->query("
    SELECT MONTH(payment_date) as m, SUM(amount) as tot 
    FROM payments p 
    JOIN tenants t ON p.tenant_id = t.tenant_id 
    JOIN units u ON t.unit_id = u.unit_id
    JOIN properties prop ON u.property_id = prop.property_id 
    WHERE prop.landlord_id = $landlord_id AND YEAR(payment_date) = $chart_year AND p.status IN ('Paid', 'success') 
    GROUP BY MONTH(payment_date)
");
while($row = $inc_q->fetch_assoc()) { $income_data[$row['m']] = $row['tot']; }

// Fetch Expense Data
$exp_q = $conn->query("
    SELECT MONTH(expense_date) as m, SUM(amount) as tot 
    FROM expenses e
    JOIN properties p ON e.property_id = p.property_id 
    WHERE p.landlord_id = $landlord_id AND YEAR(expense_date) = $chart_year 
    GROUP BY MONTH(expense_date)
");
while($row = $exp_q->fetch_assoc()) { $expense_data[$row['m']] = $row['tot']; }

$income_values = implode(",", $income_data);
$expense_values = implode(",", $expense_data);

include "../config/header.php"; 
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 bg-dark text-white vh-100 p-3 position-fixed">
            <h5 class="text-center mb-4 fw-bold text-success">LANDLORD HUB</h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="dashboard.php">📊 Dashboard</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="properties.php">🏢 Properties</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="tenants.php">👥 Tenants</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link active bg-success text-white" href="payments.php">💰 Financials</a>
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
                <h3>💰 Financial Overview (<?= date('F Y') ?>)</h3>
                <button type="button" class="btn btn-danger fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                    ➖ Log New Expense
                </button>
            </div>

            <?php if(isset($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
            <?php if(isset($error_msg)) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-0 border-start border-success border-4 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.8rem;">Rent Collected This Month</div>
                            <div class="h3 mb-0 fw-bold text-success">KES <?= number_format($monthly_rent, 2) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 border-start border-danger border-4 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.8rem;">Overhead & Bills This Month</div>
                            <div class="h3 mb-0 fw-bold text-danger">KES <?= number_format($monthly_expenses, 2) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 border-start border-primary border-4 shadow-sm h-100 <?php echo ($net_profit < 0) ? 'bg-warning' : ''; ?>">
                        <div class="card-body">
                            <div class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.8rem;">Net Profit (NOI)</div>
                            <div class="h3 mb-0 fw-bold text-primary">KES <?= number_format($net_profit, 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span>📊 Profit vs Loss Analytics (<?= $chart_year ?>)</span>
                    <form method="GET" action="payments.php" class="d-inline">
                        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="2026" <?= $chart_year == 2026 ? 'selected' : '' ?>>2026</option>
                            <option value="2025" <?= $chart_year == 2025 ? 'selected' : '' ?>>2025</option>
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    <canvas id="financeChart" height="80"></canvas>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-success text-white fw-bold">📥 Recent Rent Payments</div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light position-sticky top-0">
                                        <tr>
                                            <th>Date</th>
                                            <th>Tenant / Unit</th>
                                            <th>Amount (KES)</th>
                                            <th>Ref</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $payments = $conn->query("
                                            SELECT p.payment_date, p.amount, p.mpesa_receipt, t.tenant_name, u.unit_number 
                                            FROM payments p
                                            JOIN tenants t ON p.tenant_id = t.tenant_id
                                            JOIN units u ON t.unit_id = u.unit_id
                                            JOIN properties prop ON u.property_id = prop.property_id
                                            WHERE prop.landlord_id = $landlord_id AND p.status IN ('Paid', 'success')
                                            ORDER BY p.payment_date DESC LIMIT 15
                                        ");
                                        if ($payments->num_rows > 0) {
                                            while($pay = $payments->fetch_assoc()) {
                                                $date = date('M d', strtotime($pay['payment_date']));
                                                echo "<tr>
                                                        <td class='text-muted'>{$date}</td>
                                                        <td class='fw-bold'>{$pay['tenant_name']} <small class='text-muted'>(U:{$pay['unit_number']})</small></td>
                                                        <td class='text-success fw-bold'>" . number_format($pay['amount']) . "</td>
                                                        <td><span class='badge bg-light text-dark border'>{$pay['mpesa_receipt']}</span></td>
                                                      </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center text-muted py-3'>No recent payments found.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-danger text-white fw-bold">📤 Recent Overhead Bills</div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light position-sticky top-0">
                                        <tr>
                                            <th>Date</th>
                                            <th>Category</th>
                                            <th>Amount (KES)</th>
                                            <th>Property</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $expenses = $conn->query("
                                            SELECT e.expense_date, e.expense_type, e.amount, p.property_name 
                                            FROM expenses e
                                            JOIN properties p ON e.property_id = p.property_id
                                            WHERE p.landlord_id = $landlord_id
                                            ORDER BY e.expense_date DESC LIMIT 15
                                        ");
                                        if ($expenses->num_rows > 0) {
                                            while($exp = $expenses->fetch_assoc()) {
                                                $date = date('M d', strtotime($exp['expense_date']));
                                                echo "<tr>
                                                        <td class='text-muted'>{$date}</td>
                                                        <td class='fw-bold'>{$exp['expense_type']}</td>
                                                        <td class='text-danger fw-bold'>" . number_format($exp['amount']) . "</td>
                                                        <td class='text-muted'>{$exp['property_name']}</td>
                                                      </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center text-muted py-3'>No expenses logged yet.</td></tr>";
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

<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">➖ Log Property Expense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="payments.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Property</label>
                        <select name="property_id" class="form-select" required>
                            <option value="">-- Choose Building --</option>
                            <?php
                            $props = $conn->query("SELECT property_id, property_name FROM properties WHERE landlord_id = $landlord_id");
                            while($p = $props->fetch_assoc()) {
                                echo "<option value='{$p['property_id']}'>{$p['property_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Expense Category</label>
                        <select name="expense_type" class="form-select" required>
                            <option value="Kenya Power (KPLC)">Common Area Electricity (KPLC)</option>
                            <option value="Nairobi Water">Water Supply (Nairobi Water/Borehole)</option>
                            <option value="Caretaker Salary">Caretaker / Security Salary</option>
                            <option value="Internet (WiFi)">Internet / Zuku / Safaricom Fiber</option>
                            <option value="KRA MRI Tax">KRA Monthly Rental Income Tax</option>
                            <option value="Garbage Collection">Garbage Collection</option>
                            <option value="Maintenance">General Maintenance / Repairs</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Amount (KES)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Date Paid</label>
                            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description / Notes (Optional)</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g., Fixed leaking pipe in Unit A2">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_expense" class="btn btn-danger fw-bold">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('financeChart').getContext('2d');
    const financeChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Income (Rent)',
                    data: [<?= $income_values ?>],
                    backgroundColor: 'rgba(25, 135, 84, 0.7)', 
                    borderRadius: 4
                },
                {
                    label: 'Expenses (Bills)',
                    data: [<?= $expense_values ?>],
                    backgroundColor: 'rgba(220, 53, 69, 0.7)', 
                    borderRadius: 4
                }
            ]
        },
        options: { 
            responsive: true, 
            scales: { y: { beginAtZero: true } },
            interaction: {
                mode: 'index',
                intersect: false,
            }
        }
    });
</script>

<?php include "../config/footer.php"; ?>