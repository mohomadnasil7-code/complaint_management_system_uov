<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: " . ($_SESSION['user_type'] == 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'));
    exit();
}

$conn = getDBConnection();
$complaint_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get complaint details
if ($user_type == 'admin') {
    // Admin can view any complaint
    $sql = "SELECT c.*, u.full_name, u.student_id, u.email 
            FROM complaints c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $complaint_id);
} else {
    // Student can only view their own complaints
    $sql = "SELECT c.*, u.full_name, u.student_id, u.email 
            FROM complaints c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.id = ? AND c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $complaint_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    $conn->close();
    header("Location: " . ($user_type == 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'));
    exit();
}

$complaint = $result->fetch_assoc();
$stmt->close();

// Get responses
$responses = [];
$response_sql = "SELECT cr.*, u.full_name 
                 FROM complaint_responses cr 
                 JOIN users u ON cr.admin_id = u.id 
                 WHERE cr.complaint_id = ? 
                 ORDER BY cr.created_at ASC";
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
    <title>View Complaint</title>
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
                    <?php if ($user_type == 'admin'): ?>
                        <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="manage_complaints.php"><i class="fas fa-tasks"></i> Complaints</a></li>
                        <li><a href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
                        <li><a href="manage_complaint.php?id=<?php echo $complaint_id; ?>"><i class="fas fa-edit"></i> Manage</a></li>
                    <?php else: ?>
                        <li><a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="my_complaints.php"><i class="fas fa-list"></i> My Complaints</a></li>
                        <li><a href="submit_complaint.php"><i class="fas fa-plus-circle"></i> New Complaint</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="<?php echo $user_type == 'admin' ? 'manage_complaints.php' : 'my_complaints.php'; ?>" class="btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-eye"></i> Complaint #<?php echo $complaint['id']; ?></h2>
            
            <!-- Complaint Details -->
            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div>
                        <strong>Title:</strong> <?php echo htmlspecialchars($complaint['title']); ?>
                    </div>
                    <div>
                        <strong>Category:</strong> <?php echo htmlspecialchars($complaint['category']); ?>
                    </div>
                    <div>
                        <strong>Priority:</strong> 
                        <span class="priority-badge priority-<?php echo $complaint['priority']; ?>">
                            <?php echo ucfirst($complaint['priority']); ?>
                        </span>
                    </div>
                    <div>
                        <strong>Status:</strong> 
                        <span class="status-badge status-<?php echo str_replace('_', '-', $complaint['status']); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                        </span>
                    </div>
                    <div>
                        <strong>Submitted:</strong> <?php echo date('M d, Y H:i', strtotime($complaint['created_at'])); ?>
                    </div>
                    <div>
                        <strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($complaint['updated_at'])); ?>
                    </div>
                </div>
                
                <div>
                    <strong>Description:</strong>
                    <p style="background: white; padding: 15px; border-radius: 5px; margin-top: 10px;">
                        <?php echo nl2br(htmlspecialchars($complaint['description'])); ?>
                    </p>
                </div>
                
                <?php if ($complaint['attachment_path']): ?>
                    <div style="margin-top: 15px;">
                        <strong>Attachment:</strong>
                        <a href="<?php echo $complaint['attachment_path']; ?>" target="_blank" class="btn btn-sm">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Student Information -->
            <div style="background: #f0f7ff; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3><i class="fas fa-user-graduate"></i> Student Information</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                    <div>
                        <strong>Name:</strong> <?php echo htmlspecialchars($complaint['full_name']); ?>
                    </div>
                    <div>
                        <strong>Student ID:</strong> <?php echo htmlspecialchars($complaint['student_id'] ?? 'N/A'); ?>
                    </div>
                    <div>
                        <strong>Email:</strong> <?php echo htmlspecialchars($complaint['email']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Admin Responses -->
            <?php if (count($responses) > 0): ?>
                <div style="margin-bottom: 30px;">
                    <h3><i class="fas fa-comments"></i> Admin Responses</h3>
                    <?php foreach ($responses as $response): ?>
                        <div style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin-bottom: 10px; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <strong><?php echo htmlspecialchars($response['full_name']); ?> (Admin)</strong>
                                <small><?php echo date('M d, Y H:i', strtotime($response['created_at'])); ?></small>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($response['response'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> No admin responses yet.
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