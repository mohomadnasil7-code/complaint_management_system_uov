<?php
require_once 'config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_complaints.php");
    exit();
}

$conn = getDBConnection();
$complaint_id = $_GET['id'];
$admin_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Get complaint details
$sql = "SELECT c.*, u.full_name, u.student_id, u.email 
        FROM complaints c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: manage_complaints.php");
    exit();
}

$complaint = $result->fetch_assoc();
$stmt->close();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $admin_response = $_POST['admin_response'];
    
    // Update complaint status
    $update_sql = "UPDATE complaints SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_status, $complaint_id);
    
    if ($update_stmt->execute()) {
        // Add response if provided
        if (!empty(trim($admin_response))) {
            $response_sql = "INSERT INTO complaint_responses (complaint_id, admin_id, response) VALUES (?, ?, ?)";
            $response_stmt = $conn->prepare($response_sql);
            $response_stmt->bind_param("iis", $complaint_id, $admin_id, $admin_response);
            $response_stmt->execute();
            $response_stmt->close();
        }
        
        $success = "Complaint status updated successfully!";
        // Refresh complaint data
        $sql = "SELECT c.*, u.full_name, u.student_id, u.email 
                FROM complaints c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $complaint_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $complaint = $result->fetch_assoc();
        $stmt->close();
    } else {
        $error = "Failed to update complaint: " . $update_stmt->error;
    }
    $update_stmt->close();
}

// Get responses
$responses = [];
$response_sql = "SELECT cr.*, u.full_name 
                 FROM complaint_responses cr 
                 JOIN users u ON cr.admin_id = u.id 
                 WHERE cr.complaint_id = ? 
                 ORDER BY cr.created_at DESC";
$response_stmt = $conn->prepare($response_sql);
$response_stmt->bind_param("i", $complaint_id);
$response_stmt->execute();
$response_result = $response_stmt->get_result();
$responses = $response_result->fetch_all(MYSQLI_ASSOC);
$response_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaint</title>
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
                    <li><a href="manage_complaints.php"><i class="fas fa-tasks"></i> Complaints</a></li>
                    <li><a href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="manage_complaints.php" class="btn">
                <i class="fas fa-arrow-left"></i> Back to Complaints
            </a>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-edit"></i> Manage Complaint #<?php echo $complaint['id']; ?></h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Complaint Details -->
            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3>Complaint Details</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                    <div><strong>Title:</strong> <?php echo htmlspecialchars($complaint['title']); ?></div>
                    <div><strong>Category:</strong> <?php echo htmlspecialchars($complaint['category']); ?></div>
                    <div>
                        <strong>Status:</strong> 
                        <span class="status-badge status-<?php echo str_replace('_', '-', $complaint['status']); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                        </span>
                    </div>
                    <div>
                        <strong>Priority:</strong> 
                        <span class="priority-badge priority-<?php echo $complaint['priority']; ?>">
                            <?php echo ucfirst($complaint['priority']); ?>
                        </span>
                    </div>
                    <div><strong>Submitted:</strong> <?php echo date('M d, Y H:i', strtotime($complaint['created_at'])); ?></div>
                    <div><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></div>
                </div>
            </div>
            
            <!-- Student Information -->
            <div style="background: #f0f7ff; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3>Student Information</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                    <div><strong>Name:</strong> <?php echo htmlspecialchars($complaint['full_name']); ?></div>
                    <div><strong>Student ID:</strong> <?php echo htmlspecialchars($complaint['student_id'] ?? 'N/A'); ?></div>
                    <div><strong>Email:</strong> <?php echo htmlspecialchars($complaint['email']); ?></div>
                </div>
            </div>
            
            <!-- Update Status Form -->
            <div style="margin-bottom: 30px;">
                <h3>Update Status</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="pending" <?php echo $complaint['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $complaint['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $complaint['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="rejected" <?php echo $complaint['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_response">Admin Response</label>
                        <textarea id="admin_response" name="admin_response" class="form-control" rows="4" placeholder="Enter your response to the student..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="update_status" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Complaint
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Previous Responses -->
            <?php if (count($responses) > 0): ?>
                <div>
                    <h3>Previous Responses</h3>
                    <?php foreach ($responses as $response): ?>
                        <div style="background: #fff; border-left: 4px solid #3f51b5; padding: 15px; margin-bottom: 10px; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <strong><?php echo htmlspecialchars($response['full_name']); ?></strong>
                                <small><?php echo date('M d, Y H:i', strtotime($response['created_at'])); ?></small>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($response['response'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> University Complaint Management System</p>
        </div>
    </footer>
</body>
</html>