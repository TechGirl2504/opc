<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit;
}

include 'config/db.php';
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';

$message = '';

// -------------------- Add Vetter --------------------
if(isset($_POST['add_vetter'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if($username && $password){
        // Check duplicate username
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username=?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();
        if($stmt_check->num_rows > 0){
            $message = "Username already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'vetter')");
            $stmt->bind_param("ss", $username, $hashed_password);
            if($stmt->execute()){
                $message = "Vetter added successfully!";
            } else {
                $message = "Error: ".$conn->error;
            }
        }
    } else {
        $message = "Please fill in all fields.";
    }
}

// -------------------- Delete Vetter --------------------
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    if($id != $_SESSION['user_id']){
        $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='vetter'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: vetter_management.php");
        exit;
    } else {
        $message = "You cannot delete yourself!";
    }
}

// -------------------- Edit Vetter --------------------
$vetter_to_edit = null;
if(isset($_GET['edit'])){
    $edit_id = (int)$_GET['edit'];
    $stmt_edit = $conn->prepare("SELECT * FROM users WHERE id=? AND role='vetter'");
    $stmt_edit->bind_param("i", $edit_id);
    $stmt_edit->execute();
    $vetter_to_edit = $stmt_edit->get_result()->fetch_assoc();
}

if(isset($_POST['update_vetter'])){
    $edit_id = (int)$_POST['edit_id'];
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);
    if($new_username && $new_password){
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update = $conn->prepare("UPDATE users SET username=?, password=? WHERE id=? AND role='vetter'");
        $stmt_update->bind_param("ssi", $new_username, $hashed_password, $edit_id);
        if($stmt_update->execute()){
            $message = "Vetter updated successfully!";
            header("Location: vetter_management.php");
            exit;
        } else {
            $message = "Error: ".$conn->error;
        }
    } else {
        $message = "Please fill in all fields for update.";
    }
}

// -------------------- Fetch all vetters --------------------
$vetters_res = mysqli_query($conn,"SELECT * FROM users WHERE role='vetter' ORDER BY id DESC");
?>

<div class="container mt-4">
    <h3 class="text-primary mb-4">Manage Vetters</h3>

    <?php if($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <!-- Button trigger Add Vetter Modal -->
    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addVetterModal">
      Add New Vetter
    </button>

    <!-- Vetters List -->
    <div class="card shadow-sm">
        <div class="card-header bg-black text-white">Existing Vetters</div>
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($vetters_res) > 0): ?>
                        <?php while($vetter = mysqli_fetch_assoc($vetters_res)): ?>
                            <tr>
                                <td><?= $vetter['id'] ?></td>
                                <td><?= htmlspecialchars($vetter['username']) ?></td>
                                <td><?= $vetter['created_at'] ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editVetterModal<?= $vetter['id'] ?>">Edit</button>
                                    <a href="vetter_management.php?delete=<?= $vetter['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>

                            <!-- Edit Vetter Modal -->
                            <div class="modal fade" id="editVetterModal<?= $vetter['id'] ?>" tabindex="-1" aria-labelledby="editVetterLabel<?= $vetter['id'] ?>" aria-hidden="true">
                              <div class="modal-dialog">
                                <div class="modal-content">
                                  <div class="modal-header bg-warning text-dark">
                                    <h5 class="modal-title" id="editVetterLabel<?= $vetter['id'] ?>">Edit Vetter</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                  </div>
                                  <div class="modal-body">
                                    <form method="POST">
                                        <input type="hidden" name="edit_id" value="<?= $vetter['id'] ?>">
                                        <div class="mb-3">
                                            <label>Username</label>
                                            <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($vetter['username']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>New Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="edit_password<?= $vetter['id'] ?>" name="password" required>
                                                <button type="button" class="btn btn-outline-secondary" onclick="toggleEditPassword(<?= $vetter['id'] ?>)">Show</button>
                                            </div>
                                        </div>
                                        <button name="update_vetter" class="btn btn-primary">Update Vetter</button>
                                    </form>
                                  </div>
                                </div>
                              </div>
                            </div>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No vetters added yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Vetter Modal -->
<div class="modal fade" id="addVetterModal" tabindex="-1" aria-labelledby="addVetterLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="addVetterLabel">Add New Vetter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" class="form-control" name="username" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">Show</button>
                </div>
            </div>
            <button class="btn btn-success" name="add_vetter">Add Vetter</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function togglePassword(){
    const passInput = document.getElementById('password');
    passInput.type = (passInput.type === 'password') ? 'text' : 'password';
}

function toggleEditPassword(id){
    const passInput = document.getElementById('edit_password' + id);
    passInput.type = (passInput.type === 'password') ? 'text' : 'password';
}
</script>

<?php include 'includes/footer.php'; ?>

