<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['tenant_id'])) {
    header("Location: login.php");
    exit();
}
$tenant_id = $_SESSION['tenant_id'];

// --- 🛑 PRG PATTERN: GRAB FLASH MESSAGES AFTER REDIRECT ---
$success_msg = $error_msg = null;
if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']); 
}
if (isset($_SESSION['error_msg'])) {
    $error_msg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// --- 🪄 AJAX ENDPOINT: Fetch vacant units for transfer requests ---
if (isset($_GET['action']) && $_GET['action'] == 'get_vacant_units') {
    $prop_id = (int)$_GET['property_id'];
    $units = [];
    $res = $conn->query("SELECT unit_id, unit_number, rent_amount FROM units WHERE property_id = $prop_id AND status = 'vacant' ORDER BY unit_number ASC");
    if ($res) { while($row = $res->fetch_assoc()) { $units[] = $row; } }
    echo json_encode($units);
    exit(); 
}

// --- HANDLE FORM SUBMISSIONS (Duplicate-proof!) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_request'])) {
    $req_type = $conn->real_escape_string($_POST['request_type']);
    $message = $conn->real_escape_string($_POST['message']);
    
    $target_unit_id = NULL;
    $target_wifi_plan = NULL;

    if ($req_type == 'WiFi Change') {
        $target_wifi_plan = $conn->real_escape_string($_POST['target_wifi_plan']);
    } elseif ($req_type == 'Unit Transfer') {
        $target_unit_id = (int)$_POST['target_unit_id'];
    }

    $stmt = $conn->prepare("INSERT INTO service_requests (tenant_id, request_type, message, target_unit_id, target_wifi_plan, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("issis", $tenant_id, $req_type, $message, $target_unit_id, $target_wifi_plan);
    
    if ($stmt->execute()) {
        $conn->query("INSERT INTO audit_logs (user_type, tenant_id, action) VALUES ('Tenant', $tenant_id, 'Submitted a $req_type request.')");
        
        $_SESSION['success_msg'] = "Your request has been successfully submitted to the landlord!";
        header("Location: requests.php");
        exit();
    } else {
        $_SESSION['error_msg'] = "Failed to submit request. Please try again.";
        header("Location: requests.php");
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
                <li class="nav-item"><a class="nav-link text-white" href="payments.php">💳 Make Payment</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="receipts.php">🧾 Receipts & History</a></li>
                <li class="nav-item"><a class="nav-link text-white active bg-dark" href="requests.php">🛠 Service Requests</a></li>
                <li class="nav-item"><a class="nav-link text-white mt-4" href="logout.php">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="col-10 offset-2 p-4 bg-light min-vh-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark mb-0">🛠 Action Center</h3>
                <button type="button" class="btn btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                    ➕ Submit New Request
                </button>
            </div>

            <?php if($success_msg): ?>
                <div class="alert alert-success border-start border-success border-4 shadow-sm">
                    <?= $success_msg ?>
                </div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div class="alert alert-danger border-start border-danger border-4 shadow-sm">
                    <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-bold">My Request History</div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Date</th>
                                <th>Type</th>
                                <th style="width: 50%;">Details</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $requests = $conn->query("
                                SELECT sr.*, tu.unit_number as target_unit, tp.property_name as target_property
                                FROM service_requests sr
                                LEFT JOIN units tu ON sr.target_unit_id = tu.unit_id
                                LEFT JOIN properties tp ON tu.property_id = tp.property_id
                                WHERE sr.tenant_id = $tenant_id ORDER BY sr.request_date DESC
                            ");
                            
                            if ($requests->num_rows > 0) {
                                while($req = $requests->fetch_assoc()) {
                                    $date = date('M d, Y', strtotime($req['request_date']));
                                    
                                    $s_badge = 'warning';
                                    if($req['status'] == 'Resolved' || $req['status'] == 'Approved') $s_badge = 'success';
                                    if($req['status'] == 'Rejected') $s_badge = 'danger';

                                    $type_badge = "<span class='badge bg-secondary'>{$req['request_type']}</span>";
                                    $details = htmlspecialchars($req['message']);

                                    if ($req['request_type'] == 'WiFi Change') {
                                        $type_badge = "<span class='badge bg-info text-dark'>WiFi Change</span>";
                                        $details = "<strong>To:</strong> <span class='text-primary'>{$req['target_wifi_plan']}</span><br><i>\"{$details}\"</i>";
                                    } 
                                    elseif ($req['request_type'] == 'Unit Transfer') {
                                        $type_badge = "<span class='badge bg-primary'>Transfer</span>";
                                        $details = "<strong>To:</strong> {$req['target_property']} (Unit {$req['target_unit']})<br><i>\"{$details}\"</i>";
                                    }
                                    elseif ($req['request_type'] == 'Lease Termination') {
                                        $type_badge = "<span class='badge bg-danger'>Exit Property</span>";
                                    }

                                    echo "<tr>
                                            <td class='ps-3 text-muted small'>{$date}</td>
                                            <td>{$type_badge}</td>
                                            <td>{$details}</td>
                                            <td><span class='badge bg-{$s_badge}'>{$req['status']}</span></td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center text-muted py-4'>You haven't submitted any requests yet.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
</div>

<div class="modal fade" id="newRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">➕ Submit Service Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="requests.php">
                <div class="modal-body">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">What do you need help with?</label>
                        <select id="requestType" name="request_type" class="form-select form-select-lg" required>
                            <option value="">-- Select Request Type --</option>
                            <option value="Maintenance">🛠️ General Maintenance / Repairs</option>
                            <option value="WiFi Change">📶 Change WiFi Plan</option>
                            <option value="Unit Transfer">📦 Request Unit Transfer</option>
                            <option value="Lease Termination" class="text-danger fw-bold">🚪 Exit Property / Terminate Lease</option>
                        </select>
                    </div>

                    <div id="wifiSection" class="mb-4 d-none p-3 bg-light border rounded">
                        <label class="form-label fw-bold text-info">Select New WiFi Package</label>
                        <select name="target_wifi_plan" class="form-select">
                            <option value="None">Cancel WiFi (KES 0)</option>
                            <option value="5Mbps">5 Mbps Basic (KES 1,500)</option>
                            <option value="10Mbps">10 Mbps Standard (KES 2,500)</option>
                            <option value="20Mbps">20 Mbps Premium (KES 4,000)</option>
                        </select>
                    </div>

                    <div id="transferSection" class="mb-4 d-none p-3 bg-light border rounded">
                        <h6 class="fw-bold text-primary mb-3">Where would you like to move?</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Select Property</label>
                                <select id="propertySelect" class="form-select">
                                    <option value="">-- Choose Property --</option>
                                    <?php
                                    $props = $conn->query("SELECT property_id, property_name FROM properties");
                                    while($p = $props->fetch_assoc()) echo "<option value='{$p['property_id']}'>{$p['property_name']}</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Select Vacant Unit</label>
                                <select id="unitSelect" name="target_unit_id" class="form-select" disabled>
                                    <option value="">-- Select Property First --</option>
                                </select>
                                <small id="rentPreview" class="text-success fw-bold d-block mt-2"></small>
                            </div>
                        </div>
                    </div>
                    
                    <div id="exitWarning" class="alert alert-danger d-none shadow-sm">
                        <strong>⚠️ Warning:</strong> Submitting a Lease Termination request signals your intent to vacate. Once approved by the landlord, your portal access will be deactivated and your unit will be marked as vacant for new tenants.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Message / Details</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Please provide more details about your request..." required></textarea>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_request" class="btn btn-success fw-bold">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const requestType = document.getElementById('requestType');
    const wifiSection = document.getElementById('wifiSection');
    const transferSection = document.getElementById('transferSection');
    const exitWarning = document.getElementById('exitWarning');
    
    const propertySelect = document.getElementById('propertySelect');
    const unitSelect = document.getElementById('unitSelect');
    const rentPreview = document.getElementById('rentPreview');

    // 1. Show/Hide sections based on Request Type
    requestType.addEventListener('change', function() {
        wifiSection.classList.add('d-none');
        transferSection.classList.add('d-none');
        exitWarning.classList.add('d-none');
        
        document.querySelector('select[name="target_wifi_plan"]').removeAttribute('required');
        document.querySelector('select[name="target_unit_id"]').removeAttribute('required');

        if (this.value === 'WiFi Change') {
            wifiSection.classList.remove('d-none');
            document.querySelector('select[name="target_wifi_plan"]').setAttribute('required', 'required');
        } else if (this.value === 'Unit Transfer') {
            transferSection.classList.remove('d-none');
            document.querySelector('select[name="target_unit_id"]').setAttribute('required', 'required');
        } else if (this.value === 'Lease Termination') {
            exitWarning.classList.remove('d-none');
        }
    });

    // 2. AJAX: Fetch Vacant Units
    propertySelect.addEventListener('change', function() {
        let propId = this.value;
        unitSelect.innerHTML = '<option value="">-- Loading... --</option>';
        unitSelect.disabled = true;
        rentPreview.textContent = '';

        if (propId) {
            fetch(`requests.php?action=get_vacant_units&property_id=${propId}`)
                .then(response => response.json())
                .then(data => {
                    unitSelect.innerHTML = '<option value="">-- Select Vacant Unit --</option>';
                    if (data.length > 0) {
                        unitSelect.disabled = false;
                        data.forEach(unit => {
                            let option = document.createElement('option');
                            option.value = unit.unit_id;
                            option.dataset.rent = parseFloat(unit.rent_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
                            option.textContent = `Unit ${unit.unit_number}`;
                            unitSelect.appendChild(option);
                        });
                    } else {
                        unitSelect.innerHTML = '<option value="">❌ No vacant units available here!</option>';
                    }
                })
                .catch(error => console.error('Error fetching units:', error));
        } else {
            unitSelect.innerHTML = '<option value="">-- Select Property First --</option>';
            unitSelect.disabled = true;
        }
    });

    // 3. Show Rent Preview
    unitSelect.addEventListener('change', function() {
        let selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            rentPreview.textContent = `Expected Base Rent: KES ${selectedOption.dataset.rent}`;
        } else {
            rentPreview.textContent = '';
        }
    });
});
</script>

<?php include "../config/footer.php"; ?>