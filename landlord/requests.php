<?php
session_start();
if(!isset($_SESSION['landlord'])){
    header("Location: login.php");
}
include "../config/db.php";
include "../config/header.php";

/* Handle status update */
if(isset($_GET['resolve'])){
    $id = intval($_GET['resolve']);
    $conn->query("UPDATE service_requests SET status='resolved' WHERE id=$id");
}
?>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-2 bg-dark text-white vh-100 p-3">
            <h5 class="text-center mb-4">Landlord</h5>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">🏠 Houses</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="tenants.php">👥 Tenants</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="payments.php">💳 Payments</a></li>
                <li class="nav-item"><a class="nav-link text-white active bg-secondary" href="requests.php">🛠 Requests</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="reports.php">📊 Reports</a></li>
            </ul>
        </div>

        <!-- Content -->
        <div class="col-10 p-4">
            <h3>Service Requests</h3>

            <table class="table table-bordered table-striped mt-3">
                <thead>
                    <tr>
                        <th>House</th>
                        <th>Issue</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                $q = $conn->query("SELECT * FROM service_requests ORDER BY id DESC");
                while($r = $q->fetch_assoc()){
                    $badge = $r['status'] == 'resolved' ? 'success' : 'warning';
                    $btn = $r['status'] == 'pending'
                        ? "<a href='?resolve={$r['id']}' class='btn btn-sm btn-success'>Mark Resolved</a>"
                        : "-";

                    echo "<tr>
                        <td>{$r['house_number']}</td>
                        <td>{$r['issue']}</td>
                        <td>{$r['request_date']}</td>
                        <td><span class='badge bg-$badge'>{$r['status']}</span></td>
                        <td>$btn</td>
                    </tr>";
                }
                ?>

                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include "../config/footer.php"; ?>
