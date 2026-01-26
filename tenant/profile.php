<?php
session_start();
if(!isset($_SESSION['tenant'])){
    header("Location: login.php");
    exit();
}
include "../config/db.php";
include "../config/header.php";

$house = $_SESSION['tenant'];
$q = $conn->query("SELECT * FROM tenants WHERE house_number='$house'");
$t = $q->fetch_assoc();
?>

<div class="container mt-4">
    <div class="card shadow p-4">
        <h4>My Profile</h4>
        <p><b>House Number:</b> <?php echo $t['house_number']; ?></p>
        <p><b>Status:</b> <?php echo $t['status']; ?></p>
    </div>
</div>

<?php include "../config/footer.php"; ?>
