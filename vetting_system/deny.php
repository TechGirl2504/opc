<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

include 'config/db.php';

if(isset($_GET['id'])){
    $id = (int)$_GET['id'];

    if($_SERVER['REQUEST_METHOD']=='POST'){
        $deny_reason = trim($_POST['deny_reason']);
        mysqli_query($conn, "UPDATE clients SET status='denied', deny_reason='".mysqli_real_escape_string($conn,$deny_reason)."', assigned_vetter_id=".$_SESSION['user_id']." WHERE id=$id");
        header("Location: pending.php");
        exit;
    }
}
?>

<div class="container mt-5">
    <h3 class="text-danger">Provide Denial Reason</h3>
    <form method="POST">
        <div class="mb-3">
            <textarea name="deny_reason" class="form-control" rows="4" required></textarea>
        </div>
        <button class="btn btn-danger">Submit Denial</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
