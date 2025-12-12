<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$message = '';

if(isset($_POST['submit'])) {
    $full_name = trim($_POST['full_name']);
    $national_id = trim($_POST['national_id']);
    $current_name = trim($_POST['current_name']);
    $requested_name = trim($_POST['requested_name']);
    $reason = trim($_POST['reason']);
    $created_by = $_SESSION['user_id'];
    $status = 'pending';

    // Handle file upload
    $document_path = NULL;
    if(isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        $upload_dir = 'uploads/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $filename = time().'_'.basename($_FILES['document']['name']);
        $target_file = $upload_dir . $filename;

        $allowed = ['pdf','jpg','jpeg','png'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if(in_array($file_ext, $allowed)) {
            if(move_uploaded_file($_FILES['document']['tmp_name'], $target_file)) {
                $document_path = $target_file;
            } else {
                $message = "Failed to upload document.";
            }
        } else {
            $message = "Invalid file type. Only PDF, JPG, JPEG, PNG allowed.";
        }
    }

    if(!$message) {
        $stmt = $conn->prepare("INSERT INTO clients 
            (full_name, national_id, current_name, requested_name, reason, document_path, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $full_name, $national_id, $current_name, $requested_name, $reason, $document_path, $status, $created_by);

        if($stmt->execute()){
            $message = "Client request added successfully!";
        } else {
            $message = "Error: ".$conn->error;
        }
    }
}
?>

<div class="container">
    <h3 class="text-primary mb-4">Register New Client</h3>

    <?php if($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">National ID (optional)</label>
                    <input type="text" name="national_id" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Current Name</label>
                    <input type="text" name="current_name" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Requested Name</label>
                    <input type="text" name="requested_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason for Name Change</label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Upload Document (PDF, JPG, PNG)</label>
                    <input type="file" name="document" class="form-control">
                </div>
                <button class="btn btn-primary" name="submit">Add Client</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

