<?php
include "../config/db.php";
include "../config/header.php";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->query("SELECT * FROM landlords WHERE username='$username'");
    if($check->num_rows > 0){
        echo "<div class='alert alert-danger text-center'>Username already exists</div>";
    } else {
        $conn->query("INSERT INTO landlords (username, password)
                      VALUES ('$username', '$password')");
        echo "<div class='alert alert-success text-center'>
                Account created. <a href='login.php'>Login</a>
              </div>";
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow p-4">
                <h4 class="text-center">Landlord Registration</h4>

                <form method="POST">
                    <input type="text" name="username" class="form-control mb-3" placeholder="Username" required>
                    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                    <button class="btn btn-dark w-100">Register</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "../config/footer.php"; ?>
