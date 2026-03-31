<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['landlord_id'])) {
    header("Location: login.php");
    exit();
}
$landlord_id = $_SESSION['landlord_id'];

// --- ⚙️ HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $req_id = (int)$_POST['request_id'];
    $tenant_id = (int)$_POST['tenant_id'];

    // 1. Resolve Maintenance
    if (isset($_POST['resolve_maintenance'])) {
        $conn->query("UPDATE service_requests SET status = 'Resolved' WHERE request_id = $req_id");
        $conn->query("INSERT INTO audit_logs (user_type, tenant_id, action) VALUES ('Landlord', $tenant_id, 'Marked maintenance as resolved.')");
    }

    // 2. Approve Password Reset (NEW)
    if (isset($_POST['approve_reset'])) {
        $conn->query("UPDATE service_requests SET status = 'Approved' WHERE request_id = $req_id");
        $conn->query("INSERT INTO audit_logs (user_type, tenant_id, action) VALUES ('Landlord', $tenant_id, 'Authorized a password reset request.')");
        $success_msg = "Password reset authorized for the tenant.";
    }

    // 3. Reject Any Request
    if (isset($_POST['reject_request'])) {
        $conn->query("UPDATE service_requests SET status = 'Rejected' WHERE request_id = $req_id");
    }
}

include "../config/header.php"; 
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 bg-dark text-white vh-100 p-3 position-fixed">
            <h5 class="text-center mb-4 fw-bold text-success">LANDLORD HUB</h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item mb-2"><a class="nav-link text-white" href="dashboard.php">📊 Dashboard</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="properties.php">🏢 Properties</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="tenants.php">👥 Tenants</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="payments.php">💰 Financials</a></li>
                <li class="nav-item mb-2"><a class="nav-link active bg-success text-white" href="requests.php">🛠 Requests</a></li>
                <li class="nav-item mt-5"><a class="nav-link text-danger fw-bold" href="logout.php">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="col-md-10 offset-md-2 p-4 bg-light min-vh-100">
            <h3 class="fw-bold mb-4">🛠 Tenant Action Center</h3>

            <?php if(isset($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Date</th>
                                <th>Tenant</th>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $requests = $conn->query("
                                SELECT sr.*, t.tenant_name, p.property_name, u.unit_number 
                                FROM service_requests sr
                                JOIN tenants t ON sr.tenant_id = t.tenant_id
                                JOIN units u ON t.unit_id = u.unit_id
                                JOIN properties p ON u.property_id = p.property_id
                                WHERE p.landlord_id = $landlord_id
                                ORDER BY sr.status = 'pending' DESC, sr.request_date DESC
                            ");
                            
                            while($req = $requests->fetch_assoc()) {
                                $s_badge = ($req['status'] == 'Approved' || $req['status'] == 'Resolved') ? 'success' : (($req['status'] == 'Rejected') ? 'danger' : 'warning');
                                
                                echo "<tr>
                                        <td class='ps-3 small text-muted'>".date('M d', strtotime($req['request_date']))."</td>
                                        <td><b>{$req['tenant_name']}</b><br><small>{$req['unit_number']}</small></td>
                                        <td><span class='badge bg-secondary'>{$req['request_type']}</span></td>
                                        <td><small>{$req['message']}</small></td>
                                        <td><span class='badge bg-$s_badge'>{$req['status']}</span></td>
                                        <td class='text-end pe-3'>";
                                
                                if ($req['status'] == 'pending') {
                                    echo "<form method='POST' class='d-inline'>
                                            <input type='hidden' name='request_id' value='{$req['request_id']}'>
                                            <input type='hidden' name='tenant_id' value='{$req['tenant_id']}'>";
                                    
                                    if ($req['request_type'] == 'Password Reset') {
                                        echo "<button type='submit' name='approve_reset' class='btn btn-sm btn-info fw-bold me-1'>🔓 Authorize</button>";
                                    } else {
                                        echo "<button type='submit' name='resolve_maintenance' class='btn btn-sm btn-success fw-bold me-1'>✔️ Resolve</button>";
                                    }

                                    echo "<button type='submit' name='reject_request' class='btn btn-sm btn-outline-danger'>❌</button>
                                          </form>";
                                }
                                echo "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include "../config/footer.php"; ?>