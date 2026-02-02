<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: index.php");
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get recent complaints
$sql = "SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$recent_complaints = $result->fetch_all(MYSQLI_ASSOC);

// Get complaint counts
$counts = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'resolved' => 0
];

$count_sql = "SELECT status, COUNT(*) as count FROM complaints WHERE user_id = ? GROUP BY status";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();

while ($row = $count_result->fetch_assoc()) {
    $counts[$row['status']] = $row['count'];
    $counts['total'] += $row['count'];
}

$stmt->close();
$count_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
        <h1 style="margin: 20px 0;">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
        
        <!-- Profile Info -->
        <div class="card" style="margin-bottom: 20px;">
            <h2><i class="fas fa-user-circle"></i> Profile Information</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <div>
                    <strong>Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?>
                </div>
                <div>
                    <strong>Student ID:</strong> <?php echo htmlspecialchars($user['student_id'] ?? 'N/A'); ?>
                </div>
                <div>
                    <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                </div>
                <div>
                    <strong>Faculty:</strong> <?php echo htmlspecialchars($user['faculty'] ?? 'N/A'); ?>
                </div>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $counts['total']; ?></div>
                <div class="stat-label">Total Complaints</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $counts['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $counts['in_progress']; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $counts['resolved']; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
        
        <!-- Recent Complaints -->
        <div class="card">
            <h2><i class="fas fa-history"></i> Recent Complaints</h2>
            
            <?php if (count($recent_complaints) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_complaints as $complaint): ?>
                                <tr>
                                    <td>#<?php echo $complaint['id']; ?></td>
                                    <td><?php echo htmlspecialchars($complaint['title']); ?></td>
                                    <td><?php echo htmlspecialchars($complaint['category']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $complaint['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                    <td>
                                        <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="my_complaints.php" class="btn">View All Complaints</a>
                </div>
            <?php else: ?>
                <p>You haven't submitted any complaints yet.</p>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="submit_complaint.php" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Submit Your First Complaint
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div style="display: flex; gap: 15px; margin-top: 15px;">
                <a href="submit_complaint.php" class="btn btn-success" style="flex: 1;">
                    <i class="fas fa-plus-circle"></i> New Complaint
                </a>
                <a href="profile.php" class="btn" style="flex: 1;">
                    <i class="fas fa-user-edit"></i> Edit Profile
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