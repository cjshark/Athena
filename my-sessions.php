<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
require 'db.php';

$student_id = $_SESSION['user_id'];

$stmt_student = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt_student->execute([$student_id]);
$student = $stmt_student->fetch();

$sql_sessions = "SELECT b.id, b.tutor_id, t.name as tutor_name, b.session_date, b.session_time, b.status, b.notes, b.rating, b.feedback
                 FROM bookings b
                 JOIN tutors t ON b.tutor_id = t.id
                 WHERE b.student_id = ?
                 ORDER BY b.session_date DESC, b.session_time DESC";
$stmt_sessions = $conn->prepare($sql_sessions);
$stmt_sessions->execute([$student_id]);
$allSessions = $stmt_sessions->fetchAll(PDO::FETCH_ASSOC);

if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($status) {
        switch (strtolower($status)) {
            case 'pending': return 'warning';
            case 'accepted': return 'info';
            case 'payment_uploaded': return 'primary';
            case 'confirmed': return 'success';
            case 'completed': return 'secondary';
            case 'cancelled': return 'danger';
            case 'rejected': return 'danger';
            default: return 'light';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sessions - Athena</title>
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
        
        /* Sessions Page Styles */
        .sessions-container {
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
        
        /* Tabs styling */
        .nav-tabs {
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 1.5rem;
        }
        
        .nav-tabs .nav-link {
            color: #6c757d;
            font-weight: 600;
            border: none;
            padding: 0.8rem 1.5rem;
            margin-right: 0.5rem;
            border-radius: 10px 10px 0 0;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary);
            background-color: rgba(67, 97, 238, 0.05);
            border: none;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary);
            background-color: #fff;
            border: none;
            border-bottom: 3px solid var(--primary);
        }
        
        .nav-tabs .nav-link::after {
            display: none;
        }
        
        .tab-content {
            background-color: #fff;
            border-radius: 0 15px 15px 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            padding: 1.5rem;
        }
        
        .tab-content h4 {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }
        
        /* Session List */
        .list-group-item {
            border-radius: 12px;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
            padding: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .list-group-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
            display: inline-block;
        }
        
        .btn {
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #2d9348);
            border: none;
            box-shadow: 0 4px 10px rgba(56, 176, 0, 0.2);
        }
        
        .btn-success:hover {
            box-shadow: 0 6px 15px rgba(56, 176, 0, 0.3);
            transform: translateY(-2px);
            background: linear-gradient(135deg, #2d9348, var(--success));
        }
        
        .btn-info {
            background: linear-gradient(135deg, var(--info), #0072ff);
            border: none;
            box-shadow: 0 4px 10px rgba(58, 134, 255, 0.2);
            color: white;
        }
        
        .btn-info:hover {
            box-shadow: 0 6px 15px rgba(58, 134, 255, 0.3);
            transform: translateY(-2px);
            background: linear-gradient(135deg, #0072ff, var(--info));
            color: white;
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border: 2px solid var(--primary);
            transition: all 0.3s ease;
            background-color: transparent;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
            transform: translateY(-2px);
        }
        
        /* Rating Form */
        .rate-form {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 0.5rem;
        }
        
        .form-select, .form-control {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .text-warning {
            color: #f1c40f !important;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .alert-sm {
            padding: 0.3rem 0.75rem;
            font-size: 0.85rem;
        }
        
        /* Empty State */
        .text-muted {
            color: #6c757d !important;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.75rem 1rem;
            }
            
            .page-header h2 {
                font-size: 1.8rem;
            }
            
            .nav-tabs .nav-link {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
            
            .tab-content {
                padding: 1rem;
            }
            
            .list-group-item {
                padding: 1rem;
            }
            
            .btn {
                padding: 0.4rem 1rem;
                font-size: 0.9rem;
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
                        <a class="nav-link" href="student-dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="find-tutor.php">Find a Tutor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my-sessions.php">My Sessions</a>
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

    <div class="sessions-container">
        <div class="container">
            <div class="page-header">
                <h2>My Booked Sessions</h2>
                <p class="text-muted">Manage your tutoring appointments and track your learning progress</p>
            </div>
            
            <div id="alert-container" class="my-3"></div>
            
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'pending' || empty($_GET['tab'])) ? 'active' : ''; ?>" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="<?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'pending' || empty($_GET['tab'])) ? 'true' : 'false'; ?>">
                        <i class="fas fa-clock me-2"></i>Pending
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'confirmed') ? 'active' : ''; ?>" id="confirmed-tab" data-bs-toggle="tab" data-bs-target="#confirmed" type="button" role="tab" aria-controls="confirmed" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] == 'confirmed') ? 'true' : 'false'; ?>">
                        <i class="fas fa-calendar-check me-2"></i>Confirmed
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'completed') ? 'active' : ''; ?>" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab" aria-controls="completed" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] == 'completed') ? 'true' : 'false'; ?>">
                        <i class="fas fa-check-circle me-2"></i>Completed
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'declined') ? 'active' : ''; ?>" id="declined-tab" data-bs-toggle="tab" data-bs-target="#declined" type="button" role="tab" aria-controls="declined" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] == 'declined') ? 'true' : 'false'; ?>">
                        <i class="fas fa-times-circle me-2"></i>Declined
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <!-- Pending Tab -->
                <div class="tab-pane fade <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'pending' || empty($_GET['tab'])) ? 'show active' : ''; ?>" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                    <h4><i class="fas fa-clock me-2"></i>Pending Actions</h4>
                    <?php
                    $pendingActionSessions = array_filter($allSessions, function($s) {
                        return in_array(strtolower($s['status']), ['pending', 'accepted', 'payment_uploaded']);
                    });
                    if (empty($pendingActionSessions)):
                    ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">You have no sessions awaiting action.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($pendingActionSessions as $session): ?>
                                <li class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($session['tutor_name']); ?></h5>
                                            <p class="mb-1">
                                                <i class="far fa-calendar-alt me-1"></i> 
                                                <?php echo date('F j, Y', strtotime($session['session_date'])); ?> 
                                                <i class="far fa-clock ms-2 me-1"></i>
                                                <?php echo date('g:i A', strtotime($session['session_time'])); ?>
                                            </p>
                                            <?php
                                            $status_text = '';
                                            $status_class = '';
                                            switch (strtolower($session['status'])) {
                                                case 'pending':
                                                    $status_text = 'Awaiting Tutor Approval';
                                                    $status_class = 'warning';
                                                    break;
                                                case 'accepted':
                                                    $status_text = 'Awaiting Your Payment';
                                                    $status_class = 'info';
                                                    break;
                                                case 'payment_uploaded':
                                                    $status_text = 'Payment Under Review';
                                                    $status_class = 'primary';
                                                    break;
                                                default:
                                                    $status_text = ucfirst($session['status']);
                                                    $status_class = getStatusBadgeClass($session['status']);
                                            }
                                            ?>
                                            <span class="status-badge badge bg-<?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($status_text); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                            <?php if (strtolower($session['status']) === 'accepted'): ?>
                                                <a href="payment_page.php?booking_id=<?php echo $session['id']; ?>" class="btn btn-success">
                                                    <i class="fas fa-credit-card me-1"></i> Pay
                                                </a>
                                            <?php endif; ?>
                                            <a href="student-session-details.php?id=<?php echo $session['id']; ?>" class="btn btn-info">
                                                <i class="fas fa-info-circle me-1"></i> Details
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Confirmed Tab -->
                <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'confirmed') ? 'show active' : ''; ?>" id="confirmed" role="tabpanel" aria-labelledby="confirmed-tab">
                    <h4><i class="fas fa-calendar-check me-2"></i>Confirmed Sessions</h4>
                    <?php
                    $confirmedSessions = array_filter($allSessions, function($s) {
                        $sessionDateTime = strtotime($s['session_date'] . ' ' . $s['session_time']);
                        return strtolower($s['status']) === 'confirmed' && $sessionDateTime > time();
                    });
                    if (empty($confirmedSessions)):
                    ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-xmark fa-3x text-muted mb-3"></i>
                            <p class="text-muted">You have no confirmed sessions scheduled.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($confirmedSessions as $session): ?>
                                <li class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($session['tutor_name']); ?></h5>
                                            <p class="mb-1">
                                                <i class="far fa-calendar-alt me-1"></i> 
                                                <?php echo date('F j, Y', strtotime($session['session_date'])); ?> 
                                                <i class="far fa-clock ms-2 me-1"></i>
                                                <?php echo date('g:i A', strtotime($session['session_time'])); ?>
                                            </p>
                                            <span class="status-badge badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i> Confirmed
                                            </span>
                                        </div>
                                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                            <a href="student-session-details.php?id=<?php echo $session['id']; ?>" class="btn btn-info">
                                                <i class="fas fa-info-circle me-1"></i> Details
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Completed Tab -->
                <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'completed') ? 'show active' : ''; ?>" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                    <h4><i class="fas fa-check-circle me-2"></i>Completed Sessions</h4>
                    <?php
                     $completedSessions = array_filter($allSessions, function($s) {
                        $sessionDateTime = strtotime($s['session_date'] . ' ' . $s['session_time']);
                        return strtolower($s['status']) === 'completed' || (strtolower($s['status']) === 'confirmed' && $sessionDateTime <= time());
                     });
                    if (empty($completedSessions)):
                    ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">You have no completed sessions yet.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($completedSessions as $session): ?>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($session['tutor_name']); ?></h5>
                                            <p class="mb-1">
                                                <i class="far fa-calendar-alt me-1"></i> 
                                                <?php echo date('F j, Y', strtotime($session['session_date'])); ?> 
                                                <i class="far fa-clock ms-2 me-1"></i>
                                                <?php echo date('g:i A', strtotime($session['session_time'])); ?>
                                            </p>
                                            <span class="status-badge badge bg-secondary">
                                                <i class="fas fa-check-double me-1"></i> Completed
                                            </span>
                                        </div>
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <?php if (array_key_exists('rating', $session) && is_null($session['rating'])): ?>
                                                <form class="rate-form" data-booking-id="<?php echo $session['id']; ?>">
                                                    <h6 class="mb-2"><i class="fas fa-star me-1 text-warning"></i> Rate this session</h6>
                                                    <div class="row g-2 align-items-center mb-2">
                                                        <div class="col-auto">
                                                            <label for="rating-<?php echo $session['id']; ?>" class="form-label mb-0">Rating:</label>
                                                        </div>
                                                        <div class="col-auto">
                                                            <select id="rating-<?php echo $session['id']; ?>" class="form-select form-select-sm" name="rating" required>
                                                                <option value="" selected disabled>1-5 ★</option>
                                                                <option value="1">1 ★</option>
                                                                <option value="2">2 ★</option>
                                                                <option value="3">3 ★</option>
                                                                <option value="4">4 ★</option>
                                                                <option value="5">5 ★</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <textarea class="form-control form-control-sm" id="feedback-<?php echo $session['id']; ?>" name="feedback" rows="2" placeholder="Share your experience with this tutor..."></textarea>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-paper-plane me-1"></i> Submit Rating
                                                        </button>
                                                        <a href="student-session-details.php?id=<?php echo $session['id']; ?>" class="btn btn-info btn-sm">
                                                            <i class="fas fa-info-circle me-1"></i> Details
                                                        </a>
                                                    </div>
                                                    <div class="alert-placeholder mt-2"></div>
                                                </form>
                                            <?php elseif (array_key_exists('rating', $session) && !is_null($session['rating'])): ?>
                                                <div class="bg-light p-3 rounded-3">
                                                    <h6 class="mb-2"><i class="fas fa-star me-1 text-warning"></i> Your Rating</h6>
                                                    <div class="text-warning mb-2" style="font-size: 1.2rem;">
                                                        <?php 
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                if ($i <= $session['rating']) {
                                                                    echo '<i class="fas fa-star"></i>';
                                                                } else {
                                                                    echo '<i class="far fa-star"></i>';
                                                                }
                                                            }
                                                        ?>
                                                    </div>
                                                    <?php if (array_key_exists('feedback', $session) && !empty($session['feedback'])): ?>
                                                    <div>
                                                        <h6 class="mb-1">Your Feedback:</h6>
                                                        <p class="text-muted fst-italic mb-2">"<?php echo nl2br(htmlspecialchars($session['feedback'])); ?>"</p>
                                                    </div>
                                                    <?php endif; ?>
                                                    <a href="student-session-details.php?id=<?php echo $session['id']; ?>" class="btn btn-info btn-sm mt-1">
                                                        <i class="fas fa-info-circle me-1"></i> Details
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Declined Tab -->
                <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'declined') ? 'show active' : ''; ?>" id="declined" role="tabpanel" aria-labelledby="declined-tab">
                    <h4><i class="fas fa-times-circle me-2"></i>Declined Sessions</h4>
                    <?php
                    $declinedSessions = array_filter($allSessions, function($s) {
                        return strtolower($s['status']) === 'declined';
                    });
                    if (empty($declinedSessions)):
                    ?>
                        <div class="text-center py-4">
                            <i class="fas fa-ban fa-3x text-muted mb-3"></i>
                            <p class="text-muted">You have no declined sessions.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($declinedSessions as $session): ?>
                                <li class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($session['tutor_name']); ?></h5>
                                            <p class="mb-1">
                                                <i class="far fa-calendar-alt me-1"></i> 
                                                <?php echo date('F j, Y', strtotime($session['session_date'])); ?> 
                                                <i class="far fa-clock ms-2 me-1"></i>
                                                <?php echo date('g:i A', strtotime($session['session_time'])); ?>
                                            </p>
                                            <span class="status-badge badge bg-danger">
                                                <i class="fas fa-times-circle me-1"></i> Declined
                                            </span>
                                        </div>
                                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                            <a href="student-session-details.php?id=<?php echo $session['id']; ?>" class="btn btn-info">
                                                <i class="fas fa-info-circle me-1"></i> Details
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            function showAlert(message, type = 'success', container = '#alert-container') {
                const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show py-2" role="alert">
                                    ${message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                   </div>`;
                $(container).html(alertHtml);
                 setTimeout(() => { $(container + ' .alert').alert('close'); }, 5000);
            }

            $(document).on('submit', '.rate-form', function(e) {
                e.preventDefault();
                const form = $(this);
                const bookingId = form.data('booking-id');
                const rating = form.find('select[name="rating"]').val();
                const feedback = form.find('textarea[name="feedback"]').val();
                const alertPlaceholder = form.find('.alert-placeholder');
                const submitButton = form.find('button[type="submit"]');

                alertPlaceholder.empty();
                submitButton.prop('disabled', true).text('Submitting...');

                $.ajax({
                    url: 'submit-rating.php',
                    type: 'POST',
                    data: {
                        booking_id: bookingId,
                        rating: rating,
                        feedback: feedback
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alertPlaceholder.html('<div class="alert alert-success alert-sm py-1">Rating & Feedback submitted!</div>');
                            form.find('select, textarea, button').prop('disabled', true);
                             setTimeout(function() {
                                 location.reload();
                             }, 1500);
                        } else {
                            alertPlaceholder.html('<div class="alert alert-danger alert-sm py-1">' + (response.error || response.message || 'An error occurred.') + '</div>');
                            submitButton.prop('disabled', false).text('Submit Rating');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error, xhr.responseText);
                        alertPlaceholder.html('<div class="alert alert-danger alert-sm py-1">Could not submit rating. Please try again.</div>');
                        submitButton.prop('disabled', false).text('Submit Rating');
                    }
                });
            });

            var urlParams = new URLSearchParams(window.location.search);
            var tab = urlParams.get('tab') || window.location.hash.substring(1);
            if (tab) {
                var triggerEl = document.querySelector('#myTab button[data-bs-target="#' + tab + '"]');
                 if (triggerEl) {
                    var tabInstance = new bootstrap.Tab(triggerEl);
                    tabInstance.show();
                 }
            } else {
                 var firstTab = document.querySelector('#myTab button');
                 if(firstTab) {
                     var tabInstance = new bootstrap.Tab(firstTab);
                     tabInstance.show();
                 }
            }
        });
    </script>
</body>
</html>
