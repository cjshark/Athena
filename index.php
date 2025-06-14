<?php
require 'db.php';

try {
    // Get featured tutors for the homepage with their average ratings
    $sql = "
        SELECT t.id, t.name, t.specialty, t.description, t.profile_image, t.experience, t.bio,
               AVG(b.rating) as avg_rating, 
               COUNT(b.rating) as review_count
        FROM tutors t
        LEFT JOIN bookings b ON t.id = b.tutor_id AND b.rating IS NOT NULL
        GROUP BY t.id, t.name, t.specialty, t.description, t.profile_image, t.experience, t.bio
        ORDER BY RAND() 
        LIMIT 4
    ";
    $stmt = $conn->query($sql);
    $tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tutorIds = array_column($tutors, 'id');
    
    // Fetch unique subjects for dropdown
    $stmt_subjects = $conn->query("SELECT DISTINCT specialty FROM tutors WHERE specialty IS NOT NULL AND specialty != ''");
    $subjects = $stmt_subjects->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Database error in index.php: " . $e->getMessage());
    $tutors = [];
    $tutorIds = [];
    $subjects = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Athena - Online Tutoring Website</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel="icon" href="athenaLogo.png" type="athena logo">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  
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
    }
    
    /* Modern Navbar */
    .navbar {
      padding: 1rem 2rem;
      background: #fff;
      box-shadow: 0 2px 15px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
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
      color: var(--dark);
      margin: 0 0.5rem;
      position: relative;
      transition: all 0.3s ease;
    }
    
    .nav-link:hover {
      color: var(--primary);
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
    
    .nav-link:hover::after {
      width: 100%;
    }
    
    .nav-link.login-btn {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border-radius: 50px;
      padding: 0.5rem 1.5rem;
      margin-left: 1rem;
      box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }
    
    .nav-link.login-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(67, 97, 238, 0.4);
    }
    
    /* Hero Section */
    .hero-section {
      background: linear-gradient(135deg, #e9f1ff 0%, #f0e6ff 100%);
      padding: 5rem 0;
      border-radius: 0 0 50px 50px;
      margin-bottom: 4rem;
    }
    
    .hero-content {
      padding: 2rem 0;
    }
    
    .hero-title {
      font-size: 3.2rem;
      font-weight: 800;
      line-height: 1.2;
      color: var(--dark);
      margin-bottom: 1.5rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .hero-subtitle {
      font-size: 1.2rem;
      color: #495057;
      margin-bottom: 2rem;
      max-width: 80%;
    }
    
    .hero-image {
      border-radius: 20px;
      box-shadow: 0 15px 30px rgba(0,0,0,0.1);
      transform: perspective(1000px) rotateY(-5deg);
      transition: all 0.5s ease;
      border: 8px solid white;
    }
    
    .hero-image:hover {
      transform: perspective(1000px) rotateY(0deg);
    }
    
    .btn-find-tutor {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border-radius: 50px;
      padding: 0.8rem 2rem;
      font-weight: 600;
      box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
      border: none;
      transition: all 0.3s ease;
    }
    
    .btn-find-tutor:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
      color: white;
    }
    
    .subject-select {
      border-radius: 50px;
      padding: 0.8rem 1.5rem;
      border: 1px solid #ced4da;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    /* Subjects Section */
    .subjects-section {
      background-color: white;
      padding: 3rem 0;
      border-radius: 20px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.05);
      margin-bottom: 4rem;
    }
    
    .subject-chip {
      display: inline-block;
      background: var(--light);
      border-radius: 50px;
      padding: 0.5rem 1.5rem;
      margin: 0.5rem;
      font-weight: 600;
      color: var(--dark);
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      border: 1px solid var(--gray-light);
    }
    
    .subject-chip:hover {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
      transform: translateY(-2px);
    }
    
    .stats-container {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      margin-top: 2rem;
      padding-top: 2rem;
      border-top: 1px solid var(--gray-light);
    }
    
    .stat-item {
      text-align: center;
      padding: 1rem;
      flex: 1;
      min-width: 150px;
    }
    
    .stat-number {
      font-size: 2rem;
      font-weight: 800;
      color: var(--primary);
      margin-bottom: 0.5rem;
    }
    
    .stat-text {
      font-weight: 600;
      color: #6c757d;
    }
    
    /* Tutors Section */
    .tutors-section {
      padding: 4rem 0;
    }
    
    .section-title {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 3rem;
      text-align: center;
      position: relative;
      padding-bottom: 1rem;
    }
    
    .section-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 2px;
    }
    
    .tutor-card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      margin-bottom: 2rem;
      border: none;
    }
    
    .tutor-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }
    
    .tutor-image-wrapper {
      padding: 1.5rem;
      text-align: center;
    }
    
    .tutor-image {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid white;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .tutor-info {
      padding: 1.5rem;
    }
    
    .tutor-name {
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    
    .tutor-specialty-badge {
      display: inline-block;
      background: #e9f1ff;
      color: var(--primary);
      padding: 0.25rem 0.75rem;
      border-radius: 50px;
      font-size: 0.85rem;
      font-weight: 600;
      margin-right: 0.5rem;
      margin-bottom: 0.5rem;
    }
    
    .experience-badge {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--dark);
    }
    
    .tutor-description {
      color: #6c757d;
      font-size: 0.95rem;
      margin: 1rem 0;
      max-height: 120px;
      overflow: hidden;
    }
    
    .tutor-rating {
      margin-bottom: 1rem;
    }
    
    .tutor-action {
      padding: 1.5rem;
      border-top: 1px solid var(--gray-light);
    }
    
    .btn-book-tutor {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border-radius: 50px;
      padding: 0.7rem 1.5rem;
      font-weight: 600;
      width: 100%;
      border: none;
      transition: all 0.3s ease;
    }
    
    .btn-book-tutor:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
      color: white;
    }
    
    .feedback-container {
      padding: 1rem;
      border-radius: 10px;
      background-color: #f8f9fa;
      margin-top: 1rem;
    }
    
    .feedback-item {
      font-style: italic;
      padding: 0.5rem;
      border-left: 3px solid var(--primary);
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }
    
    /* Footer */
    .footer {
      background: linear-gradient(135deg, #2b2d42 0%, #1a1a2e 100%);
      color: white;
      padding: 4rem 0 2rem;
      border-radius: 50px 50px 0 0;
      margin-top: 4rem;
    }
    
    .footer-logo {
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: 1rem;
      color: white;
    }
    
    .footer-links {
      list-style: none;
      padding: 0;
    }
    
    .footer-links li {
      margin-bottom: 0.8rem;
    }
    
    .footer-links a {
      color: rgba(255,255,255,0.8);
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .footer-links a:hover {
      color: white;
      padding-left: 5px;
    }
    
    .footer-heading {
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      color: white;
    }
    
    .social-links {
      display: flex;
      margin-top: 1rem;
    }
    
    .social-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255,255,255,0.1);
      color: white;
      margin-right: 0.5rem;
      transition: all 0.3s ease;
    }
    
    .social-icon:hover {
      background: var(--primary);
      transform: translateY(-3px);
    }
    
    .copyright {
      text-align: center;
      padding-top: 2rem;
      border-top: 1px solid rgba(255,255,255,0.1);
      margin-top: 2rem;
      color: rgba(255,255,255,0.6);
      font-size: 0.9rem;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
      .hero-title {
        font-size: 2.5rem;
      }
      
      .hero-subtitle {
        max-width: 100%;
      }
      
      .hero-section {
        padding: 3rem 0;
      }
      
      .hero-image {
        margin-bottom: 2rem;
      }
    }
    
    @media (max-width: 768px) {
      .navbar {
        padding: 1rem;
      }
      
      .nav-link.login-btn {
        margin-top: 1rem;
        display: inline-block;
      }
      
      .hero-title {
        font-size: 2rem;
      }
      
      .stat-item {
        min-width: 120px;
      }
    }
  </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">Athena</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="find-fl.php">Find a Tutor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="SignUpTutor.php">Become a Tutor</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link login-btn" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content order-2 order-lg-1">
                    <h1 class="hero-title">Ignite Your Learning Journey</h1>
                    <p class="hero-subtitle">Unlock your learning potential and achieve academic success with personalized online tutoring. Get the grades you deserve and build a brighter future.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <button class="btn btn-find-tutor" id="findTutorBtn">
                            <i class="fas fa-search me-2"></i>Find a Tutor
                        </button>
                        <select id="subjectSelect" class="form-select subject-select">
                            <option value="">All Subjects</option>
                            <?php if (!empty($subjects)): ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo htmlspecialchars($subject); ?>"><?php echo htmlspecialchars($subject); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-lg-6 order-1 order-lg-2 mb-4 mb-lg-0">
                    <img src="bannerstudent.png" class="img-fluid hero-image" alt="Student learning online">
                </div>
            </div>
        </div>
    </section>

    <!-- Subjects Section -->
    <section class="subjects-section">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="h3 fw-bold">Popular Subjects</h2>
                <p class="text-muted">Find expert tutors in these popular fields</p>
            </div>
            
            <div class="text-center">
                <?php if (!empty($subjects)): ?>
                    <?php foreach (array_slice($subjects, 0, 7) as $subject): ?>
                        <a href="find-fl.php?subject=<?php echo urlencode($subject); ?>" class="subject-chip">
                            <?php echo htmlspecialchars($subject); ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <a href="#" class="subject-chip">Foreign Language</a>
                    <a href="#" class="subject-chip">Mathematics</a>
                    <a href="#" class="subject-chip">Art</a>
                    <a href="#" class="subject-chip">History</a>
                    <a href="#" class="subject-chip">Music</a>
                    <a href="#" class="subject-chip">Language</a>
                    <a href="#" class="subject-chip">Drama</a>
                <?php endif; ?>
            </div>
            
            <div class="stats-container">
                <div class="stat-item">
                    <div class="stat-number">5000+</div>
                    <div class="stat-text">Reviews</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-text">5-star reviews</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">2000+</div>
                    <div class="stat-text">Students served</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><i class="fas fa-award"></i></div>
                    <div class="stat-text">AACCUP Accredited</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Tutors Section -->
    <section class="tutors-section">
        <div class="container">
            <h2 class="section-title">Meet Our Top Tutors</h2>
            
            <?php if (!empty($tutors)): ?>
                <div class="row">
                    <?php foreach ($tutors as $tutor): ?>
                        <div class="col-md-6 col-lg-3 mb-4">
                            <div class="tutor-card h-100 d-flex flex-column">
                                <div class="tutor-image-wrapper">
                                    <img src="<?php echo !empty($tutor['profile_image']) ? htmlspecialchars($tutor['profile_image']) : 'blankprofile.png'; ?>" 
                                         class="tutor-image" alt="Profile picture of <?php echo htmlspecialchars($tutor['name']); ?>">
                                </div>
                                <div class="tutor-info flex-grow-1">
                                    <h3 class="tutor-name"><?php echo htmlspecialchars($tutor['name']); ?></h3>
                                    
                                    <?php if (!empty($tutor['specialty'])): ?>
                                        <div class="tutor-specialties">
                                            <?php 
                                            $specialties = explode(',', $tutor['specialty']);
                                            foreach ($specialties as $spec) {
                                                echo '<span class="tutor-specialty-badge">' . htmlspecialchars(trim($spec)) . '</span>';
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($tutor['experience'])): ?>
                                    <div class="d-flex align-items-center mt-2 mb-2">
                                        <i class="fas fa-briefcase text-primary me-2"></i>
                                        <span class="experience-badge">
                                            <?php echo intval($tutor['experience']); ?> <?php echo intval($tutor['experience']) === 1 ? 'year' : 'years'; ?> of experience
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="tutor-rating mt-2" id="rating-<?php echo $tutor['id']; ?>">
                                        <span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading ratings...</span>
                                    </div>
                                    
                                    <p class="tutor-description">
                                        <?php 
                                        // Prioritize bio if available, otherwise use description
                                        $displayText = !empty($tutor['bio']) ? $tutor['bio'] : ($tutor['description'] ?? 'No description available.');
                                        echo nl2br(htmlspecialchars(substr($displayText, 0, 150))); 
                                        if (strlen($displayText) > 150) echo '...';
                                        ?>
                                    </p>
                                    
                                    <div class="feedback-container">
                                        <div class="fw-bold mb-2"><i class="fas fa-comment-dots me-1"></i> Student Feedback</div>
                                        <div id="feedbacks-<?php echo $tutor['id']; ?>">
                                            <div class="text-center text-muted">
                                                <small><i class="fas fa-spinner fa-spin"></i> Loading feedback...</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tutor-action">
                                    <a href="login.php" class="btn btn-book-tutor">
                                        <i class="fas fa-calendar-check me-2"></i>Book Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-4">
                    <a href="find-fl.php" class="btn btn-find-tutor">
                        <i class="fas fa-users me-2"></i>See All Tutors
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>No tutors available at the moment. Please check back later.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modern Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-logo">Athena</div>
                    <p class="mb-4">We connect passionate educators with eager students to create meaningful learning experiences that transform lives.</p>
                    <div class="social-links">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="footer-heading">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="find-fl.php">Find a Tutor</a></li>
                        <li><a href="SignUpTutor.php">Become a Tutor</a></li>
                        <li><a href="#">About Us</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="footer-heading">Subjects</h5>
                    <ul class="footer-links">
                        <?php if (!empty($subjects)): ?>
                            <?php foreach (array_slice($subjects, 0, 5) as $subject): ?>
                                <li><a href="find-fl.php?subject=<?php echo urlencode($subject); ?>"><?php echo htmlspecialchars($subject); ?></a></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><a href="#">Mathematics</a></li>
                            <li><a href="#">Languages</a></li>
                            <li><a href="#">Science</a></li>
                            <li><a href="#">Arts</a></li>
                            <li><a href="#">History</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4 mb-4">
                    <h5 class="footer-heading">Contact Us</h5>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 Education Ave, Learning City</li>
                        <li><i class="fas fa-phone me-2"></i> (123) 456-7890</li>
                        <li><i class="fas fa-envelope me-2"></i> info@athena-tutoring.com</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> Athena Tutoring. All rights reserved.
            </div>
        </div>
    </footer>

  <script>
    $(document).ready(function() {
      $('#findTutorBtn').on('click', function() {
        const selectedSubject = $('#subjectSelect').val();
        let url = 'find-fl.php';
        if (selectedSubject) {
          url += '?subject=' + encodeURIComponent(selectedSubject);
        }
        window.location.href = url;
      });
    });

    var tutorIds = <?php echo json_encode($tutorIds); ?>;
    if (tutorIds.length > 0) {
        $.ajax({
            url: 'fetch-tutor-reviews.php',
            method: 'POST',
            data: { tutor_ids: tutorIds },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    for (var tid in resp.data) {
                        var data = resp.data[tid];
                        var rating = data.avg_rating ? parseFloat(data.avg_rating) : 0;
                        var reviewCount = data.review_count || 0;
                        var starsHtml = '';
                        for (var i = 1; i <= 5; i++) {
                            if (rating >= i) {
                                starsHtml += '<i class="fas fa-star text-warning"></i>';
                            } else if (rating > i - 1 && rating < i) {
                                starsHtml += '<i class="fas fa-star-half-alt text-warning"></i>';
                            } else {
                                starsHtml += '<i class="far fa-star text-warning"></i>';
                            }
                        }
                        starsHtml += rating ? `<span class='ms-2 text-dark small'>(${rating}/5)</span>` : `<span class='ms-2 text-muted small'>(No ratings)</span>`;
                        starsHtml += `<div class='text-muted small'>${reviewCount} review${reviewCount == 1 ? '' : 's'}</div>`;
                        $('#rating-' + tid).html(starsHtml);
                        
                        var feedbacksHtml = '';
                        if (data.feedbacks && data.feedbacks.length > 0) {
                            data.feedbacks.forEach(function(fb) {
                                feedbacksHtml += `<div class='border rounded p-2 mb-1 bg-light'><span class='fw-bold'>"</span>${$('<div>').text(fb.feedback).html()}<span class='fw-bold'>"</span><br/><span class='text-warning'>`;
                                for (var j = 1; j <= 5; j++) {
                                    feedbacksHtml += j <= fb.rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                }
                                feedbacksHtml += `</span></div>`;
                            });
                        }
                        $('#feedbacks-' + tid).html(feedbacksHtml);
                    }
                } else {
                    $('.tutor-rating').html('<span class="text-danger">Error loading reviews</span>');
                }
            },
            error: function() {
                $('.tutor-rating').html('<span class="text-danger">Error loading reviews</span>');
            }
        });
    }
  </script>

</body>

</html>
