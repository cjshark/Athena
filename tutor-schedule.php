<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}
require 'db.php';

$tutor_id = $_SESSION['user_id'];

// Fetch full tutor information including profile image
$stmt_tutor_info = $conn->prepare("SELECT * FROM tutors WHERE id = ?");
$stmt_tutor_info->execute([$tutor_id]);
$tutor = $stmt_tutor_info->fetch();

// Fetch Pending Booking Requests
$stmt_pending = $conn->prepare("
    SELECT b.id, b.session_date, b.session_time, b.notes, s.name as student_name
    FROM bookings b
    JOIN students s ON b.student_id = s.id
    WHERE b.tutor_id = ? AND b.status = 'pending'
    ORDER BY b.created_at DESC
");
$stmt_pending->execute([$tutor_id]);
$pendingBookings = $stmt_pending->fetchAll();

// Fetch Current Confirmed Sessions (for marking as complete)
$stmt_current = $conn->prepare("
    SELECT b.id, b.session_date, b.session_time, b.notes, s.name as student_name
    FROM bookings b
    JOIN students s ON b.student_id = s.id
    WHERE b.tutor_id = ? AND b.status = 'confirmed'
    ORDER BY b.session_date ASC, b.session_time ASC
");
$stmt_current->execute([$tutor_id]);
$currentSessions = $stmt_current->fetchAll();

// Fetch Upcoming Accepted Sessions (Today or later, not yet paid or payment not yet confirmed)
$stmt_upcoming = $conn->prepare("
    SELECT b.id, b.session_date, b.session_time, b.notes, s.name as student_name
    FROM bookings b
    JOIN students s ON b.student_id = s.id
    WHERE b.tutor_id = ? AND b.status = 'accepted' AND b.session_date >= CURDATE()
    ORDER BY b.session_date ASC, b.session_time ASC
");
$stmt_upcoming->execute([$tutor_id]);
$upcomingSessions = $stmt_upcoming->fetchAll();

// Fetch Past/Completed Sessions
$stmt_past = $conn->prepare("
    SELECT b.id, b.session_date, b.session_time, b.status, b.notes, s.name as student_name
    FROM bookings b
    JOIN students s ON b.student_id = s.id
    WHERE b.tutor_id = ? AND (b.status = 'completed' OR (b.status = 'accepted' AND b.session_date < CURDATE()) OR b.status = 'declined')
    ORDER BY b.session_date DESC, b.session_time DESC
");
$stmt_past->execute([$tutor_id]);
$pastSessions = $stmt_past->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Athena</title>
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
        
        /* Card Styles */
        .session-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .session-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            font-weight: 700;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
        }
        
        .list-group-item {
            border: none;
            padding: 1rem 1.25rem;
        }
        
        .list-group-item:not(:last-child) {
            border-bottom: 1px solid var(--gray-light);
        }
        
        /* Section Headers */
        .section-header {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .section-header h4 {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 0;
            color: var(--dark);
        }
        
        .section-header i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        /* Buttons */
        .btn {
            border-radius: 50px;
            padding: 0.5rem 1.25rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-sm {
            padding: 0.4rem 1rem;
            font-size: 0.875rem;
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
        
        .btn-success {
            background-color: var(--success);
            border: none;
            box-shadow: 0 4px 10px rgba(56, 176, 0, 0.2);
        }
        
        .btn-success:hover {
            background-color: #32a000;
            box-shadow: 0 6px 15px rgba(56, 176, 0, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--accent);
            border: none;
            box-shadow: 0 4px 10px rgba(247, 37, 133, 0.2);
        }
        
        .btn-danger:hover {
            background-color: #e61e79;
            box-shadow: 0 6px 15px rgba(247, 37, 133, 0.3);
            transform: translateY(-2px);
        }
        
        /* Tables */
        .table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .table thead th {
            background-color: #f8f9fa;
            font-weight: 700;
            border-top: none;
            border-bottom: 2px solid var(--gray-light);
        }
        
        .table-bordered {
            border: none;
        }
        
        /* Alert Container */
        #alert-container {
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
        }
        
        #alert-container .alert {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 12px;
            border: none;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.75rem 1rem;
            }
            
            .navbar-brand {
                font-size: 1.5rem;
            }
            
            .page-header h2 {
                font-size: 1.8rem;
            }
            
            .btn {
                padding: 0.4rem 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="tutor-dashboard.php">Athena</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="tutor-dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="tutor-schedule.php">My Schedule</a>
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
    </nav>

    <!-- Alert Container -->
    <div id="alert-container"></div>

    <div class="container py-4">
        <div class="page-header">
            <h2>My Schedule</h2>
            <p class="text-muted">Manage your tutoring sessions and booking requests</p>
        </div>

        <!-- Current Confirmed Sessions -->
        <section class="mb-5">
            <div class="section-header">
                <h4><i class="fas fa-calendar-check text-success"></i> Current Sessions</h4>
            </div>
            <?php if (empty($currentSessions)): ?>
                <div class="alert alert-light border-0 shadow-sm rounded-3 py-3">
                    <i class="fas fa-info-circle me-2 text-primary"></i>
                    <span class="text-muted">No current confirmed sessions</span>
                </div>
            <?php else: ?>
                <div class="card session-card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Notes</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($currentSessions as $session): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user-graduate text-primary me-2"></i>
                                                    <span><?php echo htmlspecialchars($session['student_name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="fas fa-calendar-alt text-secondary me-1"></i>
                                                <?php echo htmlspecialchars(date('D, M j, Y', strtotime($session['session_date']))); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-clock text-secondary me-1"></i>
                                                <?php echo htmlspecialchars(date('g:i A', strtotime($session['session_time']))); ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($session['notes'])): ?>
                                                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo htmlspecialchars($session['notes']); ?>">
                                                        <i class="fas fa-sticky-note text-secondary"></i> View Notes
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="fas fa-sticky-note text-secondary me-1"></i> No notes</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-success btn-sm action-btn" data-action="complete" data-booking-id="<?php echo $session['id']; ?>">
                                                    <i class="fas fa-check-double me-1"></i> Mark Completed
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Pending Requests -->
        <section class="mb-5">
            <div class="section-header">
                <h4><i class="fas fa-hourglass-half text-warning"></i> Pending Booking Requests</h4>
            </div>
            <?php if (empty($pendingBookings)): ?>
                <div class="alert alert-light border-0 shadow-sm rounded-3 py-3">
                    <i class="fas fa-info-circle me-2 text-primary"></i>
                    <span class="text-muted">No pending booking requests</span>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($pendingBookings as $booking): ?>
                        <div class="col-lg-6 col-md-12" id="booking-<?php echo $booking['id']; ?>">
                            <div class="card session-card h-100">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-user-graduate me-2"></i><?php echo htmlspecialchars($booking['student_name']); ?></span>
                                        <span class="status-badge bg-warning text-dark">
                                            <i class="fas fa-clock me-1"></i> Pending
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex mb-2">
                                            <div class="me-3"><i class="fas fa-calendar-alt text-primary"></i></div>
                                            <div>
                                                <strong>Date:</strong>
                                                <div><?php echo htmlspecialchars(date('D, M j, Y', strtotime($booking['session_date']))); ?></div>
                                            </div>
                                        </div>
                                        <div class="d-flex mb-2">
                                            <div class="me-3"><i class="fas fa-clock text-primary"></i></div>
                                            <div>
                                                <strong>Time:</strong>
                                                <div><?php echo htmlspecialchars(date('g:i A', strtotime($booking['session_time']))); ?></div>
                                            </div>
                                        </div>
                                        <?php if (!empty($booking['notes'])): ?>
                                        <div class="d-flex">
                                            <div class="me-3"><i class="fas fa-sticky-note text-primary"></i></div>
                                            <div>
                                                <strong>Notes:</strong>
                                                <div class="text-muted small"><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex justify-content-end mt-3">
                                        <button class="btn btn-success btn-sm me-2 action-btn" data-action="accept" data-booking-id="<?php echo $booking['id']; ?>">
                                            <i class="fas fa-check me-1"></i> Accept
                                        </button>
                                        <button class="btn btn-danger btn-sm action-btn" data-action="decline" data-booking-id="<?php echo $booking['id']; ?>">
                                            <i class="fas fa-times me-1"></i> Decline
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Upcoming Sessions (Accepted, Awaiting Payment or Confirmed) -->
        <section class="mb-5">
            <div class="section-header">
                <h4><i class="fas fa-calendar-check text-primary"></i> Accepted & Upcoming Sessions</h4>
            </div>
            <p class="text-muted small mb-3">Sessions you've accepted that are awaiting student payment or already confirmed by you after payment.</p>
            
            <?php if (empty($upcomingSessions)): ?>
                <div class="alert alert-light border-0 shadow-sm rounded-3 py-3">
                    <i class="fas fa-info-circle me-2 text-primary"></i>
                    <span class="text-muted">No upcoming accepted sessions</span>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($upcomingSessions as $session): ?>
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="card session-card h-100">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-user-graduate me-2"></i><?php echo htmlspecialchars($session['student_name']); ?></span>
                                        <span class="status-badge bg-warning text-dark">
                                            <i class="fas fa-credit-card me-1"></i> Awaiting Payment
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex mb-2">
                                            <div class="me-3"><i class="fas fa-calendar-alt text-primary"></i></div>
                                            <div>
                                                <strong>Date:</strong>
                                                <div><?php echo htmlspecialchars(date('D, M j, Y', strtotime($session['session_date']))); ?></div>
                                            </div>
                                        </div>
                                        <div class="d-flex mb-2">
                                            <div class="me-3"><i class="fas fa-clock text-primary"></i></div>
                                            <div>
                                                <strong>Time:</strong>
                                                <div><?php echo htmlspecialchars(date('g:i A', strtotime($session['session_time']))); ?></div>
                                            </div>
                                        </div>
                                        <?php if (!empty($session['notes'])): ?>
                                        <div class="d-flex">
                                            <div class="me-3"><i class="fas fa-sticky-note text-primary"></i></div>
                                            <div>
                                                <strong>Notes:</strong>
                                                <div class="text-muted small"><?php echo nl2br(htmlspecialchars($session['notes'])); ?></div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex justify-content-end mt-3">
                                        <a href="session-details.php?id=<?php echo $session['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Past Sessions -->
        <section>
            <div class="section-header">
                <h4><i class="fas fa-history text-secondary"></i> Session History</h4>
            </div>
            <?php if (empty($pastSessions)): ?>
                <div class="alert alert-light border-0 shadow-sm rounded-3 py-3">
                    <i class="fas fa-info-circle me-2 text-primary"></i>
                    <span class="text-muted">No past session history</span>
                </div>
            <?php else: ?>
                <div class="card session-card">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($pastSessions as $session): ?>
                                <?php
                                $status_text = ucfirst(htmlspecialchars($session['status']));
                                $badge_class = 'bg-secondary';
                                $icon_class = 'circle-info';
                                
                                if ($session['status'] == 'completed') {
                                    $badge_class = 'bg-success';
                                    $icon_class = 'check-circle';
                                } elseif ($session['status'] == 'declined') {
                                    $badge_class = 'bg-danger';
                                    $icon_class = 'times-circle';
                                } elseif ($session['status'] == 'accepted') {
                                    $badge_class = 'bg-info text-dark';
                                    $icon_class = 'thumbs-up';
                                }
                                ?>
                                <div class="list-group-item list-group-item-action p-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-user-graduate text-primary me-2"></i>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($session['student_name']); ?></h6>
                                            </div>
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="fas fa-calendar-alt text-secondary me-2"></i>
                                                <span class="text-muted"><?php echo htmlspecialchars(date('D, M j, Y', strtotime($session['session_date']))); ?></span>
                                            </div>
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="fas fa-clock text-secondary me-2"></i>
                                                <span class="text-muted"><?php echo htmlspecialchars(date('g:i A', strtotime($session['session_time']))); ?></span>
                                            </div>
                                            <?php if (!empty($session['notes'])): ?>
                                            <button class="btn btn-sm btn-light mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#notes-<?php echo $session['id']; ?>">
                                                <i class="fas fa-sticky-note me-1"></i> View Notes
                                            </button>
                                            <div class="collapse mt-2" id="notes-<?php echo $session['id']; ?>">
                                                <div class="card card-body bg-light small">
                                                    <?php echo nl2br(htmlspecialchars($session['notes'])); ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="status-badge <?php echo $badge_class; ?>">
                                                <i class="fas fa-<?php echo $icon_class; ?> me-1"></i> <?php echo $status_text; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
            
            // Function to show alerts
            function showAlert(message, type = 'success') {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i> ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>`;
                $('#alert-container').html(alertHtml); // Replace existing alert
                setTimeout(() => {
                    $('#alert-container .alert').alert('close');
                }, 5000);
            }

            // Store original button text for all action buttons
            $('.action-btn').each(function() {
                $(this).data('original-text', $(this).html());
            });

            // Handle Accept/Decline/Complete Buttons
            $('.action-btn').click(function() {
                const button = $(this);
                const bookingId = button.data('booking-id');
                const action = button.data('action'); // 'accept', 'decline', 'complete'

                button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                $.ajax({
                    type: 'POST',
                    url: 'update-booking-status.php', 
                    data: {
                        booking_id: bookingId,
                        action: action 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message || 'Action successful: ' + action.replace('_', ' '), 'success');
                            // Consider more targeted DOM updates instead of reload for better UX
                            // For now, reload to reflect changes across sections
                            setTimeout(function() { location.reload(); }, 1500); // Reload after a short delay
                        } else {
                            showAlert('Error: ' + (response.error || 'Could not update status.'), 'danger');
                            button.prop('disabled', false).html(button.data('original-text'));
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        showAlert('An unexpected error occurred: ' + textStatus + ' - ' + errorThrown, 'danger');
                        button.prop('disabled', false).html(button.data('original-text'));
                    }
                });
            });
        });
    </script>
</body>
</html>