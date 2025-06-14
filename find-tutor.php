<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
require 'db.php';

$stmt_student = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt_student->execute([$_SESSION['user_id']]);
$student = $stmt_student->fetch();

// Update query to include profile_image, hourly_rate, experience, and bio
$stmt_tutors = $conn->prepare("SELECT id, name, specialty, description, profile_image, hourly_rate, experience, bio FROM tutors WHERE status = 'active'");
$stmt_tutors->execute();
$tutors = $stmt_tutors->fetchAll();

foreach ($tutors as &$tutor) {
    $stmt_rating = $conn->prepare("SELECT AVG(rating) as avg_rating FROM bookings WHERE tutor_id = ? AND rating IS NOT NULL");
    $stmt_rating->execute([$tutor['id']]);
    $row = $stmt_rating->fetch();
    $tutor['rating'] = $row && $row['avg_rating'] !== null ? round($row['avg_rating'], 1) : null;
}
unset($tutor);

$tutorFeedbacks = [];
foreach ($tutors as $tutor) {
    $stmt_feedback = $conn->prepare("SELECT feedback, rating FROM bookings WHERE tutor_id = ? AND feedback IS NOT NULL AND feedback != '' ORDER BY id DESC LIMIT 2");
    $stmt_feedback->execute([$tutor['id']]);
    $tutorFeedbacks[$tutor['id']] = $stmt_feedback->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Tutor - Athena</title>
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
        
        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 1rem;
        }
        
        .page-header h2 {
            color: var(--primary);
            font-weight: 800;
            position: relative;
        }
        
        /* Filter Controls */
        .filter-controls {
            background-color: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .search-bar {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .search-bar:focus {
            box-shadow: 0 3px 15px rgba(67, 97, 238, 0.15);
            border-color: var(--primary);
        }
        
        /* Tutor Cards */
        .tutor-card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .tutor-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .tutor-profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .tutor-profile-image {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .tutor-info {
            margin-left: 1.2rem;
            flex: 1;
        }
        
        .tutor-name {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 0.25rem;
            font-size: 1.25rem;
        }
        
        .tutor-specialty {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .rating-stars {
            margin-bottom: 1rem;
            color: #ffc107;
            font-size: 1.1rem;
        }
        
        .rate-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .tutor-description {
            color: #495057;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            flex-grow: 1;
            line-height: 1.7;
        }
        
        .feedback-container {
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
        }
        
        .feedback-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.85rem;
        }
        
        .feedback-item {
            background-color: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.7rem;
            font-style: italic;
            font-size: 0.9rem;
            border-left: 4px solid var(--primary);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .book-btn {
            border-radius: 50px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
            color: white;
        }
        
        .book-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
            color: white;
        }
        
        .no-results {
            background-color: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .no-results i {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .tutor-profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .tutor-info {
                margin-left: 0;
                margin-top: 1rem;
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

    <div class="container mt-4">
        <div class="page-header">
            <h2 class="display-6 fw-bold">Find Your Perfect Tutor</h2>
            <p class="text-muted">Browse our qualified tutors and book a session that fits your learning needs</p>
        </div>
        
        <div class="filter-controls">
            <div class="row align-items-center">
                <div class="col-md-8 mb-3 mb-md-0">
                    <div class="input-group">
                        <span class="input-group-text border-0 bg-white">
                            <i class="fas fa-search text-primary"></i>
                        </span>
                        <input type="text" class="form-control search-bar border-start-0" id="searchTutor" placeholder="Search by name, subject, or specialty...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select search-bar" id="filterSpecialty">
                        <option value="">All Specialties</option>
                        <?php
                        $specialties = [];
                        foreach ($tutors as $tutor) {
                            if (!empty($tutor['specialty']) && !in_array($tutor['specialty'], $specialties)) {
                                $specialties[] = $tutor['specialty'];
                                echo '<option value="' . htmlspecialchars($tutor['specialty']) . '">' . htmlspecialchars($tutor['specialty']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <?php if ($tutors): ?>
            <div class="row" id="tutorsList">
                <?php foreach ($tutors as $tutor): ?>
                    <div class="col-md-6 col-lg-4 mb-4 tutor-item" data-specialty="<?php echo htmlspecialchars($tutor['specialty'] ?? ''); ?>">
                        <div class="card tutor-card h-100">
                            <div class="card-body">
                                <div class="tutor-profile-header">
                                    <img src="<?php echo !empty($tutor['profile_image']) ? htmlspecialchars($tutor['profile_image']) : 'uploads/profile_images/default-avatar.png'; ?>" 
                                         class="tutor-profile-image" alt="<?php echo htmlspecialchars($tutor['name']); ?>" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($tutor['name']); ?>&background=4361ee&color=fff&size=90'">
                                    <div class="tutor-info">
                                        <h5 class="tutor-name"><?php echo htmlspecialchars($tutor['name']); ?></h5>
                                        <p class="tutor-specialty">
                                            <?php if (!empty($tutor['specialty'])): ?>
                                                <i class="fas fa-graduation-cap me-1"></i> 
                                                <?php echo htmlspecialchars($tutor['specialty']); ?>
                                            <?php else: ?>
                                                <span class="text-muted"><i class="fas fa-user-graduate me-1"></i> General Tutor</span>
                                            <?php endif; ?>
                                        </p>
                                        <?php if (isset($tutor['experience']) && $tutor['experience'] > 0): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-info text-white rounded-pill">
                                                    <i class="fas fa-briefcase me-1"></i> 
                                                    <?php echo intval($tutor['experience']); ?> <?php echo intval($tutor['experience']) === 1 ? 'year' : 'years'; ?> of experience
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="rating-stars">
                                    <?php
                                        $rating = isset($tutor['rating']) ? round($tutor['rating'], 1) : 0;
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= floor($rating)) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i - $rating < 1) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        echo $rating ? " <span class='ms-2 fw-bold'>($rating)</span>" : "<span class='ms-2 text-muted'>(No ratings yet)</span>";
                                    ?>
                                </div>
                                
                                <?php if (isset($tutor['hourly_rate']) && $tutor['hourly_rate']): ?>
                                    <div class="rate-badge">
                                        <i class="fas fa-dollar-sign me-1"></i>
                                        <?php echo number_format($tutor['hourly_rate'], 2); ?> / hour
                                    </div>
                                <?php endif; ?>
                                
                                <p class="tutor-description" data-full-text="<?php 
                                    $displayText = !empty($tutor['bio']) ? $tutor['bio'] : ($tutor['description'] ?? 'No information available.');
                                    echo htmlspecialchars($displayText);
                                ?>">
                                    <?php 
                                        $displayText = !empty($tutor['bio']) ? $tutor['bio'] : ($tutor['description'] ?? 'No information available.');
                                        echo (strlen($displayText) > 150) ? 
                                            nl2br(htmlspecialchars(substr($displayText, 0, 150) . '...')) : 
                                            nl2br(htmlspecialchars($displayText)); 
                                    ?>
                                </p>
                                
                                <?php if (!empty($tutorFeedbacks[$tutor['id']])): ?>
                                    <div class="feedback-container">
                                        <p class="feedback-title">
                                            <i class="fas fa-comment-dots me-2"></i> 
                                            Student Feedback
                                        </p>
                                        <?php foreach ($tutorFeedbacks[$tutor['id']] as $fb): ?>
                                            <div class="feedback-item">
                                                <div class="text-warning mb-1">
                                                    <?php
                                                        $fb_rating = (int)$fb['rating'];
                                                        echo str_repeat('<i class="fas fa-star fa-xs"></i>', $fb_rating) . 
                                                             str_repeat('<i class="far fa-star fa-xs"></i>', 5 - $fb_rating);
                                                    ?>
                                                </div>
                                                "<?php echo htmlspecialchars($fb['feedback']); ?>"
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-grid gap-2">
                                    <a href="view-tutor-profile.php?tutor_id=<?php echo $tutor['id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-user me-2"></i> View Full Profile
                                    </a>
                                    <a href="book-session.php?tutor_id=<?php echo $tutor['id']; ?>" class="btn book-btn w-100">
                                        <i class="fas fa-calendar-plus me-2"></i> Book a Session
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="no-results-container" style="display: none;">
                <div class="no-results my-5">
                    <i class="fas fa-search-minus d-block"></i>
                    <h4 class="mb-3">No tutors match your search</h4>
                    <p class="text-muted mb-4">Try adjusting your search terms or clear your filters to see all available tutors.</p>
                    <button class="btn book-btn px-4" id="clearFilters">
                        <i class="fas fa-sync-alt me-2"></i> Clear Filters
                    </button>
                </div>
            </div>
            
        <?php else: ?>
            <div class="no-results my-5">
                <i class="fas fa-user-graduate d-block"></i>
                <h4 class="mb-3">No tutors available</h4>
                <p class="text-muted">Our team is working on bringing more tutors on board. Please check back soon!</p>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Search functionality
            $('#searchTutor').on('input', function() {
                filterTutors();
            });
            
            // Specialty filter functionality
            $('#filterSpecialty').on('change', function() {
                filterTutors();
            });
            
            // Clear filters button
            $('#clearFilters').on('click', function() {
                $('#searchTutor').val('');
                $('#filterSpecialty').val('');
                filterTutors();
            });
            
            // Combined filter function
            function filterTutors() {
                const searchTerm = $('#searchTutor').val().toLowerCase().trim();
                const specialty = $('#filterSpecialty').val().toLowerCase();
                
                let visibleCount = 0;
                
                $('.tutor-item').each(function() {
                    const $this = $(this);
                    const tutorName = $this.find('.tutor-name').text().toLowerCase();
                    const tutorSpecialty = $this.data('specialty').toLowerCase();
                    const tutorDescription = $this.find('.tutor-description').data('full-text').toLowerCase();
                    
                    const matchesSearch = !searchTerm || 
                                        tutorName.includes(searchTerm) || 
                                        tutorSpecialty.includes(searchTerm) || 
                                        tutorDescription.includes(searchTerm);
                                        
                    const matchesSpecialty = !specialty || tutorSpecialty === specialty;
                    
                    if (matchesSearch && matchesSpecialty) {
                        $this.show();
                        visibleCount++;
                    } else {
                        $this.hide();
                    }
                });
                
                // Toggle no results message
                if (visibleCount === 0) {
                    $('#tutorsList').hide();
                    $('#no-results-container').show();
                } else {
                    $('#tutorsList').show();
                    $('#no-results-container').hide();
                }
            }
            
            // Read more functionality
            $('.tutor-description').each(function() {
                const $this = $(this);
                const fullText = $this.data('full-text');
                
                if (fullText && fullText.length > 150) {
                    const truncatedText = fullText.substring(0, 150) + '... <a href="#" class="read-more text-primary">Read more</a>';
                    $this.html(truncatedText);
                }
            });
            
            // Read more click handler
            $(document).on('click', '.read-more', function(e) {
                e.preventDefault();
                const $description = $(this).closest('.tutor-description');
                $description.html(nl2br($description.data('full-text')));
            });
            
            // Helper function to preserve line breaks
            function nl2br(str) {
                return str.replace(/\n/g, '<br>');
            }
        });
    </script>
</body>
</html>