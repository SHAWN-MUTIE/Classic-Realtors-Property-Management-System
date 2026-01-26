<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['landlord_id'])) {
    header("Location: login.php");
    exit();
}

$landlord_id = $_SESSION['landlord_id'];

/* ADD PROPERTY */
if (isset($_POST['add_property'])) {
    $property_name = trim($_POST['property_name']);
    $location = trim($_POST['location']);

    if (!empty($property_name)) {
        $stmt = $conn->prepare(
            "INSERT INTO properties (landlord_id, property_name, location)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iss", $landlord_id, $property_name, $location);
        $stmt->execute();
    }
}

/* DELETE PROPERTY */
if (isset($_GET['delete'])) {
    $property_id = intval($_GET['delete']);

    $stmt = $conn->prepare(
        "DELETE FROM properties WHERE property_id = ? AND landlord_id = ?"
    );
    $stmt->bind_param("ii", $property_id, $landlord_id);
    $stmt->execute();

    header("Location: properties.php");
    exit();
}

/* FETCH PROPERTIES */
$stmt = $conn->prepare(
    "SELECT 
        p.property_id,
        p.property_name,
        p.location,
        COUNT(t.tenant_id) AS total_tenants
     FROM properties p
     LEFT JOIN tenants t ON p.property_id = t.property_id
     WHERE p.landlord_id = ?
     GROUP BY p.property_id"
);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include("../includes/header.php"); ?>

<div class="container mt-4">
    <h3 class="mb-3">My Properties</h3>

    <!-- ADD PROPERTY FORM -->
    <div class="card mb-4">
        <div class="card-header">Add New Property</div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-5">
                        <input type="text" name="property_name" class="form-control"
                               placeholder="Property Name" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="location" class="form-control"
                               placeholder="Location (optional)">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" name="add_property"
                                class="btn btn-primary">
                            Add Property
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- PROPERTY LIST -->
    <div class="card">
        <div class="card-header">Your Properties</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Property Name</th>
                        <th>Location</th>
                        <th>Tenants</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['property_name']) ?></td>
                                <td><?= htmlspecialchars($row['location']) ?></td>
                                <td><?= $row['total_tenants'] ?></td>
                                <td>
                                    <a href="properties.php?delete=<?= $row['property_id'] ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Delete this property? All tenants will also be removed.')">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center p-3">
                                No properties added yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
