<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$selected_tutor = null;
$error = '';

if (!isset($_GET['tutor_id']) || !filter_var($_GET['tutor_id'], FILTER_VALIDATE_INT)) {
    $error = "No tutor specified or invalid tutor ID.";
} else {
    $tutor_id = (int)$_GET['tutor_id'];
    $stmt = $conn->prepare("SELECT id, name, email, specialty, profile_image FROM tutors WHERE id = ? AND status = 'active'");
    $stmt->execute([$tutor_id]);
    $selected_tutor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$selected_tutor) {
        $error = "Tutor not found or is not currently active.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$selected_tutor) {
        $error = "Cannot book session: Tutor information is missing. Please select a tutor first.";
    } else {
        try {
            if (empty($_POST['tutor_id']) || empty($_POST['session_date']) || empty($_POST['session_time'])) {
                throw new Exception("All required fields must be filled out");
            }

            if ((int)$_POST['tutor_id'] !== $selected_tutor['id']) {
                throw new Exception("Tutor mismatch. Please try again.");
            }

            $stmt = $conn->prepare("INSERT INTO bookings (student_id, tutor_id, session_date, session_time, status, notes) 
                                   VALUES (?, ?, ?, ?, 'pending', ?)");
            $result = $stmt->execute([
                $student_id,
                $selected_tutor['id'],
                $_POST['session_date'],
                $_POST['session_time'],
                $_POST['notes'] ?? ''
            ]);

            if ($result) {
                header("Location: my-sessions.php?tab=pending&booking=success"); 
                exit();
            } else {
                throw new Exception("Database error occurred while booking the session.");
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get student information for navbar
$stmt_student = $conn->prepare("SELECT name FROM students WHERE id = ?");
$stmt_student->execute([$student_id]);
$student = $stmt_student->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Session - Athena</title>
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
        
        /* Booking Container */
        .booking-container {
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
        
        /* Booking Card */
        .booking-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            background-color: #fff;
            transition: all 0.3s ease;
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        .booking-card .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 1.5rem;
            position: relative;
        }
        
        .booking-card .card-header h3 {
            font-weight: 700;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .booking-card .card-body {
            padding: 2rem;
        }
        
        /* Tutor Info Card */
        .tutor-info-card {
            background-color: rgba(67, 97, 238, 0.05);
            border-radius: 15px;
            border-left: 5px solid var(--primary);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .tutor-info-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .tutor-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .tutor-details h5 {
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--primary-dark);
        }
        
        .tutor-details p {
            margin-bottom: 0.25rem;
            color: #6c757d;
        }
        
        .tutor-details .specialty {
            display: inline-block;
            background-color: rgba(114, 9, 183, 0.1);
            color: var(--secondary);
            border-radius: 50px;
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            margin-top: 0.25rem;
            font-weight: 600;
        }
        
        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: none;
        }
        
        .form-text {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .btn {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2);
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            box-shadow: 0 6px 15px rgba(108, 117, 125, 0.3);
            transform: translateY(-2px);
        }
        
        /* Alerts */
        .alert {
            border-radius: 10px;
            padding: 1rem 1.5rem;
            border: none;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--accent);
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #e9ecef;
            margin-bottom: 1rem;
        }
        
        .empty-state h5 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.75rem 1rem;
            }
            
            .page-header h2 {
                font-size: 1.8rem;
            }
            
            .booking-card .card-header {
                padding: 1.25rem;
            }
            
            .booking-card .card-body {
                padding: 1.5rem;
            }
            
            .tutor-info-card {
                padding: 1rem;
                flex-direction: column;
                text-align: center;
            }
            
            .tutor-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .btn {
                padding: 0.6rem 1.25rem;
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
                        <a class="nav-link" href="my-sessions.php">My Sessions</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($student['name'] ?? 'Student'); ?>
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

    <div class="booking-container">
        <div class="container">
            <div class="page-header">
                <h2>Book a Session</h2>
                <p class="text-muted">Schedule one-on-one time with your chosen tutor</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="booking-card">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar-plus me-2"></i> New Session Request</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                                <?php if (!$selected_tutor || (isset($_GET['tutor_id']) && !$selected_tutor) ): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-user-graduate"></i>
                                        <h5>No Tutor Selected</h5>
                                        <p>You need to select a tutor before booking a session.</p>
                                        <a href="find-tutor.php" class="btn btn-primary">
                                            <i class="fas fa-search me-2"></i> Find a Tutor
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($selected_tutor && empty($error) || ($selected_tutor && $_SERVER['REQUEST_METHOD'] == 'POST' && !empty($error))): ?>
                                <div class="tutor-info-card">
                                    <img src="<?php echo !empty($selected_tutor['profile_image']) ? htmlspecialchars($selected_tutor['profile_image']) : 'person-circle.svg'; ?>" alt="Tutor" class="tutor-avatar">
                                    <div class="tutor-details">
                                        <h5><?php echo htmlspecialchars($selected_tutor['name']); ?></h5>
                                        <?php if (!empty($selected_tutor['email'])): ?>
                                            <p><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($selected_tutor['email']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($selected_tutor['specialty'])): ?>
                                            <span class="specialty"><i class="fas fa-graduation-cap me-1"></i><?php echo htmlspecialchars($selected_tutor['specialty']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            
                                <form method="POST" action="book-session.php?tutor_id=<?php echo htmlspecialchars($selected_tutor['id']); ?>">
                                    <input type="hidden" name="tutor_id" value="<?php echo htmlspecialchars($selected_tutor['id']); ?>">
                                    
                                    <div class="mb-4">
                                        <label for="session_date" class="form-label">Session Date</label>
                                        <input type="date" id="session_date" name="session_date" class="form-control" required 
                                               min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['session_date']) ? htmlspecialchars($_POST['session_date']) : ''; ?>">
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i> Select a date in the future
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="session_time" class="form-label">Session Time</label>
                                        <input type="time" id="session_time" name="session_time" class="form-control" required 
                                               value="<?php echo isset($_POST['session_time']) ? htmlspecialchars($_POST['session_time']) : ''; ?>">
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i> Choose a time that works for you
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="notes" class="form-label">Session Notes</label>
                                        <textarea id="notes" name="notes" class="form-control" 
                                                  placeholder="Describe what you'd like to learn, topics to cover, or any specific questions you have..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i> Help your tutor prepare by providing details
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-calendar-check me-2"></i> Request Session
                                        </button>
                                        <a href="find-tutor.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i> Choose Another Tutor
                                        </a>
                                    </div>
                                </form>
                            <?php elseif (empty($error)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-user-graduate"></i>
                                    <h5>No Tutor Selected</h5>
                                    <p>You need to select a tutor before booking a session.</p>
                                    <a href="find-tutor.php" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i> Find a Tutor
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Set default date to today
            if (!$('#session_date').val()) {
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                const formattedDate = `${yyyy}-${mm}-${dd}`;
                $('#session_date').val(formattedDate);
            }
            
            // Set default time if not set
            if (!$('#session_time').val()) {
                // Set to current hour + 1 as default
                const now = new Date();
                const hours = String(now.getHours() + 1).padStart(2, '0');
                const minutes = '00';
                $('#session_time').val(`${hours}:${minutes}`);
            }
        });
    </script>
</body>
</html>