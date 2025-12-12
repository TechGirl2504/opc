<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

include 'config/db.php';

if(isset($_GET['id'])){
    $id = (int)$_GET['id'];
    $vetter_id = $_SESSION['user_id'];

    mysqli_query($conn, "UPDATE clients SET assigned_vetter_id=$vetter_id WHERE id=$id AND (assigned_vetter_id IS NULL OR assigned_vetter_id=$vetter_id)");

    header("Location: pending.php");
}
