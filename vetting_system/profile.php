<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$user_id = $_SESSION['user_id'];
$message = "";

// Handle profile picture upload
if(isset($_POST['upload'])){
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0){
        $filename = time() . '_' . basename($_FILES['profile_picture']['name']);
        $target_dir = "uploads/profile_pics/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . $filename;

        if(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)){
            $update = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $update->bind_param("si", $target_file, $user_id);
            if($update->execute()){
                $_SESSION['profile_picture'] = $target_file;
                $message = "✅ Profile picture updated successfully!";
            } else {
                $message = "❌ Error updating profile picture.";
            }
        } else {
            $message = "⚠️ Failed to upload file.";
        }
    } else {
        $message = "⚠️ Please select an image.";
    }
}

// Handle removing profile picture
if(isset($_POST['remove'])){
    $defaultPic = 'assets/default.png';
    $update = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $update->bind_param("si", $defaultPic, $user_id);
    if($update->execute()){
        $_SESSION['profile_picture'] = $defaultPic;
        $message = "✅ Profile picture removed successfully!";
    } else {
        $message = "❌ Error removing profile picture.";
    }
}

// Fetch user info from database to refresh sidebar and page data
$stmt = $conn->prepare("SELECT username, role, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Refresh session data (fixes disappearing profile after re-login)
$_SESSION['profile_picture'] = $user['profile_picture'] ?? 'assets/default.png';
?>

<style>
    .profile-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 100px);
        background-color: #f8f9fa;
    }

    .profile-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 450px;
        padding: 30px;
        transition: transform 0.2s ease-in-out;
    }

    .profile-card:hover {
        transform: translateY(-3px);
    }

    .profile-picture {
        position: relative;
        display: inline-block;
    }

    .profile-picture img {
        border-radius: 50%;
        border: 4px solid #0d6efd;
        object-fit: cover;
        width: 130px;
        height: 130px;
        transition: 0.3s ease;
    }

    .profile-picture:hover img {
        opacity: 0.85;
    }

    .profile-picture label {
        position: absolute;
        bottom: 0;
        right: 0;
        background: #0d6efd;
        border-radius: 50%;
        padding: 8px;
        color: white;
        cursor: pointer;
    }

    input[type="file"] {
        display: none;
    }

    .btn-primary {
        background: #0d6efd;
        border: none;
        font-weight: 500;
    }

    .btn-primary:hover {
        background: #0b5ed7;
    }

    .btn-outline-danger {
        border-radius: 8px;
        font-weight: 500;
    }

    .alert {
        border-radius: 10px;
    }
</style>

<div class="profile-wrapper">
    <div class="profile-card text-center">
        <h4 class="text-primary mb-4 fw-bold">My Profile</h4>

        <?php if($message): ?>
            <div class="alert alert-info py-2"><?= $message ?></div>
        <?php endif; ?>

        <div class="profile-picture mb-3">
            <img src="<?= $user['profile_picture'] && $user['profile_picture'] != '' ? htmlspecialchars($user['profile_picture']) : 'assets/default.png'; ?>" 
                 alt="Profile Picture" id="profilePreview">
            <label for="profileInput">
                <i class="bi bi-camera-fill"></i>
            </label>
        </div>

        <h5 class="mb-1"><?= htmlspecialchars($user['username']) ?></h5>
        <p class="text-muted mb-4"><?= ucfirst($user['role']) ?></p>

        <!-- Upload new picture -->
        <form method="POST" enctype="multipart/form-data" class="text-start">
            <input type="file" id="profileInput" name="profile_picture" accept="image/*" required>
            <button type="submit" name="upload" class="btn btn-primary w-100">Update Profile Picture</button>
        </form>

        <!-- Remove picture -->
        <?php if($user['profile_picture'] && $user['profile_picture'] != 'assets/default.png'): ?>
            <form method="POST" class="mt-3">
                <button type="submit" name="remove" class="btn btn-outline-danger w-100">
                    <i class="bi bi-trash"></i> Remove Profile Picture
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    // Preview selected image before upload
    document.getElementById('profileInput').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            document.getElementById('profilePreview').src = URL.createObjectURL(file);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>


