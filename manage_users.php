<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}

$conn = getDBConnection();

$error = '';
$success = '';

// Handle user deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Check if it's not the current admin
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        $delete_sql = "DELETE FROM users WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            $success = "User deleted successfully!";
        } else {
            $error = "Failed to delete user: " . $delete_stmt->error;
        }
        $delete_stmt->close();
    }
}

// Handle user registration (admin adding new user)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $student_id = $_POST['student_id'];
    $faculty = $_POST['faculty'];
    $year = $_POST['year'];
    $req_num = $_POST['req_num'];
    $user_type = $_POST['user_type'];
    
    // Check if username/email already exists
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Username or email already exists!";
    } else {
        // Insert new user
        $insert_sql = "INSERT INTO users (username, password, email, full_name, student_id, faculty, year, req_num, user_type) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssssssss", $username, $password, $email, $full_name, $student_id, $faculty, $year, $req_num, $user_type);
        
        if ($insert_stmt->execute()) {
            $success = "User added successfully!";
        } else {
            $error = "Failed to add user: " . $insert_stmt->error;
        }
        
        $insert_stmt->close();
    }
    
    $check_stmt->close();
}

// Get all users
$users_sql = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = $conn->query($users_sql);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Count users by type
$stats_sql = "SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type";
$stats_result = $conn->query($stats_sql);
$user_stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $user_stats[$row['user_type']] = $row['count'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Complaint Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container header-container">
            <div class="logo">
                <img src="css/uniLogoi.png" alt="University Logo" class="logo-img">
                <span>University Complaint System - Admin Panel</span>
            </div>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="manage_complaints.php"><i class="fas fa-tasks"></i> Manage Complaints</a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <h1 style="margin: 20px 0;">Manage Users</h1>
        
        <!-- User Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($users); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo isset($user_stats['admin']) ? $user_stats['admin'] : 0; ?></div>
                <div class="stat-label">Admins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo isset($user_stats['student']) ? $user_stats['student'] : 0; ?></div>
                <div class="stat-label">Students</div>
            </div>
        </div>
        
        <!-- Add New User Form -->
        <div class="card">
            <h2><i class="fas fa-user-plus"></i> Add New User</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div class="form-group">
                        <label for="user_type">User Type</label>
                        <select id="user_type" name="user_type" class="form-control" required>
                            <option value="student">Student</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" name="student_id" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="faculty">Faculty</label>
                        <select id="faculty" name="faculty" class="form-control">
                            <option value="">Select Faculty</option>
                            <option value="Faculty of Technological Studies">Faculty of Technological Studies</option>
                            <option value="Faculty of Business Studies">Faculty of Business Studies</option>
                            <option value="Faculty of Applied Sciences">Faculty of Applied Sciences</option>
                            <option value="Faculty of Humanities">Faculty of Humanities</option>
                            <option value="Faculty of Medicine">Faculty of Medicine</option>
                            <option value="Faculty of Engineering">Faculty of Engineering</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="year">Academic Year</label>
                        <select id="year" name="year" class="form-control">
                            <option value="">Select Year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                            <option value="Graduate">Graduate</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="req_num">Registration Number</label>
                        <input type="text" id="req_num" name="req_num" class="form-control">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" name="add_user" class="btn btn-success" style="width: 100%;">
                        <i class="fas fa-user-plus"></i> Add User
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="card">
            <h2><i class="fas fa-users"></i> All Users (<?php echo count($users); ?>)</h2>
            
            <?php if (count($users) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Student ID</th>
                                <th>User Type</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['student_id'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $user['user_type'] == 'admin' ? 'status-in-progress' : 'status-pending'; ?>">
                                            <?php echo ucfirst($user['user_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="manage_users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        <?php else: ?>
                                            <span class="btn btn-sm" style="background-color: #ccc; cursor: not-allowed;">
                                                <i class="fas fa-user-shield"></i> Current
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No users found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> University Complaint Management System | Group 10</p>
        </div>
    </footer>
</body>
</html>