<?php
include "../config/db.php";
session_start();
$house = $_SESSION['tenant'];

$r = $conn->query("SELECT * FROM payments WHERE house_number='$house'");
while($row = $r->fetch_assoc()){
    echo "Paid ".$row['amount']." on ".$row['payment_date']."<br>";
}
