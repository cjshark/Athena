<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}
require 'db.php';

$tutor_id = $_SESSION['user_id'];
$booking_id = $_GET['id'] ?? null;

if (!$booking_id) {
    header("Location: tutor-dashboard.php");
    exit();
}

// Get full tutor information including profile image
$stmt_tutor = $conn->prepare("SELECT * FROM tutors WHERE id = ?");
$stmt_tutor->execute([$tutor_id]);
$tutor = $stmt_tutor->fetch();

$stmt = $conn->prepare("
    SELECT b.*, s.name as student_name, s.email as student_email, s.phone_number as student_phone
    FROM bookings b
    JOIN students s ON b.student_id = s.id
    WHERE b.id = ? AND b.tutor_id = ?
");
$stmt->execute([$booking_id, $tutor_id]);
$session = $stmt->fetch();

if (!$session) {
    echo "Session not found or access denied.";
    exit();
}

$session_date_formatted = date('F j, Y', strtotime($session['session_date']));
$session_time_formatted = date('g:i A', strtotime($session['session_time']));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Details - Athena</title>
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
        
        /* Page Header */
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
        
        /* Session Details Card */
        .details-card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 2rem;
        }
        
        .details-card .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            padding: 1.25rem 1.5rem;
            border: none;
        }
        
        .details-card .card-body {
            padding: 2rem;
        }
        
        .card-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }
        
        .section-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }
        
        .info-group {
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            margin-bottom: 0.75rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            display: inline-block;
        }
        
        .notes-container {
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            background-color: white;
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid #e9ecef;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin-top: 0.75rem;
        }
        
        .btn {
            border-radius: 50px;
            padding: 0.6rem 1.5rem;
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
        
        .btn-outline-primary {
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
            transform: translateY(-2px);
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1.5rem;
        }
        
        .breadcrumb-item a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .breadcrumb-item.active {
            color: var(--dark);
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.75rem 1rem;
            }
            
            .page-header h2 {
                font-size: 1.8rem;
            }
            
            .details-card .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="#">Athena</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="tutor-dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tutor-schedule.php">My Schedule</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php if (isset($tutor['profile_image']) && !empty($tutor['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($tutor['profile_image']); ?>" alt="Profile" class="nav-profile-img me-1">
                            <?php else: ?>
                                <i class="fas fa-user-circle me-1"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($tutor['name'] ?? 'Tutor'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="tutor-profile.php"><i class="fas fa-user-cog me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>    <div class="container py-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="tutor-dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Session Details</li>
            </ol>
        </nav>

        <div class="page-header">
            <h2>Session Details</h2>
            <p class="text-muted">Review your upcoming tutoring session details</p>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card details-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-chalkboard-teacher me-2"></i>Session Information</span>
                        <span class="status-badge bg-<?php echo getStatusBadgeClass($session['status']); ?>">
                            <i class="fas fa-<?php echo getStatusIcon($session['status']); ?> me-1"></i> 
                            <?php echo ucfirst(htmlspecialchars($session['status'])); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="info-group">
                            <h5 class="section-title"><i class="fas fa-calendar-day me-2"></i>Date & Time</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <strong><i class="fas fa-calendar-alt me-1"></i> Date:</strong>
                                        <span class="ms-2"><?php echo $session_date_formatted; ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <strong><i class="fas fa-clock me-1"></i> Time:</strong>
                                        <span class="ms-2"><?php echo $session_time_formatted; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="info-group">
                            <h5 class="section-title"><i class="fas fa-user-graduate me-2"></i>Student Information</h5>
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <div class="info-item">
                                        <strong><i class="fas fa-user me-1"></i> Name:</strong>
                                        <span class="ms-2"><?php echo htmlspecialchars($session['student_name']); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <strong><i class="fas fa-envelope me-1"></i> Email:</strong>
                                        <span class="ms-2"><?php echo htmlspecialchars($session['student_email'] ?? 'Not provided'); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <strong><i class="fas fa-phone me-1"></i> Phone:</strong>
                                        <span class="ms-2"><?php echo htmlspecialchars($session['student_phone'] ?? 'Not provided'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="info-group">
                            <h5 class="section-title"><i class="fas fa-info-circle me-2"></i>Booking Details</h5>
                            <div class="info-item">
                                <strong><i class="fas fa-calendar-check me-1"></i> Booked On:</strong>
                                <span class="ms-2"><?php echo date('F j, Y, g:i a', strtotime($session['created_at'])); ?></span>
                            </div>
                        </div>

                        <?php if (!empty($session['notes'])): ?>
                        <div class="notes-container">
                            <h5 class="section-title"><i class="fas fa-sticky-note me-2"></i>Notes from Student</h5>
                            <pre><?php echo htmlspecialchars($session['notes']); ?></pre>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="tutor-dashboard.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                            </a>
                            <?php if (strtolower($session['status']) === 'pending'): ?>
                            <div>
                                <a href="update-session-status.php?id=<?php echo $booking_id; ?>&status=accepted" class="btn btn-success me-2">
                                    <i class="fas fa-check me-1"></i> Accept
                                </a>
                                <a href="update-session-status.php?id=<?php echo $booking_id; ?>&status=declined" class="btn btn-danger">
                                    <i class="fas fa-times me-1"></i> Decline
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card details-card">
                    <div class="card-header">
                        <i class="fas fa-question-circle me-2"></i> Session Guide
                    </div>
                    <div class="card-body">
                        <h6 class="section-title">Before the Session</h6>
                        <ul class="mb-3">
                            <li>Review the student's notes</li>
                            <li>Prepare your teaching materials</li>
                            <li>Check your internet connection</li>
                            <li>Set up your learning environment</li>
                        </ul>

                        <h6 class="section-title">During the Session</h6>
                        <ul class="mb-3">
                            <li>Be punctual and professional</li>
                            <li>Take notes for follow-up</li>
                            <li>Ask for feedback throughout</li>
                        </ul>

                        <h6 class="section-title">After the Session</h6>
                        <ul>
                            <li>Mark the session as completed</li>
                            <li>Send follow-up materials if needed</li>
                            <li>Request feedback from the student</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Helper function for status icon
function getStatusIcon($status) {
    switch (strtolower($status)) {
        case 'pending': return 'clock';
        case 'accepted': return 'thumbs-up';
        case 'declined': return 'times-circle';
        case 'completed': return 'check-circle';
        case 'payment_uploaded': return 'credit-card';
        case 'cancelled': return 'ban';
        default: return 'question-circle';
    }
}

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending': return 'warning text-dark';
        case 'accepted': return 'success';
        case 'declined': return 'danger';
        case 'completed': return 'info';
        case 'payment_uploaded': return 'primary';
        case 'cancelled': return 'secondary';
        default: return 'light text-dark';
    }
}
?>