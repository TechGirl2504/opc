<?php
// Only run once to reset the password
include 'config/db.php';

$new_password = "admin123"; // The password you want to set
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

$username = "admin"; // The account to reset

$stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
$stmt->bind_param("ss", $hashed, $username);

if($stmt->execute()){
    echo "Password reset successfully for '$username'. New password is: $new_password";
} else {
    echo "Error: " . $stmt->error;
}
