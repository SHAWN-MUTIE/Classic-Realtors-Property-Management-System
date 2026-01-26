<?php
session_start();
require_once("../config/db.php");

// If already logged in, redirect to dashboard
if (isset($_SESSION['landlord_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT landlord_id, username, password FROM landlords WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // ✅ CORRECT SESSION VARIABLES
            $_SESSION['landlord_id'] = $user['landlord_id'];
            $_SESSION['username']    = $user['username'];

            header("Location: dashboard.php");
            exit();
        }
    }

    $error = "Invalid login details";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Landlord Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

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
                    <input type="text"
                           name="username"
                           class="form-control mb-3"
                           placeholder="Username"
                           required>

                    <input type="password"
                           name="password"
                           class="form-control mb-3"
                           placeholder="Password"
                           required>

                    <button class="btn btn-dark w-100">
                        Login
                    </button>
                </form>

                <p class="text-center mt-3">
                    <a href="register.php">Create Account</a>
                </p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
