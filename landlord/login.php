<?php
session_start();
require_once "../config/db.php";
require_once "../includes/header.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare(
        "SELECT landlord_id, password 
         FROM landlords 
         WHERE username = ?"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $landlord = $result->fetch_assoc();

        if (password_verify($password, $landlord["password"])) {
            $_SESSION["landlord_id"] = $landlord["landlord_id"];
            header("Location: dashboard.php");
            exit();
        }
    }

    $error = "Invalid login credentials";
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow p-4">
                <h4 class="text-center mb-3">Landlord Login</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger text-center">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="text" name="username" class="form-control mb-3" placeholder="Username" required>
                    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                    <button class="btn btn-dark w-100">Login</button>
                </form>

                <p class="text-center mt-3">
                    <a href="register.php">Create Account</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
