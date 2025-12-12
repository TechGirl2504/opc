<?php
include 'config/db.php';

// The vetter username to reset
$username = "Henita Kamponda"; // replace with actual username
$new_password = "Heni123";   // desired password

// Hash the password
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

// Update in database safely
$stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
$stmt->bind_param("ss", $hashed, $username);

if($stmt->execute()){
    echo "Password reset successfully for '$username'. New password is: $new_password";
} else {
    echo "Error: " . $stmt->error;
}
