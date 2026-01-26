<?php
include "../config/db.php";
$id = $_POST['request_id'];
$conn->query("UPDATE service_requests SET status='Resolved' WHERE request_id=$id");
header("Location: ../landlord/requests.php");
