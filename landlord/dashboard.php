<?php
session_start();

if (!isset($_SESSION['landlord_id'])) {
    header("Location: login.php");
    exit();
}

require_once("../config/db.php");
?>


<div class="container-fluid">
    <div class="row">

       <!-- Sidebar -->
<div class="col-2 bg-dark text-white vh-100 p-3">
    <h5 class="text-center mb-4">Landlord</h5>

    <ul class="nav nav-pills flex-column">
        <li class="nav-item">
            <a class="nav-link text-white" href="dashboard.php">📋 Overview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="properties.php">🏢 Properties</a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="tenants.php">👥 Tenants</a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="payments.php">💳 Payments</a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="requests.php">🛠 Requests</a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="reports.php">📊 Reports</a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white mt-4" href="logout.php">🚪 Logout</a>
        </li>
    </ul>
</div>



        <!-- Content -->
        <div class="col-10 p-4">
            <h3>Houses Overview</h3>

            <table class="table table-bordered">
                <tr>
                    <th>House Number</th>
                    <th>Status</th>
                </tr>
                <?php
                $houses = $conn->query("SELECT * FROM houses");
                while($h = $houses->fetch_assoc()){
                    echo "<tr>
                        <td>{$h['house_number']}</td>
                        <td>{$h['status']}</td>
                    </tr>";
                }
                ?>
            </table>
        </div>

    </div>
</div>

<?php include "../config/footer.php"; ?>
