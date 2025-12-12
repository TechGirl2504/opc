<?php
session_start();
if(!isset($_SESSION['user_id'])) header("Location: login.php");
include 'config/db.php';

$id = $_POST['id'];
$reason = $_POST['denial_reason'];

$stmt = $conn->prepare("UPDATE clients SET status='denied', denial_reason=? WHERE id=?");
$stmt->bind_param("si", $reason, $id);
$stmt->execute();

header("Location: pending.php");
exit;
?>
