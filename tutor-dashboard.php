<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}
require 'db.php';

$tutor_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM tutors WHERE id = ?");
$stmt->execute([$tutor_id]);
$tutor = $stmt->fetch();

$stmt_stats = $conn->prepare("SELECT 
    COUNT(*) as total_sessions,
    SUM(CASE WHEN status = 'confirmed' AND session_date >= CURRENT_DATE THEN 1 ELSE 0 END) as upcoming_sessions_stat,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_sessions,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_sessions
    FROM bookings WHERE tutor_id = ?");
$stmt_stats->execute([$tutor_id]);
$stats = $stmt_stats->fetch();

$stmt_confirmed = $conn->prepare(
    "SELECT b.*, s.name as student_name, s.email as student_email 
     FROM bookings b 
     JOIN students s ON b.student_id = s.id
     WHERE b.tutor_id = ? AND b.status = 'confirmed' 
     ORDER BY b.session_date ASC, b.session_time ASC"
);
$stmt_confirmed->execute([$tutor_id]);
$bookings_confirmed = $stmt_confirmed->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Dashboard - Athena</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --accent: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-light: #e9ecef;
            --success: #38b000;
            --warning: #ffaa00;
            --info: #3a86ff;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark);
            line-height: 1.6;
            background-color: var(--light);
            padding-top: 76px;
        }
        
        /* Modern Navbar */
        .navbar {
            padding: 1rem 2rem;
            background: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            color: var(--primary);
            letter-spacing: -0.5px;
        }
        
        .nav-link {
            font-weight: 600;
            margin: 0 0.5rem;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary) !important;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: var(--primary);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after, .nav-link.active::after {
            width: 100%;
        }
        
        .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1rem;
        }
        
        .dropdown-item {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: var(--light);
            color: var(--primary);
            transform: translateX(5px);
        }
        
        /* Dashboard Styles */
        .dashboard-container {
            padding: 2rem 0;
        }
        
        .page-header {
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }
        
        .page-header h2 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        /* Stats Cards */
        .stats-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            padding: 1.5rem;
            height: 100%;
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
            position: relative;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.08);
        }
        
        .stats-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
        }
        
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stats-card p {
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 0;
        }
        
        .stats-icon {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 3rem;
            opacity: 0.1;
            color: var(--primary);
        }
        
        /* Session Card */
        .sessions-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .sessions-card .card-header {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 1.25rem 1.5rem;
        }
        
        .sessions-card .card-header h3 {
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
            color: var(--dark);
            display: flex;
            align-items: center;
        }
        
        .sessions-card .card-header h3 i {
            margin-right: 0.75rem;
            color: var(--primary);
        }
        
        .sessions-card .card-body {
            padding: 1.5rem;
        }
        
        .table-container {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            color: var(--dark);
            font-weight: 600;
            padding: 1rem;
            border-bottom: 2px solid #e9ecef;
            white-space: nowrap;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #e9ecef;
        }
        
        .badge {
            padding: 0.5rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge.bg-success {
            background: linear-gradient(135deg, var(--success), #2d9348) !important;
            box-shadow: 0 3px 8px rgba(56, 176, 0, 0.15);
        }
        
        .badge.bg-secondary {
            background: linear-gradient(135deg, #6c757d, #495057) !important;
            box-shadow: 0 3px 8px rgba(108, 117, 125, 0.15);
        }
        
        .badge.bg-info {
            background: linear-gradient(135deg, var(--info), #0072ff) !important;
            box-shadow: 0 3px 8px rgba(58, 134, 255, 0.15);
            color: white;
        }
        
        .badge.bg-warning {
            background: linear-gradient(135deg, var(--warning), #ff8800) !important;
            box-shadow: 0 3px 8px rgba(255, 170, 0, 0.15);
            color: white;
        }
        
        .btn {
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
        }
        
        .btn-primary:hover {
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-sm {
            padding: 0.35rem 0.9rem;
            font-size: 0.875rem;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .alert-info {
            background-color: rgba(58, 134, 255, 0.1);
            color: var(--info);
        }
        
        .no-sessions-message {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .no-sessions-message i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #e9ecef;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.75rem 1rem;
            }
            
            .page-header h2 {
                font-size: 1.8rem;
            }
            
            .stats-card h3 {
                font-size: 2rem;
            }
            
            .stats-card p {
                font-size: 0.9rem;
            }
            
            .sessions-card .card-header {
                padding: 1rem;
            }
            
            .sessions-card .card-header h3 {
                font-size: 1.3rem;
            }
            
            .sessions-card .card-body {
                padding: 1rem;
            }
            
            .table td, .table th {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="tutor-dashboard.php">Athena</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="tutor-dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tutor-schedule.php">My Sessions</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($tutor['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="tutor-profile.php">
                                <i class="fas fa-id-card me-2"></i> My Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container">
            <div class="page-header">
                <h2>Tutor Dashboard</h2>
                <p class="text-muted">Welcome back, <?php echo htmlspecialchars($tutor['name']); ?>! Manage your sessions and track your tutoring activity.</p>
            </div>

            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="stats-card">
                        <h3><?php echo $stats['upcoming_sessions_stat'] ?: '0'; ?></h3>
                        <p>Confirmed Sessions</p>
                        <i class="fas fa-calendar-check stats-icon"></i>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="stats-card">
                        <h3><?php echo $stats['completed_sessions'] ?: '0'; ?></h3>
                        <p>Completed Sessions</p>
                        <i class="fas fa-check-circle stats-icon"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h3><?php echo $stats['pending_sessions'] ?: '0'; ?></h3>
                        <p>Requested Sessions</p>
                        <i class="fas fa-hourglass-half stats-icon"></i>
                    </div>
                </div>
            </div>

            <!-- Confirmed Sessions -->
            <div class="card sessions-card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-check"></i> Confirmed Sessions</h3>
                </div>
                <div class="card-body">
                    <?php if (count($bookings_confirmed) > 0): ?>
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i> You have <strong><?php echo count($bookings_confirmed); ?></strong> confirmed session(s) scheduled.
                        </div>
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Email</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings_confirmed as $booking): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($booking['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['student_email']); ?></td>
                                                <td>
                                                    <i class="far fa-calendar-alt me-1 text-primary"></i>
                                                    <?php echo htmlspecialchars(date('M d, Y', strtotime($booking['session_date']))); ?>
                                                </td>
                                                <td>
                                                    <i class="far fa-clock me-1 text-primary"></i>
                                                    <?php echo htmlspecialchars(date('h:i A', strtotime($booking['session_time']))); ?>
                                                </td>
                                                <td>
                                                    <?php if (strtolower($booking['status']) === 'completed'): ?>
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-check-double me-1"></i> Completed
                                                        </span>
                                                    <?php elseif (strtolower($booking['status']) === 'confirmed'): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle me-1"></i> Confirmed
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark">
                                                            <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="session-details.php?id=<?php echo $booking['id']; ?>" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="fas fa-info-circle me-1"></i> Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-sessions-message">
                            <i class="fas fa-calendar-day"></i>
                            <h5>No Confirmed Sessions</h5>
                            <p class="mb-0">You don't have any confirmed sessions yet. Check back later.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>