<div class="d-flex">
    <nav class="bg-primary sidebar flex-column p-3 text-white vh-100" style="width:250px;">
        <?php
            include 'config/db.php';
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                
                // Fetch the latest user info from the database
                $stmt = $conn->prepare("SELECT username, role, profile_picture FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                // Determine which picture to show
                $profilePic = !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'uploads/profile_pics/default.jpg';
                $username = htmlspecialchars($user['username']);
                $role = htmlspecialchars(ucfirst($user['role']));
            }
        ?>

        <!-- Profile Section -->
        <div class="text-center mb-4 position-relative">
            <div class="profile-pic-wrapper position-relative d-inline-block">
                <img src="<?= $profilePic ?>" alt="Profile Picture" 
                     class="rounded-circle mb-2 border border-light" width="90" height="90"
                     style="object-fit: cover;">

                <!-- Small camera icon overlay -->
                <a href="profile.php" class="position-absolute bottom-0 end-0 bg-light text-primary rounded-circle p-1"
                   style="transform: translate(25%, 25%);">
                    <i class="bi bi-camera-fill"></i>
                </a>
            </div>
            <h5 class="mb-0 mt-2"><?= $username ?></h5>
            <small class="text-light"><?= $role ?></small>
            <hr class="bg-light">
        </div>

        <!-- Sidebar Menu -->
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a class="nav-link text-white" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link text-white" href="pending.php">
                    <i class="bi bi-hourglass-split me-2"></i> Pending
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link text-white" href="approved.php">
                    <i class="bi bi-check-circle me-2"></i> Approved
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link text-white" href="denied.php">
                    <i class="bi bi-x-circle me-2"></i> Denied
                </a>
            </li>

            <?php if (!empty($user['role']) && $user['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link text-white" href="vetter_management.php">
                    <i class="bi bi-people me-2"></i> Vetters
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-item mt-auto">
                <a class="nav-link text-white" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Page content area -->
    <div class="flex-grow-1 p-4">



