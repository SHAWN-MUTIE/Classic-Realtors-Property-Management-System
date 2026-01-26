<?php
include "../config/db.php";
session_start();

$house = $_POST['house_number'];
$password = $_POST['password'];

$sql = $conn->prepare("SELECT * FROM tenants WHERE house_number=?");
$sql->bind_param("s",$house);
$sql->execute();
$result = $sql->get_result();

if($row = $result->fetch_assoc()){
    if(password_verify($password,$row['password'])){
        $_SESSION['tenant'] = $house;
        header("Location: ../tenant/dashboard.php");
        exit();
    }
}
echo "Invalid login";
