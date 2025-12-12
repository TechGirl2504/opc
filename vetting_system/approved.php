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

// Pagination
$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Count total approved
$total_sql = "SELECT COUNT(*) as total FROM clients c JOIN decisions d ON c.id=d.client_id AND d.decision='approved'";
$total_result = $conn->query($total_sql)->fetch_assoc();
$total_pages = ceil($total_result['total'] / $limit);

// Fetch approved clients
$sql = "SELECT c.*, u.username as creator, v.username as vetter, d.decided_at
        FROM clients c
        JOIN decisions d ON c.id=d.client_id AND d.decision='approved'
        LEFT JOIN users u ON c.created_by=u.id
        LEFT JOIN users v ON d.vetter_id=v.id
        ORDER BY d.decided_at DESC
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $start, $limit);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
    <h3 class="text-success mb-3">Approved Requests</h3>

    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Current Name</th>
                <th>Requested Name</th>
                <th>Reason</th>
                <th>Document</th>
                <th>Created By</th>
                <th>Approved By</th>
                <th>Approved At</th>
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
                    <td><?= htmlspecialchars($row['vetter']) ?></td>
                    <td><?= $row['decided_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9" class="text-center">No approved requests found.</td></tr>
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



