<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';

$vetter_id = $_SESSION['user_id'];
$message = '';

// Handle Add Client
if(isset($_POST['add_client'])){
    $full_name = trim($_POST['full_name']);
    $national_id = trim($_POST['national_id']);
    $current_name = trim($_POST['current_name']);
    $requested_name = trim($_POST['requested_name']);
    $reason = trim($_POST['reason']);

    if(!preg_match('/^[A-Z0-9]{8}$/', $national_id)){
        $message = "National ID must be 8 characters with uppercase letters and numbers only.";
    } else {
        $document_path = null;
        if(isset($_FILES['document']) && $_FILES['document']['error'] == 0){
            $filename = time().'_'.basename($_FILES['document']['name']);
            $target_dir = "uploads/";
            $target_file = $target_dir . $filename;
            if(move_uploaded_file($_FILES['document']['tmp_name'], $target_file)){
                $document_path = $target_file;
            }
        }

        $stmt = $conn->prepare("INSERT INTO clients (full_name, national_id, current_name, requested_name, reason, status, created_by, document_path) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->bind_param("sssssis", $full_name, $national_id, $current_name, $requested_name, $reason, $vetter_id, $document_path);
        $stmt->execute() ? $message = "Client added successfully!" : $message = "Error: ".$stmt->error;
    }
}

// Handle Approve
if(isset($_POST['approve_id'])){
    $client_id = (int)$_POST['approve_id'];
    $check = $conn->prepare("SELECT status FROM clients WHERE id=?");
    $check->bind_param("i", $client_id);
    $check->execute();
    $status = $check->get_result()->fetch_assoc()['status'];

    if($status === 'pending'){
        $stmt = $conn->prepare("INSERT INTO decisions (client_id, vetter_id, decision) VALUES (?, ?, 'approved')");
        $stmt->bind_param("ii", $client_id, $vetter_id);
        $stmt->execute();

        $stmt2 = $conn->prepare("UPDATE clients SET status='approved', assigned_vetter_id=? WHERE id=?");
        $stmt2->bind_param("ii", $vetter_id, $client_id);
        $stmt2->execute();

        $message = "Client approved successfully!";
    } else {
        $message = "This client has already been processed!";
    }
}

// Handle Deny
if(isset($_POST['deny_id'])){
    $client_id = (int)$_POST['deny_id'];
    $check = $conn->prepare("SELECT status FROM clients WHERE id=?");
    $check->bind_param("i", $client_id);
    $check->execute();
    $status = $check->get_result()->fetch_assoc()['status'];

    if($status === 'pending'){
        $deny_reason = trim($_POST['deny_reason']);
        $stmt = $conn->prepare("INSERT INTO decisions (client_id, vetter_id, decision, denial_reason) VALUES (?, ?, 'denied', ?)");
        $stmt->bind_param("iis", $client_id, $vetter_id, $deny_reason);
        $stmt->execute();

        $stmt2 = $conn->prepare("UPDATE clients SET status='denied', assigned_vetter_id=? WHERE id=?");
        $stmt2->bind_param("ii", $vetter_id, $client_id);
        $stmt2->execute();

        $message = "Client denied successfully!";
    } else {
        $message = "This client has already been processed!";
    }
}

// Pagination
$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$total_sql = "SELECT COUNT(*) as total FROM clients WHERE status='pending'";
$total_result = $conn->query($total_sql)->fetch_assoc();
$total_pages = ceil($total_result['total'] / $limit);

$sql = "SELECT c.*, u.username as creator FROM clients c 
        LEFT JOIN users u ON c.created_by=u.id 
        WHERE c.status='pending' 
        ORDER BY c.created_at DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $start, $limit);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
    <h3 class="text-primary mb-3">Pending Name Change Requests</h3>

    <?php if($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <!-- Add Client Button -->
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addClientModal">Add New Client</button>

    <!-- ✅ Add Client Modal -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add New Client</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>National ID</label>
                            <input type="text" name="national_id" class="form-control" pattern="[A-Z0-9]{8}" title="8 characters: uppercase letters and numbers only" required>
                        </div>
                        <div class="mb-3">
                            <label>Current Name</label>
                            <input type="text" name="current_name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Requested Name</label>
                            <input type="text" name="requested_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Reason</label>
                            <textarea name="reason" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Upload Document (optional)</label>
                            <input type="file" name="document" class="form-control">
                        </div>
                        <button name="add_client" class="btn btn-primary w-100">Add Client</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <table class="table table-striped table-hover">
        <thead class="table-primary">
            <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Current Name</th>
                <th>Requested Name</th>
                <th>Reason</th>
                <th>Document</th>
                <th>Created By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows > 0): $i=$start+1; ?>
                <?php while($row=$result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['current_name']) ?></td>
                        <td><?= htmlspecialchars($row['requested_name']) ?></td>
                        <td><?= htmlspecialchars($row['reason']) ?></td>
                        <td><?= $row['document_path'] ? "<a href='{$row['document_path']}' target='_blank'>View</a>" : 'N/A' ?></td>
                        <td><?= htmlspecialchars($row['creator']) ?></td>
                        <td>
                            <!-- Approve -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="approve_id" value="<?= $row['id'] ?>">
                                <button class="btn btn-success btn-sm">Approve</button>
                            </form>

                            <!-- Deny -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="deny_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="deny_reason" value="Rejected by vetter">
                                <button class="btn btn-danger btn-sm">Deny</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center">No pending requests found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <nav>
        <ul class="pagination">
            <?php for($p=1; $p<=$total_pages; $p++): ?>
                <li class="page-item <?= $p==$page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<?php include 'includes/footer.php'; ?>





