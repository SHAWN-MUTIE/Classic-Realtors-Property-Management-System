<?php
session_start();
if(!isset($_SESSION['tenant'])){ header("Location: login.php"); }
include "../config/header.php";
$house = $_SESSION['tenant'];
?>

<div class="container mt-4">
    <div class="card shadow p-4">
        <h4>Welcome, House <?php echo $house; ?></h4>
        <p class="text-muted">Manage your rent, receipts and service requests</p>

        <div class="list-group">
            <a href="profile.php" class="list-group-item list-group-item-action">👤 My Profile</a>
            <a href="pay.php" class="list-group-item list-group-item-action">💳 Make Payment</a>
            <a href="receipts.php" class="list-group-item list-group-item-action">🧾 View Receipts</a>
            <a href="request.php" class="list-group-item list-group-item-action">🛠 Service Request</a>
            <a href="logout.php" class="btn btn-danger mt-3">Logout</a>
        </div>
    </div>
</div>


<?php include "../config/footer.php"; ?>
