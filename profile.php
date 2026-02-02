<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $student_id = $_POST['student_id'];
    $faculty = $_POST['faculty'];
    $year = $_POST['year'];
    $req_num = $_POST['req_num'];
    
    // Handle password update if provided
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET full_name = ?, email = ?, student_id = ?, faculty = ?, year = ?, req_num = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssi", $full_name, $email, $student_id, $faculty, $year, $req_num, $hashed_password, $user_id);
        } else {
            $error = "Passwords do not match!";
        }
    } else {
        $sql = "UPDATE users SET full_name = ?, email = ?, student_id = ?, faculty = ?, year = ?, req_num = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $full_name, $email, $student_id, $faculty, $year, $req_num, $user_id);
    }
    
    if (empty($error)) {
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $success = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get current user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container header-container">
            <div class="logo">
                <img src="css/uniLogoi.png" alt="University Logo" class="logo-img">
                <span>University Complaint System</span>
            </div>
            <nav>
                <ul>
                    <li><a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="my_complaints.php"><i class="fas fa-list"></i> My Complaints</a></li>
                    <li><a href="submit_complaint.php"><i class="fas fa-plus-circle"></i> New Complaint</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="card form-container">
            <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" class="form-control" disabled value="<?php echo htmlspecialchars($user['username']); ?>">
                    <small>Username cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" class="form-control" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="faculty">Faculty</label>
                    <select id="faculty" name="faculty" class="form-control">
                        <option value="">Select Faculty</option>
                        <option value="Faculty of Technology" <?php echo ($user['faculty'] ?? '') == 'Faculty of Technology' ? 'selected' : ''; ?>>Faculty of Technology</option>
                        <option value="Faculty of Business" <?php echo ($user['faculty'] ?? '') == 'Faculty of Business' ? 'selected' : ''; ?>>Faculty of Business</option>
                        <option value="Faculty of Science" <?php echo ($user['faculty'] ?? '') == 'Faculty of Science' ? 'selected' : ''; ?>>Faculty of Science</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="year">Academic Year</label>
                    <select id="year" name="year" class="form-control">
                        <option value="">Select Year</option>
                        <option value="1st Year" <?php echo ($user['year'] ?? '') == '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2nd Year" <?php echo ($user['year'] ?? '') == '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3rd Year" <?php echo ($user['year'] ?? '') == '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4th Year" <?php echo ($user['year'] ?? '') == '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="req_num">Registration Number</label>
                    <input type="text" id="req_num" name="req_num" class="form-control" value="<?php echo htmlspecialchars($user['req_num'] ?? ''); ?>">
                </div>
                
                <hr style="margin: 20px 0;">
                
                <h3>Change Password (Optional)</h3>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control">
                    <small>Leave blank to keep current password</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-success" style="width: 100%;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="student_dashboard.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> University Complaint Management System</p>
        </div>
    </footer>
</body>
</html>