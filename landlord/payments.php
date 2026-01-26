<?php
session_start();
if(!isset($_SESSION['landlord'])){
    header("Location: login.php");
}
include "../config/db.php";
include "../config/header.php";
?>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-2 bg-dark text-white vh-100 p-3">
            <h5 class="text-center mb-4">Landlord</h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">🏠 Houses</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="tenants.php">👥 Tenants</a></li>
                <li class="nav-item"><a class="nav-link text-white active bg-secondary" href="payments.php">💳 Payments</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="requests.php">🛠 Requests</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="reports.php">📊 Reports</a></li>
            </ul>
        </div>

        <!-- Content -->
        <div class="col-10 p-4">
            <h3>Payments Overview</h3>

            <table class="table table-bordered table-striped mt-3">
                <thead>
                    <tr>
                        <th>House</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $q = $conn->query("SELECT * FROM payments ORDER BY id DESC");
                while($p = $q->fetch_assoc()){
                    $badge = $p['status'] == 'paid' ? 'success' : 'warning';

                    echo "<tr>
                        <td>{$p['house_number']}</td>
                        <td>KES {$p['amount']}</td>
                        <td>{$p['payment_date']}</td>
                        <td><span class='badge bg-$badge'>{$p['status']}</span></td>
                    </tr>";
                }
                ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include "../config/footer.php"; ?>
