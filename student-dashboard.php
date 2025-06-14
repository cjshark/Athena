<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
require 'db.php';

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

$stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined
    FROM bookings 
    WHERE student_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

$stmt = $conn->prepare("
    SELECT b.*, t.name as tutor_name, t.profile_image
    FROM bookings b 
    JOIN tutors t ON b.tutor_id = t.id 
    WHERE b.student_id = ? 
    AND b.status = 'confirmed' 
    ORDER BY b.session_date ASC, b.session_time ASC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$confirmedSessionDetails = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Athena</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .nav-profile-img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
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
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.15);
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            z-index: 0;
        }
        
        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: -70px;
            left: -70px;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            z-index: 0;
        }
        
        .welcome-content {
            position: relative;
            z-index: 1;
        }
        
        .welcome-section h2 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
        }
        
        .welcome-section p {
            opacity: 0.9;
            font-size: 1.1rem;
            max-width: 600px;
        }
        
        .sidebar {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            height: 100%;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            position: sticky;
            top: 90px;
        }
        
        .profile-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .user-name {
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 0.25rem;
        }
        
        .user-email {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        .sidebar-menu {
            margin-bottom: 2rem;
        }
        
        .sidebar-menu-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6c757d;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            color: var(--dark);
            text-decoration: none;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            transform: translateX(5px);
        }
        
        .menu-item i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            color: var(--dark);
        }
        
        .stat-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            color: var(--dark);
        }
        
        .stat-card.confirmed {
            border-top: 4px solid var(--primary);
        }
        
        .stat-card.completed {
            border-top: 4px solid var(--success);
        }
        
        .stat-card.declined {
            border-top: 4px solid var(--accent);
        }
        
        .stat-card .icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        
        .stat-card.confirmed .icon {
            background-color: rgba(67, 97, 238, 0.15);
            color: var(--primary);
        }
        
        .stat-card.completed .icon {
            background-color: rgba(56, 176, 0, 0.15);
            color: var(--success);
        }
        
        .stat-card.declined .icon {
            background-color: rgba(247, 37, 133, 0.15);
            color: var(--accent);
        }
        
        .stat-card .count {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        
        .stat-card .label {
            color: #6c757d;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .section-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 0.75rem;
            color: var(--primary);
        }
        
        .upcoming-sessions {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .session-card {
            border: none;
            border-radius: 15px;
            margin-bottom: 1.25rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
            overflow: hidden;
            background-color: #f9f9f9;
        }
        
        .session-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        
        .session-card .tutor-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .session-date {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 0.75rem;
        }
        
        .session-card .card-body {
            padding: 1.5rem;
        }
        
        .tutor-name {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .session-time {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .session-time i {
            margin-right: 0.5rem;
            color: var(--primary);
        }
        
        .cta-button {
            border-radius: 50px;
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: none;
        }
        
        .cta-button.primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
        }
        
        .cta-button.primary:hover {
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
            transform: translateY(-3px);
        }
        
        .no-sessions {
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 3rem 1.5rem;
            text-align: center;
        }
        
        .no-sessions i {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        
        .no-sessions h5 {
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .no-sessions p {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        
        .find-tutor-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
        }
        
        .find-tutor-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
            color: white;
        }
        
        @media (max-width: 991.98px) {
            .stats-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 767.98px) {
            .stats-container {
                grid-template-columns: repeat(1, 1fr);
            }
            
            .welcome-section {
                padding: 1.5rem;
            }
            
            .welcome-section h2 {
                font-size: 1.8rem;
            }
            
            .welcome-section p {
                font-size: 1rem;
            }
            
            .sidebar {
                margin-bottom: 2rem;
                position: static;
            }
            
            .navbar {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="student-dashboard.php">Athena</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="student-dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="find-tutor.php">Find a Tutor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-sessions.php">My Sessions</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php if (isset($student['profile_image']) && !empty($student['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="nav-profile-img me-1">
                            <?php else: ?>
                                <i class="fas fa-user-circle me-1"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($student['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="student-profile.php">
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
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h2>Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h2>
                    <p>Welcome to your dashboard. Here you can manage your tutoring sessions, find new tutors, and track your learning progress.</p>
                </div>
            </div>
            
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-3">
                    <div class="sidebar">
                        <div class="profile-section">
                            <?php if (isset($student['profile_image']) && !empty($student['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" class="profile-image" alt="Profile Image">
                            <?php else: ?>
                                <img src="uploads/profile_images/default-avatar.png" class="profile-image" alt="Profile Image">
                            <?php endif; ?>
                            <h5 class="user-name"><?php echo htmlspecialchars($student['name']); ?></h5>
                            <p class="user-email"><?php echo htmlspecialchars($student['email']); ?></p>
                            <a href="student-profile.php" class="btn btn-sm btn-light">
                                <i class="fas fa-edit me-2"></i> Edit Profile
                            </a>
                        </div>
                        
                        <div class="sidebar-menu">
                            <h6 class="sidebar-menu-title">Quick Menu</h6>
                            <a href="find-tutor.php" class="menu-item">
                                <i class="fas fa-search"></i> Find a Tutor
                            </a>
                            <a href="my-sessions.php" class="menu-item">
                                <i class="fas fa-calendar-check"></i> My Sessions
                            </a>
                            <a href="my-sessions.php?view=completed" class="menu-item">
                                <i class="fas fa-clipboard-check"></i> Learning History
                            </a>
                            <a href="student-profile.php" class="menu-item">
                                <i class="fas fa-user-edit"></i> Update Profile
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="col-lg-9">
                    <!-- Stats Cards -->
                    <div class="stats-container">
                        <a href="my-sessions.php?view=confirmed" class="stat-card confirmed">
                            <div class="icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="count"><?php echo $stats['confirmed'] ?: '0'; ?></div>
                            <div class="label">Confirmed Sessions</div>
                        </a>
                        
                        <a href="my-sessions.php?view=completed" class="stat-card completed">
                            <div class="icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="count"><?php echo $stats['completed'] ?: '0'; ?></div>
                            <div class="label">Completed Sessions</div>
                        </a>
                        
                        <a href="my-sessions.php?view=declined" class="stat-card declined">
                            <div class="icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="count"><?php echo $stats['declined'] ?: '0'; ?></div>
                            <div class="label">Declined Sessions</div>
                        </a>
                    </div>
                    
                    <!-- Upcoming Sessions -->
                    <div class="upcoming-sessions">
                        <h4 class="section-title">
                            <i class="fas fa-calendar-day"></i> Your Upcoming Sessions
                        </h4>
                        
                        <?php if ($confirmedSessionDetails): ?>
                            <div class="row">
                                <?php foreach ($confirmedSessionDetails as $session): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card session-card h-100">
                                            <div class="card-body">
                                                <div class="session-date">
                                                    <i class="fas fa-calendar-alt me-2"></i>
                                                    <?php echo date('F j, Y', strtotime($session['session_date'])); ?>
                                                </div>
                                                
                                                <div class="d-flex align-items-center mb-3">
                                                    <img src="<?php echo !empty($session['profile_image']) ? htmlspecialchars($session['profile_image']) : 'uploads/profile_images/default-avatar.png'; ?>" 
                                                         class="tutor-img me-3" alt="<?php echo htmlspecialchars($session['tutor_name']); ?>">
                                                    <div>
                                                        <h5 class="tutor-name"><?php echo htmlspecialchars($session['tutor_name']); ?></h5>
                                                        <div class="session-time">
                                                            <i class="fas fa-clock"></i>
                                                            <?php echo date('g:i A', strtotime($session['session_time'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="badge bg-primary rounded-pill">
                                                        <i class="fas fa-check-circle me-1"></i> Confirmed
                                                    </span>
                                                    <a href="student-session-details.php?id=<?php echo $session['id']; ?>" class="cta-button primary">
                                                        <i class="fas fa-eye me-2"></i> View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="my-sessions.php" class="btn btn-outline-primary rounded-pill px-4">
                                    <i class="fas fa-list me-2"></i> View All Sessions
                                </a>
                            </div>
                            
                        <?php else: ?>
                            <div class="no-sessions">
                                <i class="fas fa-calendar-times d-block mx-auto"></i>
                                <h5>No upcoming sessions</h5>
                                <p>You don't have any confirmed sessions scheduled. Start by finding a tutor that matches your learning needs.</p>
                                <a href="find-tutor.php" class="find-tutor-btn">
                                    <i class="fas fa-search me-2"></i> Find a Tutor
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function checkBookingStatus() {
            $.ajax({
                url: 'check-booking-status.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.updated) {
                        location.reload();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error checking booking status:", textStatus, errorThrown);
                }
            });
        }
        
        $(document).ready(function() {
            // Add hover effect to sidebar menu items
            $('.menu-item').hover(
                function() { $(this).find('i').addClass('fa-bounce'); },
                function() { $(this).find('i').removeClass('fa-bounce'); }
            );
            
            // Check for updates every 60 seconds
            setInterval(checkBookingStatus, 60000);
        });
    </script>
</body>
</html>
