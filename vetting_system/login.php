<?php
session_start();
include 'config/db.php';

$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if($user = $res->fetch_assoc()){
        if(password_verify($password, $user['password'])){
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_picture'] = $user['profile_picture'] ?? 'assets/default.png';


            // Redirect based on role
            if($user['role'] === 'admin'){
                header("Location: dashboard.php");
            } else {
                header("Location: pending.php");
            }
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container d-flex justify-content-center align-items-center" style="height:80vh;">
    <div class="card p-4 shadow" style="width: 400px;">
        <h3 class="text-center mb-4 text-primary">IMS</h3>
        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


