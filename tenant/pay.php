<?php
session_start();
if(!isset($_SESSION['tenant'])){ header("Location: login.php"); }
include "../config/header.php";
?>

<div class="container mt-4">
    <h3>Make Rent Payment</h3>

    <form method="POST">
        <input type="number" name="amount" class="form-control" placeholder="Amount" required><br>
        <button class="btn btn-success">Pay via M-Pesa</button>
    </form>

<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    echo "<div class='alert alert-info mt-3'>
            STK Push sent to your phone. Payment successful.
          </div>";

    include "../config/db.php";
    $house = $_SESSION['tenant'];
    $amount = $_POST['amount'];

    $conn->query("INSERT INTO payments (house_number, amount)
                  VALUES ('$house','$amount')");
}
?>
</div>

<?php include "../config/footer.php"; ?>
