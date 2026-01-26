<?php
session_start();
if(!isset($_SESSION['landlord'])){ header("Location: login.php"); exit(); }
include "../config/db.php";
include "../config/header.php";

$id = intval($_GET['id']);
$tenant = $conn->query("SELECT * FROM tenants WHERE tenant_id=$id")->fetch_assoc();

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $house = $_POST['house'];
    $status = $_POST['status'];

    if(!empty($_POST['password'])){
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $conn->query("UPDATE tenants 
                      SET house_number='$house', password='$pass', status='$status'
                      WHERE tenant_id=$id");
    } else {
        $conn->query("UPDATE tenants 
                      SET house_number='$house', status='$status'
                      WHERE tenant_id=$id");
    }

    header("Location: tenants.php");
    exit();
}
?>

<div class="container mt-4">
    <h3>Edit Tenant</h3>

    <div class="card p-4 shadow">
        <form method="POST">
            <label>House Number</label>
            <input type="text" name="house" class="form-control mb-3"
                   value="<?php echo $tenant['house_number']; ?>" required>

            <label>New Password (leave blank to keep current)</label>
            <input type="password" name="password" class="form-control mb-3">

            <label>Status</label>
            <select name="status" class="form-control mb-3">
                <option value="active" <?php if($tenant['status']=='active') echo 'selected'; ?>>Active</option>
                <option value="inactive" <?php if($tenant['status']=='inactive') echo 'selected'; ?>>Inactive</option>
            </select>

            <button class="btn btn-primary">Update Tenant</button>
            <a href="tenants.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include "../config/footer.php"; ?>
