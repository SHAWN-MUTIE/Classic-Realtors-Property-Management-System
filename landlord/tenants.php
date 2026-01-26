<?php
session_start();
if (!isset($_SESSION['landlord'])) {
    header("Location: login.php");
    exit();
}

include "../config/db.php";
include "../config/header.php";

$landlord_id = $_SESSION['landlord'];

/* =========================
   DEACTIVATE TENANT
========================= */
if (isset($_GET['deactivate'])) {
    $id = intval($_GET['deactivate']);

    $conn->query("UPDATE tenants SET status='inactive' WHERE tenant_id=$id");

    $conn->query("INSERT INTO audit_logs (user_type, action)
                  VALUES ('landlord',
                  'Deactivated tenant ID $id')");

    header("Location: tenants.php");
    exit();
}

/* =========================
   DELETE TENANT
========================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $conn->query("DELETE FROM tenants WHERE tenant_id=$id");

    $conn->query("INSERT INTO audit_logs (user_type, action)
                  VALUES ('landlord',
                  'Deleted tenant ID $id')");

    header("Location: tenants.php");
    exit();
}

/* =========================
   ADD TENANT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $house = trim($_POST['house']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->query("SELECT * FROM tenants WHERE house_number='$house'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger'>House already assigned</div>";
    } else {
        $conn->query("INSERT INTO tenants (house_number, password, status)
                      VALUES ('$house', '$password', 'active')");

        $conn->query("INSERT INTO audit_logs (user_type, action)
                      VALUES ('landlord',
                      'Added tenant for house $house')");

        $msg = "<div class='alert alert-success'>Tenant added successfully</div>";
    }
}
?>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-2 bg-dark text-white vh-100 p-3">
            <h5 class="text-center mb-4">Landlord</h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a class="nav-link text-white" href="dashboard.php">🏠 Houses</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white active bg-secondary" href="tenants.php">👥 Tenants</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="payments.php">💳 Payments</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="requests.php">🛠 Requests</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="reports.php">📊 Reports</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="audit.php">🧾 Audit Logs</a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-white" href="logout.php">🚪 Logout</a>
                </li>
            </ul>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-10 p-4">
            <h3 class="mb-4">Manage Tenants</h3>

            <?php if (isset($msg)) echo $msg; ?>

            <!-- ADD TENANT FORM -->
            <div class="card shadow p-4 mb-4">
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <input type="text"
                               name="house"
                               class="form-control"
                               placeholder="House Number"
                               required>
                    </div>

                    <div class="col-md-4">
                        <input type="password"
                               name="password"
                               class="form-control"
                               placeholder="Tenant Password"
                               required>
                    </div>

                    <div class="col-md-4">
                        <button class="btn btn-success w-100">
                            Add Tenant
                        </button>
                    </div>
                </form>
            </div>

            <!-- TENANTS TABLE -->
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>House Number</th>
                        <th>Status</th>
                        <th width="30%">Actions</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                $q = $conn->query("SELECT * FROM tenants ORDER BY tenant_id DESC");
                while ($t = $q->fetch_assoc()) {
                    $badge = $t['status'] === 'active' ? 'success' : 'secondary';
                ?>

                    <tr>
                        <td><?php echo $t['house_number']; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $badge; ?>">
                                <?php echo $t['status']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit_tenant.php?id=<?php echo $t['tenant_id']; ?>"
                               class="btn btn-sm btn-warning">Edit</a>

                            <a href="?deactivate=<?php echo $t['tenant_id']; ?>"
                               class="btn btn-sm btn-secondary">Deactivate</a>

                            <button class="btn btn-sm btn-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteModal<?php echo $t['tenant_id']; ?>">
                                Delete
                            </button>
                        </td>
                    </tr>

                    <!-- DELETE MODAL -->
                    <div class="modal fade"
                         id="deleteModal<?php echo $t['tenant_id']; ?>"
                         tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">

                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Delete</h5>
                                    <button type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    Delete tenant in house
                                    <strong><?php echo $t['house_number']; ?></strong>?
                                </div>

                                <div class="modal-footer">
                                    <a href="?delete=<?php echo $t['tenant_id']; ?>"
                                       class="btn btn-danger">Yes, Delete</a>
                                    <button type="button"
                                            class="btn btn-secondary"
                                            data-bs-dismiss="modal">Cancel</button>
                                </div>

                            </div>
                        </div>
                    </div>

                <?php } ?>

                </tbody>
            </table>

        </div>
    </div>
</div>

<?php include "../config/footer.php"; ?>
