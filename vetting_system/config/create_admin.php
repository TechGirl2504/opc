<?php
include __DIR__ . '/config/db.php';

$username = 'admin';
$password = '12345';

// Hash the password
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $hash);
$stmt->execute();

if($stmt->affected_rows > 0){
    echo "Admin user created successfully!";
} else {
    echo "Admin already exists or error!";
}
?>