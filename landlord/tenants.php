<?php
session_start();
require_once("../config/db.php");

// 1. Security Check
if (!isset($_SESSION['landlord_id'])) {
    header("Location: login.php");
    exit();
}
$landlord_id = $_SESSION['landlord_id'];

// 2. Handle Actions (Penalty & Deactivation)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $t_id = (int)$_POST['tenant_id'];

    if (isset($_POST['apply_penalty'])) {
        $amount = (float)$_POST['penalty_amount'];
        $conn->query("UPDATE tenants SET balance = balance + $amount WHERE tenant_id = $t_id");
        $conn->query("INSERT INTO audit_logs (user_type, tenant_id, action) VALUES ('Landlord', $t_id, 'Applied penalty of KES $amount')");
    }

    if (isset($_POST['deactivate'])) {
        $conn->query("UPDATE tenants SET status = 'inactive' WHERE tenant_id = $t_id");
        $conn->query("UPDATE units JOIN tenants ON units.unit_id = tenants.unit_id SET units.status = 'vacant' WHERE tenants.tenant_id = $t_id");
    }
}

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
                    <a class="nav-link active bg-success text-white" href="tenants.php">👥 Tenants</a>
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
                <div>
                    <h3 class="fw-bold mb-0">Tenant Directory</h3>
                    <p class="text-muted">Manage active leases and balances</p>
                </div>
                <div class="d-flex align-items-center">
                    <input type="text" id="tenantSearch" class="form-control me-2" placeholder="Search name or phone..." style="width: 250px;">
                    <button class="btn btn-success fw-bold shadow-sm">+ New Tenant</button>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tenantTable">
                            <thead class="table-dark">
                                <tr>
                                    <th class="ps-3">Name / Property</th>
                                    <th>Unit</th>
                                    <th>Phone (Login ID)</th>
                                    <th>Fixed Bill</th>
                                    <th>Arrears</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT t.*, u.unit_number, u.rent_amount, p.property_name 
                                          FROM tenants t 
                                          JOIN units u ON t.unit_id = u.unit_id 
                                          JOIN properties p ON u.property_id = p.property_id 
                                          WHERE p.landlord_id = $landlord_id AND t.status = 'active'
                                          ORDER BY t.balance DESC";
                                $res = $conn->query($query);

                                while($t = $res->fetch_assoc()) {
                                    $total_bill = $t['rent_amount'] + $t['wifi_cost'] + $t['garbage_fee'];
                                    $bal_color = ($t['balance'] > 0) ? 'text-danger fw-bold' : 'text-success';
                                    
                                    echo "<tr>
                                        <td class='ps-3'>
                                            <div class='fw-bold text-dark'>{$t['tenant_name']}</div>
                                            <small class='text-muted'>{$t['property_name']}</small>
                                        </td>
                                        <td><span class='badge bg-secondary'>{$t['unit_number']}</span></td>
                                        <td>
                                            <code class='fs-6 fw-bold text-primary'>{$t['phone']}</code>
                                        </td>
                                        <td>KES " . number_format($total_bill) . "</td>
                                        <td class='{$bal_color}'>KES " . number_format($t['balance'], 2) . "</td>
                                        <td class='text-end pe-3'>
                                            <button type='button' class='btn btn-sm btn-light border' data-bs-toggle='modal' data-bs-target='#penaltyModal{$t['tenant_id']}'>⚠️ Fee</button>
                                            <form method='POST' class='d-inline' onsubmit='return confirm(\"Evict/Deactivate this tenant?\")'>
                                                <input type='hidden' name='tenant_id' value='{$t['tenant_id']}'>
                                                <button type='submit' name='deactivate' class='btn btn-sm btn-outline-danger'>🛑</button>
                                            </form>
                                        </td>
                                    </tr>";

                                    // Penalty Modal
                                    echo "
                                    <div class='modal fade' id='penaltyModal{$t['tenant_id']}' tabindex='-1'>
                                      <div class='modal-dialog modal-dialog-centered modal-sm'>
                                        <form class='modal-content' method='POST'>
                                          <div class='modal-header border-0 pb-0'><h6 class='modal-title fw-bold'>Add Penalty Fee</h6></div>
                                          <div class='modal-body'>
                                            <input type='hidden' name='tenant_id' value='{$t['tenant_id']}'>
                                            <label class='small text-muted mb-1'>Amount (KES)</label>
                                            <input type='number' name='penalty_amount' class='form-control form-control-lg' placeholder='e.g. 500' required>
                                          </div>
                                          <div class='modal-footer border-0 pt-0'>
                                            <button type='submit' name='apply_penalty' class='btn btn-danger w-100 fw-bold'>Apply Penalty</button>
                                          </div>
                                        </form>
                                      </div>
                                    </div>";
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

<script>
// Real-time filtering
document.getElementById('tenantSearch').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let rows = document.querySelector("#tenantTable tbody").rows;
    for (let i = 0; i < rows.length; i++) {
        let text = rows[i].textContent.toUpperCase();
        rows[i].style.display = text.includes(filter) ? "" : "none";
    }
});
</script>

<?php include "../config/footer.php"; ?>