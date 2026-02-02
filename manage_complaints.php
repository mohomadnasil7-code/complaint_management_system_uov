<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}

$conn = getDBConnection();

// Handle filtering
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build WHERE clause
$where_clauses = [];
$params = [];
$types = '';

if (!empty($status_filter) && $status_filter != 'all') {
    $where_clauses[] = "c.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($category_filter) && $category_filter != 'all') {
    $where_clauses[] = "c.category = ?";
    $params[] = $category_filter;
    $types .= 's';
}

if (!empty($search)) {
    $where_clauses[] = "(c.title LIKE ? OR c.description LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Get complaints with filters
$sql = "SELECT c.*, u.full_name, u.student_id, u.email 
        FROM complaints c 
        JOIN users u ON c.user_id = u.id 
        $where_sql
        ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$complaints = $result->fetch_all(MYSQLI_ASSOC);

// Get counts for stats
$count_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM complaints";
$count_result = $conn->query($count_sql);
$counts = $count_result->fetch_assoc();

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints - Complaint Management System</title>
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
        <h1 style="margin: 20px 0;">Manage Complaints</h1>
        
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $counts['total']; ?></div>
                <div class="stat-label">Total</div>
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
        
        <!-- Filter Form -->
        <div class="card">
            <h2><i class="fas fa-filter"></i> Filter Complaints</h2>
            <form method="GET" action="">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="all" <?php echo $status_filter == 'all' || empty($status_filter) ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="form-control">
                            <option value="all" <?php echo $category_filter == 'all' || empty($category_filter) ? 'selected' : ''; ?>>All Categories</option>
                            <option value="Academic" <?php echo $category_filter == 'Academic' ? 'selected' : ''; ?>>Academic</option>
                            <option value="Facilities" <?php echo $category_filter == 'Facilities' ? 'selected' : ''; ?>>Facilities</option>
                            <option value="Hostel" <?php echo $category_filter == 'Hostel' ? 'selected' : ''; ?>>Hostel</option>
                            <option value="Library" <?php echo $category_filter == 'Library' ? 'selected' : ''; ?>>Library</option>
                            <option value="Cafeteria" <?php echo $category_filter == 'Cafeteria' ? 'selected' : ''; ?>>Cafeteria</option>
                            <option value="Transport" <?php echo $category_filter == 'Transport' ? 'selected' : ''; ?>>Transport</option>
                            <option value="Other" <?php echo $category_filter == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" class="form-control" placeholder="Search by title, description, or student name" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                
                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="manage_complaints.php" class="btn">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Complaints Table -->
        <div class="card">
            <h2><i class="fas fa-list-alt"></i> All Complaints (<?php echo count($complaints); ?>)</h2>
            
            <?php if (count($complaints) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Student</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $complaint): ?>
                                <tr>
                                    <td>#<?php echo $complaint['id']; ?></td>
                                    <td><?php echo htmlspecialchars($complaint['title']); ?></td>
                                    <td><?php echo htmlspecialchars($complaint['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($complaint['category']); ?></td>
                                    <td>
                                        <span class="priority-badge priority-<?php echo $complaint['priority']; ?>">
                                            <?php echo ucfirst($complaint['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $complaint['status']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                                    <td>
                                        <a href="manage_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm">
                                            <i class="fas fa-edit"></i> Manage
                                        </a>
                                        <a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No complaints found.</p>
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