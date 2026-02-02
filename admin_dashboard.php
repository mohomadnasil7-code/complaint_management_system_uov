<?php
session_start();
// Correct path to database.php - all files are in root directory
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}

$conn = getDBConnection();

// Get statistics
$total_complaints = $pending_complaints = $resolved_complaints = $total_users = 0;

// Get total complaints
$sql = "SELECT COUNT(*) as total FROM complaints";
$result = $conn->query($sql);
if ($result) {
    $total_complaints = $result->fetch_assoc()['total'];
}

// Get pending complaints
$sql = "SELECT COUNT(*) as pending FROM complaints WHERE status = 'pending'";
$result = $conn->query($sql);
if ($result) {
    $pending_complaints = $result->fetch_assoc()['pending'];
}

// Get resolved complaints
$sql = "SELECT COUNT(*) as resolved FROM complaints WHERE status = 'resolved'";
$result = $conn->query($sql);
if ($result) {
    $resolved_complaints = $result->fetch_assoc()['resolved'];
}

// Get total students
$sql = "SELECT COUNT(*) as users FROM users WHERE user_type = 'student'";
$result = $conn->query($sql);
if ($result) {
    $total_users = $result->fetch_assoc()['users'];
}

// Get recent complaints
$recent_complaints = [];
$sql = "SELECT c.*, u.full_name 
        FROM complaints c 
        JOIN users u ON c.user_id = u.id 
        ORDER BY c.created_at DESC 
        LIMIT 10";
$result = $conn->query($sql);
if ($result) {
    $recent_complaints = $result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();

// Add: build safe links to avoid 404s if files are missing
$required = [
    'manage_complaints' => 'manage_complaints.php',
    'manage_users'      => 'manage_users.php',
    'logout'            => 'logout.php',
    'submit_complaint'  => 'submit_complaint.php',
    'index'             => 'index.php'
];

$links = [];
$missing_files = [];
foreach ($required as $key => $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        $links[$key] = $file;
    } else {
        $links[$key] = '#';
        $missing_files[] = $file;
    }
}

// Prepare variants that include query strings only when target exists
$links['manage_complaints_pending'] = $links['manage_complaints'] !== '#' ? $links['manage_complaints'] . '?status=pending' : '#';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Temporary inline styles until CSS loads */
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
        .container { width: 90%; max-width: 1200px; margin: 0 auto; padding: 20px; }
        header { background: #1a237e; color: white; padding: 15px 0; }
        .header-container { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.8rem; font-weight: bold; display: flex; align-items: center; }
        .logo-img { height: 40px; margin-right: 12px; }
        nav ul { display: flex; list-style: none; padding: 0; margin: 0; }
        nav ul li { margin-left: 20px; }
        nav ul li a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; }
        nav ul li a:hover { background: rgba(255,255,255,0.1); }
        .card { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .stats-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 10px; padding: 25px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #3f51b5; margin-bottom: 10px; }
        .stat-label { font-size: 1rem; color: #666; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: bold; }
        .btn { display: inline-block; padding: 10px 20px; background: #3f51b5; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #303f9f; }
        footer { background: #1a237e; color: white; text-align: center; padding: 20px; margin-top: 40px; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-in-progress { background: #d1ecf1; color: #0c5460; }
        .status-resolved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .alert { padding: 15px; border-radius: 5px; margin: 15px 0; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
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
                    <li><a href="<?php echo htmlspecialchars($links['manage_complaints']); ?>"><i class="fas fa-tasks"></i> Complaints</a></li>
                    <li><a href="<?php echo htmlspecialchars($links['manage_users']); ?>"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="<?php echo htmlspecialchars($links['logout']); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <li><a href="#"><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></a></li>
                </ul>
            </nav>
        </div>
    </header>

    <?php if (!empty($missing_files)): ?>
        <div class="container">
            <div class="alert alert-error">
                <strong>Missing files:</strong> <?php echo htmlspecialchars(implode(', ', $missing_files)); ?>
                <p>Please create these files in the root directory.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="container">
        <h1 style="margin: 20px 0;">Admin Dashboard</h1>
        
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_complaints; ?></div>
                <div class="stat-label">Total Complaints</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_complaints; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $resolved_complaints; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Students</div>
            </div>
        </div>
        
        <!-- Recent Complaints -->
        <div class="card">
            <h2><i class="fas fa-history"></i> Recent Complaints</h2>
            
            <?php if (count($recent_complaints) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Student</th>
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
                                    <td><?php echo htmlspecialchars($complaint['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($complaint['category']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $complaint['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                    <td>
                                        <?php if ($links['manage_complaints'] !== '#'): ?>
                                            <a href="<?php echo htmlspecialchars($links['manage_complaints'] . '?id=' . $complaint['id']); ?>" class="btn">
                                                <i class="fas fa-edit"></i> Manage
                                            </a>
                                        <?php else: ?>
                                            <span class="btn" style="background-color: #ccc; cursor: not-allowed;">
                                                <i class="fas fa-edit"></i> Manage
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($links['manage_complaints'] !== '#'): ?>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="<?php echo htmlspecialchars($links['manage_complaints']); ?>" class="btn">
                            <i class="fas fa-tasks"></i> View All Complaints
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p>No complaints found.</p>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div style="display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap;">
                <?php if ($links['manage_complaints_pending'] !== '#'): ?>
                    <a href="<?php echo htmlspecialchars($links['manage_complaints_pending']); ?>" class="btn" style="background-color: #ff9800;">
                        <i class="fas fa-clock"></i> View Pending
                    </a>
                <?php endif; ?>
                
                <?php if ($links['manage_users'] !== '#'): ?>
                    <a href="<?php echo htmlspecialchars($links['manage_users']); ?>" class="btn">
                        <i class="fas fa-users-cog"></i> Manage Users
                    </a>
                <?php endif; ?>
                
                <?php if ($links['submit_complaint'] !== '#'): ?>
                    <a href="<?php echo htmlspecialchars($links['submit_complaint']); ?>" class="btn" style="background-color: #4caf50;">
                        <i class="fas fa-plus-circle"></i> Add Complaint
                    </a>
                <?php endif; ?>
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