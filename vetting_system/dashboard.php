<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';

// Fetch stats
$total = $pending = $approved = $denied = 0;

$total_res = mysqli_query($conn,"SELECT COUNT(*) AS c FROM clients");
$total = mysqli_fetch_assoc($total_res)['c'] ?? 0;

$pending_res = mysqli_query($conn,"SELECT COUNT(*) AS c FROM clients WHERE status='pending'");
$pending = mysqli_fetch_assoc($pending_res)['c'] ?? 0;

$approved_res = mysqli_query($conn,"SELECT COUNT(*) AS c FROM clients WHERE status='approved'");
$approved = mysqli_fetch_assoc($approved_res)['c'] ?? 0;

$denied_res = mysqli_query($conn,"SELECT COUNT(*) AS c FROM clients WHERE status='denied'");
$denied = mysqli_fetch_assoc($denied_res)['c'] ?? 0;
?>

<h3 class="text-primary mb-4">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h3>
<div class="row g-4">
    <div class="col-md-3">
        <div class="card shadow-sm text-center p-3" style="background-color:#e9f4fc;">
            <h6>Total Clients</h6>
            <h3><?= $total ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm text-center p-3 bg-info text-white">
            <h6>Pending</h6>
            <h3><?= $pending ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm text-center p-3 bg-success text-white">
            <h6>Approved</h6>
            <h3><?= $approved ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm text-center p-3 bg-danger text-white">
            <h6>Denied</h6>
            <h3><?= $denied ?></h3>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
