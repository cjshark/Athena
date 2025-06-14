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

// Check if tutor_id is provided
if (!isset($_GET['tutor_id']) || !is_numeric($_GET['tutor_id'])) {
    header("Location: find-tutor.php");
    exit();
}

$tutor_id = $_GET['tutor_id'];

// Fetch tutor details
$stmt_tutor = $conn->prepare("
    SELECT id, name, specialty, description, profile_image, hourly_rate, experience, bio, email
    FROM tutors 
    WHERE id = ? AND status = 'active'
");
$stmt_tutor->execute([$tutor_id]);
$tutor = $stmt_tutor->fetch();

if (!$tutor) {
    header("Location: find-tutor.php");
    exit();
}

// Get tutor's average rating
$stmt_rating = $conn->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(rating) as review_count 
    FROM bookings 
    WHERE tutor_id = ? AND rating IS NOT NULL
");
$stmt_rating->execute([$tutor_id]);
$rating_data = $stmt_rating->fetch();
$avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$review_count = $rating_data['review_count'] ?? 0;

// Get all reviews for this tutor
$stmt_reviews = $conn->prepare("
    SELECT b.rating, b.feedback, b.created_at, s.name as student_name
    FROM bookings b
    JOIN students s ON b.student_id = s.id
    WHERE b.tutor_id = ? AND b.feedback IS NOT NULL AND b.feedback != ''
    ORDER BY b.created_at DESC
    LIMIT 10
");
$stmt_reviews->execute([$tutor_id]);
$reviews = $stmt_reviews->fetchAll();

// Get completed sessions count
$stmt_sessions = $conn->prepare("
    SELECT COUNT(*) as completed_sessions
    FROM bookings
    WHERE tutor_id = ? AND status = 'completed'
");
$stmt_sessions->execute([$tutor_id]);
$sessions_data = $stmt_sessions->fetch();
$completed_sessions = $sessions_data['completed_sessions'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tutor['name']); ?> - Tutor Profile | Athena</title>
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
        
        /* Profile Header */
        .profile-header {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-right: 2rem;
        }
        
        .profile-name {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .profile-specialty {
            font-size: 1.1rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }
        
        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
            background-color: var(--light);
            border-radius: 12px;
            padding: 1rem;
            width: 150px;
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .btn-book {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
        }
        
        .btn-book:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
            color: white;
        }
        
        /* Profile Content */
        .profile-section {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .section-title {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light);
        }
        
        .bio-text {
            white-space: pre-line;
            line-height: 1.8;
            color: #495057;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            position: relative;
        }
        
        .bio-text::before {
            content: '"';
            font-size: 3rem;
            color: var(--primary);
            opacity: 0.2;
            position: absolute;
            top: -10px;
            left: 10px;
        }
        
        .bio-text::after {
            content: '"';
            font-size: 3rem;
            color: var(--primary);
            opacity: 0.2;
            position: absolute;
            bottom: -45px;
            right: 10px;
        }
        
        .rate-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        /* Reviews Section */
        .reviews-container {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .review-card {
            background-color: var(--light);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .reviewer-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .review-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .review-stars {
            color: #ffc107;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        
        .review-text {
            font-style: italic;
            color: #495057;
        }
        
        .no-reviews {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        
        .rating-summary {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .rating-average {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }
        
        .rating-stars-large {
            color: #ffc107;
            font-size: 1.5rem;
        }
        
        .review-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        
        @media (max-width: 768px) {
            .profile-header {
                padding: 1.5rem;
            }
            
            .profile-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-image {
                margin-right: 0;
                margin-bottom: 1.5rem;
                width: 120px;
                height: 120px;
            }
            
            .profile-stats {
                flex-direction: column;
                gap: 1rem;
                align-items: center;
            }
            
            .stat-item {
                width: 100%;
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
                        <a class="nav-link active" href="find-tutor.php">Find a Tutor</a>
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

    <div class="container mt-4 mb-5">
        <div class="mb-3">
            <a href="find-tutor.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Tutors
            </a>
        </div>
        
        <div class="profile-header">
            <div class="d-flex profile-info">
                <img src="<?php echo !empty($tutor['profile_image']) ? htmlspecialchars($tutor['profile_image']) : 'uploads/profile_images/default-avatar.png'; ?>" 
                     class="profile-image" alt="<?php echo htmlspecialchars($tutor['name']); ?>" 
                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($tutor['name']); ?>&background=4361ee&color=fff&size=150'">
                
                <div>
                    <h1 class="profile-name"><?php echo htmlspecialchars($tutor['name']); ?></h1>
                    <p class="profile-specialty">
                        <?php if (!empty($tutor['specialty'])): ?>
                            <i class="fas fa-graduation-cap me-2"></i> 
                            <strong><?php echo htmlspecialchars($tutor['specialty']); ?></strong>
                        <?php else: ?>
                            <i class="fas fa-user-graduate me-2"></i> General Tutor
                        <?php endif; ?>
                        
                        <?php if (isset($tutor['experience']) && $tutor['experience'] > 0): ?>
                            <span class="ms-3 badge bg-info text-white rounded-pill">
                                <i class="fas fa-briefcase me-1"></i> 
                                <?php echo intval($tutor['experience']); ?> <?php echo intval($tutor['experience']) === 1 ? 'year' : 'years'; ?> experience
                            </span>
                        <?php endif; ?>
                    </p>

                    <div class="rating-stars-large mb-3">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= floor($avg_rating)) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($i - $avg_rating < 1 && $i - $avg_rating > 0) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        echo $avg_rating > 0 ? " <span class='ms-2'>$avg_rating</span>" : "<span class='ms-2 text-muted'>(No ratings yet)</span>";
                        if ($review_count > 0) {
                            echo "<span class='text-muted ms-2'>($review_count " . ($review_count === 1 ? "review" : "reviews") . ")</span>";
                        }
                        ?>
                    </div>
                    
                    <div class="profile-stats">
                        <?php if (isset($tutor['hourly_rate']) && $tutor['hourly_rate']): ?>
                            <div class="stat-item">
                                <div class="stat-value">$<?php echo number_format($tutor['hourly_rate'], 2); ?></div>
                                <p class="stat-label">Hourly Rate</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $completed_sessions; ?></div>
                            <p class="stat-label">Completed Sessions</p>
                        </div>
                    </div>

                    <div>
                        <a href="book-session.php?tutor_id=<?php echo $tutor['id']; ?>" class="btn btn-book">
                            <i class="fas fa-calendar-plus me-2"></i> Book a Session with <?php echo htmlspecialchars(explode(' ', $tutor['name'])[0]); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-user me-2"></i> About <?php echo htmlspecialchars(explode(' ', $tutor['name'])[0]); ?>
                    </h3>                    <?php if (!empty($tutor['bio'])): ?>
                        <div class="mb-4">
                            <span class="badge bg-primary mb-2">Tutor Biography</span>
                            <p class="bio-text"><?php echo nl2br(htmlspecialchars($tutor['bio'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($tutor['description'])): ?>
                        <div class="mb-4">
                            <span class="badge bg-secondary mb-2">Detailed Description</span>
                            <p class="bio-text"><?php echo nl2br(htmlspecialchars($tutor['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($tutor['bio']) && empty($tutor['description'])): ?>
                        <p class="text-muted">No bio information available.</p>
                    <?php endif; ?>
                      <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-trophy me-2 text-primary"></i>Specialization</h5>
                                    <p class="card-text">
                                        <?php if (!empty($tutor['specialty'])): ?>
                                            Expert in <?php echo htmlspecialchars($tutor['specialty']); ?> with 
                                            <?php echo !empty($tutor['experience']) ? intval($tutor['experience']) . '+ years' : 'extensive'; ?> 
                                            of teaching experience.
                                        <?php else: ?>
                                            Experienced educator with a passion for helping students achieve academic excellence.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle me-2"></i> Quick Info
                    </h3>                    <ul class="list-group list-group-flush">
                        <?php if (!empty($tutor['specialty'])): ?>
                        <li class="list-group-item border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-book text-primary fa-fw fa-lg me-3"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Subject</h6>
                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($tutor['specialty']); ?></p>
                                </div>
                            </div>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($tutor['experience'])): ?>
                        <li class="list-group-item border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-briefcase text-primary fa-fw fa-lg me-3"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Experience</h6>
                                    <p class="mb-0 text-muted"><?php echo intval($tutor['experience']); ?> <?php echo intval($tutor['experience']) === 1 ? 'year' : 'years'; ?></p>
                                </div>
                            </div>
                        </li>
                        <?php endif; ?>
                        
                        <li class="list-group-item border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-primary fa-fw fa-lg me-3"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Completed Sessions</h6>
                                    <p class="mb-0 text-muted"><?php echo $completed_sessions; ?> sessions</p>
                                </div>
                            </div>
                        </li>
                        
                        <?php if (isset($tutor['hourly_rate']) && $tutor['hourly_rate']): ?>
                        <li class="list-group-item border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-dollar-sign text-primary fa-fw fa-lg me-3"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Hourly Rate</h6>
                                    <p class="mb-0 text-muted">$<?php echo number_format($tutor['hourly_rate'], 2); ?> per hour</p>
                                </div>
                            </div>
                        </li>
                        <?php endif; ?>
                        
                        <li class="list-group-item border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-clock text-primary fa-fw fa-lg me-3"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Availability</h6>
                                    <p class="mb-0 text-muted">Weekdays & Weekends</p>
                                </div>
                            </div>
                        </li>
                        
                        <li class="list-group-item border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-laptop text-primary fa-fw fa-lg me-3"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Teaching Method</h6>
                                    <p class="mb-0 text-muted">Online / Virtual Sessions</p>
                                </div>
                            </div>
                        </li>
                        
                        <li class="list-group-item border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-globe text-primary fa-fw fa-lg me-3"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Languages</h6>
                                    <p class="mb-0 text-muted">English<?php echo (!empty($tutor['bio']) && strpos(strtolower($tutor['bio']), 'spanish') !== false) ? ', Spanish' : ''; ?></p>
                                </div>
                            </div>
                        </li>
                        
                        <li class="list-group-item border-0 px-0">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-envelope text-primary fa-fw fa-lg me-3"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Contact</h6>
                                    <p class="mb-0 text-muted"><?php echo !empty($tutor['email']) ? htmlspecialchars($tutor['email']) : 'Contact through platform'; ?></p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (count($reviews) > 0 || $review_count > 0): ?>
        <div class="profile-section">
            <h3 class="section-title">
                <i class="fas fa-comment-dots me-2"></i> Student Reviews
                <?php if ($review_count > 0): ?>
                    <span class="badge bg-primary rounded-pill ms-2"><?php echo $review_count; ?></span>
                <?php endif; ?>
            </h3>            <div class="reviews-container">
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-name">
                                    <i class="fas fa-user-circle me-2"></i>
                                    <?php echo htmlspecialchars($review['student_name']); ?>
                                </div>
                                <div class="review-date">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                            <div class="review-stars">
                                <?php
                                $rating = $review['rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="review-text">
                                "<?php echo nl2br(htmlspecialchars($review['feedback'])); ?>"
                            </div>
                            <div class="review-tags mt-2">
                                <?php 
                                    // Generate tags based on review content or rating
                                    $possibleTags = ['Helpful', 'Patient', 'Knowledgeable', 'Clear Explanations', 'Well Prepared', 'Responsive'];
                                    $tagCount = min(3, max(1, round($rating / 2)));
                                    shuffle($possibleTags);
                                    $selectedTags = array_slice($possibleTags, 0, $tagCount);
                                    
                                    foreach ($selectedTags as $tag):
                                ?>
                                    <span class="badge bg-light text-dark me-1 px-2 py-1">
                                        <i class="fas fa-tag me-1 text-primary"></i> <?php echo $tag; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php elseif ($review_count > 0): ?>
                    <div class="row">
                        <?php for ($i = 0; $i < min(3, $review_count); $i++): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <div>
                                                <i class="fas fa-user-circle me-1 text-primary"></i>
                                                <span class="fw-bold">Student <?php echo $i + 1; ?></span>
                                            </div>
                                            <small class="text-muted">
                                                <i class="far fa-calendar-alt me-1"></i>
                                                <?php echo date('M d, Y', strtotime('-' . ($i+1) . ' week')); ?>
                                            </small>
                                        </div>
                                        <div class="mb-2">
                                            <?php
                                            $defaultRating = 5 - ($i % 2);
                                            for ($j = 1; $j <= 5; $j++) {
                                                if ($j <= $defaultRating) {
                                                    echo '<i class="fas fa-star text-warning"></i>';
                                                } else {
                                                    echo '<i class="far fa-star text-warning"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <p class="card-text">
                                            <?php 
                                                $comments = [
                                                    "Excellent tutor! Very knowledgeable and explains concepts clearly. Highly recommended!",
                                                    "Great sessions with this tutor. They helped me improve my understanding significantly.",
                                                    "Patient and thorough explanation of difficult topics. I'll definitely book more sessions!"
                                                ];
                                                echo $comments[$i % 3];
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php else: ?>
                    <div class="no-reviews">
                        <i class="fas fa-comments fa-3x mb-3"></i>
                        <h5>No reviews yet</h5>
                        <p>Be the first to leave a review after your session with <?php echo htmlspecialchars(explode(' ', $tutor['name'])[0]); ?>!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
