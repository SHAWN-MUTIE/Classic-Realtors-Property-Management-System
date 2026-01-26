<?php
include "../config/db.php";
session_start();

$username = $_POST['username'];
$password = $_POST['password'];

$sql = $conn->prepare("SELECT * FROM landlords WHERE username=?");
$sql->bind_param("s",$username);
$sql->execute();
$result = $sql->get_result();

if($row = $result->fetch_assoc()){
    if(password_verify($password,$row['password'])){
        $_SESSION['landlord'] = $row['landlord_id'];
        header("Location: ../landlord/dashboard.php");
        exit();
    }
}
echo "Invalid login";
