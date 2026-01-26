<?php
include "../config/db.php";
include "../config/header.php";

$total = $conn->query("SELECT SUM(amount) AS total FROM payments")
              ->fetch_assoc()['total'];

$houses = $conn->query("SELECT COUNT(*) AS total FROM houses")
               ->fetch_assoc()['total'];

$occupied = $conn->query("SELECT COUNT(*) AS total FROM houses WHERE status='occupied'")
                 ->fetch_assoc()['total'];
?>

<div class="container mt-4">
    <h3>Reports & Analytics</h3>

    <p><b>Total Income:</b> KES <?php echo $total; ?></p>
    <p><b>Total Houses:</b> <?php echo $houses; ?></p>
    <p><b>Occupied Houses:</b> <?php echo $occupied; ?></p>

    <canvas id="chart" height="100"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chart'), {
    type: 'bar',
    data: {
        labels: ['Occupied', 'Vacant'],
        datasets: [{
            data: [<?php echo $occupied; ?>, <?php echo $houses - $occupied; ?>]
        }]
    }
});
</script>

<?php include "../config/footer.php"; ?>
