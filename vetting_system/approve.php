<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

include 'config/db.php';

if(isset($_GET['id'])){
    $id = (int)$_GET['id'];
    mysqli_query($conn, "UPDATE clients SET status='approved', assigned_vetter_id=".$_SESSION['user_id']." WHERE id=$id");
    header("Location: pending.php");
}

?>
