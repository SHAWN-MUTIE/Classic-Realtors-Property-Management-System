<?php
session_start();
include "../config/db.php";

/* 
  STEP 1: Security check
  Make sure tenant is logged in
*/
if(!isset($_SESSION['tenant'])){
    header("Location: login.php");
    exit();
}

$house = $_SESSION['tenant'];
$messageSent = false;

/*
  STEP 2: Handle form submission
  This code runs ONLY when form is submitted
*/
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $message = trim($_POST['message']);

    if(!empty($message)){
        $stmt = $conn->prepare(
            "INSERT INTO service_requests (house_number, message)
             VALUES (?, ?)"
        );
        $stmt->bind_param("ss", $house, $message);
        $stmt->execute();

        $messageSent = true;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Service Request</title>
</head>
<body>

<h2>Service Request</h2>

<?php if($messageSent): ?>
    <p style="color:green;">Request sent successfully.</p>
<?php endif; ?>

<form method="POST">
    <textarea name="message" placeholder="Describe the issue" required></textarea><br><br>
    <button type="submit">Submit Request</button>
</form>

<br>
<a href="dashboard.php">Back to Dashboard</a>

</body>
</html>
