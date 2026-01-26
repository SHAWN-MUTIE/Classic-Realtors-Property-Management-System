<?php
session_start();
include "../config/db.php";
include "../config/header.php";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $house = $_POST['house'];
    $password = $_POST['password'];

    $q = $conn->query("SELECT * FROM tenants 
                       WHERE house_number='$house' AND status='active'");
    if($q->num_rows == 1){
        $tenant = $q->fetch_assoc();
        if(password_verify($password, $tenant['password'])){
            $_SESSION['tenant'] = $tenant['house_number'];
            header("Location: dashboard.php");
            exit();
        }
    }
    echo "<div class='alert alert-danger text-center'>Invalid login credentials</div>";
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow p-4">
                <h4 class="text-center">Tenant Login</h4>

                <form method="POST">
                    <input type="text" name="house" class="form-control mb-3" placeholder="House Number" required>
                    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                    <button class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "../config/footer.php"; ?>
