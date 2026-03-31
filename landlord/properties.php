<?php
session_start();

if (!isset($_SESSION['landlord_id'])) {
    header("Location: login.php");
    exit();
}

require_once("../config/db.php");
$landlord_id = $_SESSION['landlord_id'];

// --- HANDLE FORM SUBMISSIONS ---

// 1. Add New Property
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_property'])) {
    $prop_name = $conn->real_escape_string($_POST['property_name']);
    $location = $conn->real_escape_string($_POST['location']);

    $stmt = $conn->prepare("INSERT INTO properties (landlord_id, property_name, location) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $landlord_id, $prop_name, $location);
    if ($stmt->execute()) {
        $success_msg = "Property added successfully!";
    } else {
        $error_msg = "Error adding property.";
    }
}

// 2. Add New Unit to a Property
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_unit'])) {
    $prop_id = (int)$_POST['property_id'];
    $unit_num = $conn->real_escape_string($_POST['unit_number']);
    $rent = (float)$_POST['rent_amount'];

    // Check for duplicate unit in the same property
    $check = $conn->query("SELECT * FROM units WHERE property_id = $prop_id AND unit_number = '$unit_num'");
    if ($check->num_rows > 0) {
        $error_msg = "Unit $unit_num already exists in this property!";
    } else {
        $stmt = $conn->prepare("INSERT INTO units (property_id, unit_number, rent_amount, status) VALUES (?, ?, ?, 'vacant')");
        $stmt->bind_param("isd", $prop_id, $unit_num, $rent);
        if ($stmt->execute()) {
            $success_msg = "Unit $unit_num created successfully!";
        } else {
            $error_msg = "Error adding unit.";
        }
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
                <li class="nav-item mb-2"><a class="nav-link active bg-success text-white" href="properties.php">🏢 Properties</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="tenants.php">👥 Tenants</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="payments.php">💰 Financials</a></li>
                <li class="nav-item mb-2"><a class="nav-link text-white" href="requests.php">🛠 Requests</a></li>
                <li class="nav-item mt-5"><a class="nav-link text-danger fw-bold" href="logout.php">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="col-md-10 offset-md-2 p-4 bg-light min-vh-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>🏢 Property & Unit Manager</h3>
                <button type="button" class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
                    ➕ Add New Property
                </button>
            </div>

            <?php if(isset($success_msg)): ?>
                <div class="alert alert-success border-start border-success border-4 shadow-sm alert-dismissible fade show">
                    <?= $success_msg ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if(isset($error_msg)): ?>
                <div class="alert alert-danger border-start border-danger border-4 shadow-sm alert-dismissible fade show">
                    <?= $error_msg ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <?php
                $properties = $conn->query("SELECT * FROM properties WHERE landlord_id = $landlord_id ORDER BY property_id DESC");
                if ($properties->num_rows > 0) {
                    while($prop = $properties->fetch_assoc()) {
                        $p_id = $prop['property_id'];
                        $unit_stats = $conn->query("
                            SELECT 
                                COUNT(*) as total_units,
                                SUM(CASE WHEN status = 'vacant' THEN 1 ELSE 0 END) as vacant_units,
                                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_units
                            FROM units WHERE property_id = $p_id
                        ")->fetch_assoc();

                        echo "
                        <div class='col-md-6 mb-4'>
                            <div class='card shadow-sm border-0 h-100'>
                                <div class='card-header bg-dark text-white d-flex justify-content-between align-items-center'>
                                    <h5 class='mb-0 fw-bold'>{$prop['property_name']}</h5>
                                    <span class='badge bg-secondary'>{$prop['location']}</span>
                                </div>
                                <div class='card-body'>
                                    <div class='row text-center mb-3'>
                                        <div class='col-4'><div class='text-muted small fw-bold'>Total Units</div><div class='h4'>{$unit_stats['total_units']}</div></div>
                                        <div class='col-4'><div class='text-success small fw-bold'>Occupied</div><div class='h4'>{$unit_stats['occupied_units']}</div></div>
                                        <div class='col-4'><div class='text-danger small fw-bold'>Vacant</div><div class='h4'>{$unit_stats['vacant_units']}</div></div>
                                    </div>
                                    
                                    <h6 class='fw-bold border-bottom pb-2'>Manage Units</h6>
                                    <div class='table-responsive' style='max-height: 200px; overflow-y: auto;'>
                                        <table class='table table-sm table-hover'>
                                            <thead class='table-light position-sticky top-0'>
                                                <tr><th>Unit</th><th>Rent (KES)</th><th>Status</th></tr>
                                            </thead>
                                            <tbody>";
                        
                        $units = $conn->query("SELECT * FROM units WHERE property_id = $p_id ORDER BY unit_number ASC");
                        if ($units->num_rows > 0) {
                            while($u = $units->fetch_assoc()) {
                                $badge = ($u['status'] == 'vacant') ? 'danger' : 'success';
                                echo "<tr>
                                        <td class='fw-bold'>{$u['unit_number']}</td>
                                        <td>" . number_format($u['rent_amount'], 2) . "</td>
                                        <td><span class='badge bg-{$badge}'>{$u['status']}</span></td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' class='text-center text-muted'>No units created yet.</td></tr>";
                        }

                        echo "              </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class='card-footer bg-white border-top-0'>
                                    <button class='btn btn-sm btn-outline-primary w-100 fw-bold' data-bs-toggle='modal' data-bs-target='#addUnitModal{$p_id}'>
                                        ➕ Add Unit to {$prop['property_name']}
                                    </button>
                                </div>
                            </div>
                        </div>";

                        echo "
                        <div class='modal fade' id='addUnitModal{$p_id}' tabindex='-1'>
                            <div class='modal-dialog'>
                                <div class='modal-content'>
                                    <div class='modal-header bg-dark text-white'>
                                        <h5 class='modal-title'>Add Unit to {$prop['property_name']}</h5>
                                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                                    </div>
                                    <form method='POST' action='properties.php'>
                                        <div class='modal-body'>
                                            <input type='hidden' name='property_id' value='{$p_id}'>
                                            <div class='mb-3'>
                                                <label class='form-label fw-bold'>Unit Number / Name</label>
                                                <input type='text' name='unit_number' class='form-control' placeholder='e.g., A1, 102, Shop 4' required>
                                            </div>
                                            <div class='mb-3'>
                                                <label class='form-label fw-bold'>Monthly Rent Amount (KES)</label>
                                                <input type='number' step='0.01' name='rent_amount' class='form-control' placeholder='e.g., 15000' required>
                                            </div>
                                        </div>
                                        <div class='modal-footer'>
                                            <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                                            <button type='submit' name='add_unit' class='btn btn-primary'>Save Unit</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>";
                    }
                } else {
                    echo "<div class='col-12'><div class='alert alert-info'>You haven't added any properties yet. Click 'Add New Property' to begin.</div></div>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addPropertyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">🏢 Add New Property</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="properties.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Property Name</label>
                        <input type="text" name="property_name" class="form-control" placeholder="e.g., Classic Heights" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="e.g., Mlolongo, Nairobi" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_property" class="btn btn-primary">Save Property</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "../config/footer.php"; ?>