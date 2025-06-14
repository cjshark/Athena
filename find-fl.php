<?php
require 'db.php';

try {
    // Fetch all tutors with their ratings and experience
    $stmt = $conn->query("
        SELECT t.id, t.name, t.specialty, t.hourly_rate, t.experience,
               AVG(b.rating) as avg_rating, 
               COUNT(b.rating) as review_count 
        FROM tutors t
        LEFT JOIN bookings b ON t.id = b.tutor_id AND b.rating IS NOT NULL
        GROUP BY t.id, t.name, t.specialty, t.hourly_rate, t.experience
    ");
    $tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch unique subjects from tutors
    $stmt_subjects = $conn->query("SELECT DISTINCT specialty FROM tutors WHERE specialty IS NOT NULL AND specialty != ''");
    $subjects = $stmt_subjects->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $tutors = [];
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
      background-color: var(--light);
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
    
    /* Hero Banner */
    .hero-banner {
      background: linear-gradient(135deg, #e9f1ff 0%, #f0e6ff 100%);
      padding: 3rem 2rem;
      border-radius: 20px;
      margin-bottom: 2.5rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.05);
      text-align: center;
      border: none;
    }
    
    .hero-banner p {
      font-size: 2.2rem;
      font-weight: 700;
      margin: 0;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    /* Content Container */
    .content-container {
      background: #ffffff;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.05);
      overflow: hidden;
      margin-bottom: 3rem;
      border: none;
    }
    
    /* Filter Sidebar */
    .filter-sidebar {
      padding: 1.5rem;
      background: #f8f9fa;
      border-right: 1px solid #e9ecef;
    }
    
    .filter-heading {
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid var(--gray-light);
      color: var(--dark);
    }
    
    .filter-btn {
      width: 100%;
      text-align: left;
      padding: 0.8rem 1rem;
      margin-bottom: 0.5rem;
      border-radius: 10px;
      transition: all 0.3s ease;
      font-weight: 600;
      border: 1px solid #e9ecef;
      color: var(--dark);
    }
    
    .filter-btn:hover {
      background-color: #e9f1ff;
      color: var(--primary);
      border-color: var(--primary);
      transform: translateX(3px);
    }
    
    .filter-btn.active {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
    }
    
    /* Tutor Table */
    .tutor-content {
      padding: 2rem;
    }
    
    .search-bar {
      border-radius: 50px;
      padding: 0.75rem 1.5rem;
      border: 1px solid #ced4da;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      font-size: 1rem;
      transition: all 0.3s ease;
      margin-bottom: 1.5rem;
    }
    
    .search-bar:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
    }
    
    .tutor-table {
      border-radius: 10px;
      overflow: hidden;
      border-collapse: separate;
      border-spacing: 0;
      width: 100%;
    }
    
    .tutor-table thead th {
      background-color: #f8f9fa;
      color: var(--dark);
      font-weight: 600;
      padding: 1rem;
      border-bottom: 2px solid #e9ecef;
    }
    
    .tutor-table tbody tr {
      transition: all 0.2s ease;
    }
    
    .tutor-table tbody tr:hover {
      background-color: #f8f9fa;
      transform: scale(1.005);
    }
    
    .tutor-table td {
      padding: 1rem;
      vertical-align: middle;
      border-bottom: 1px solid #e9ecef;
    }    .tutor-name {
      font-weight: 600;
      color: var(--dark);
    }
    
    .experience-badge {
      display: inline-flex;
      align-items: center;
      background-color: #e6f7ff;
      color: #0081cf;
      padding: 0.35rem 0.75rem;
      border-radius: 50px;
      font-size: 0.85rem;
      font-weight: 600;
      white-space: nowrap;
    }
    
    .subject-badge {
      display: inline-block;
      background-color: #e9f1ff;
      color: var(--primary);
      padding: 0.35rem 0.75rem;
      border-radius: 50px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    
    .rating-stars {
      color: #f1c40f;
      letter-spacing: 2px;
    }
    
    .rate-badge {
      color: var(--success);
      font-weight: 600;
    }
    
    .book-btn {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border-radius: 50px;
      padding: 0.5rem 1.2rem;
      font-weight: 600;
      border: none;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
    }
    
    .book-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
      color: white;
    }
    
    .no-results {
      text-align: center;
      padding: 2rem;
      color: #6c757d;
    }
    
    .no-results i {
      font-size: 3rem;
      margin-bottom: 1rem;
      color: #e9ecef;
    }
    
    /* Responsive Design */
    @media (max-width: 992px) {
      .filter-sidebar {
        border-right: none;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 1rem;
      }
      
      .filter-btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
      }
      
      .filter-btn {
        width: auto;
        flex: 1 0 auto;
      }
      
      .hero-banner p {
        font-size: 1.8rem;
      }
    }
    
    @media (max-width: 768px) {
      .navbar {
        padding: 0.75rem 1rem;
      }
      
      .hero-banner {
        padding: 2rem 1rem;
      }
      
      .hero-banner p {
        font-size: 1.5rem;
      }
      
      .tutor-content {
        padding: 1.5rem;
      }
      
      .tutor-table {
        font-size: 0.9rem;
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
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            Find a Tutor
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="find-fl.php">All Subjects</a>
                            <?php if (!empty($subjects)): ?>
                                <div class="dropdown-divider"></div>
                                <?php foreach ($subjects as $subject): ?>
                                    <a class="dropdown-item" href="find-fl.php?subject=<?php echo htmlspecialchars(urlencode($subject)); ?>"><?php echo htmlspecialchars($subject); ?></a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="SignUpTutor.php">Become a Tutor</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link login-btn" href="login.php">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Hero Banner -->
        <div class="hero-banner">
            <p>Unlock Your Potential. Online.</p>
        </div>

        <!-- Main Content -->
        <div class="content-container">
            <div class="row g-0">
                <!-- Filter Sidebar -->
                <div class="col-lg-3 filter-sidebar">
                    <h5 class="filter-heading">
                        <i class="fas fa-filter me-2"></i>Filter by Subject
                    </h5>
                    <div class="filter-btn-group">
                        <button class="filter-btn active" type="button" data-subject="all">
                            <i class="fas fa-th-list me-2"></i>All Subjects
                        </button>
                        <?php if (!empty($subjects)): ?>
                            <?php foreach ($subjects as $subject): ?>
                                <button class="filter-btn" type="button" data-subject="<?php echo htmlspecialchars($subject); ?>">
                                    <i class="fas fa-graduation-cap me-2"></i><?php echo htmlspecialchars($subject); ?>
                                </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tutors Content -->
                <div class="col-lg-9 tutor-content">
                    <div class="input-group mb-4">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control search-bar border-start-0" id="searchInput" placeholder="Search tutors by name or subject...">
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>                    <div class="table-responsive">
                        <table class="table tutor-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Subject</th>
                                    <th>Experience</th>
                                    <th>Rating</th>
                                    <th>Rate</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="tutorTableBody">
                                <?php if (!empty($tutors)): ?>
                                    <?php foreach ($tutors as $tutor): ?>                                        <tr data-subject="<?php echo htmlspecialchars($tutor['specialty']); ?>">
                                            <td class="tutor-name"><?php echo htmlspecialchars($tutor['name']); ?></td>
                                            <td><span class="subject-badge"><?php echo htmlspecialchars($tutor['specialty']); ?></span></td>
                                            <td>
                                                <?php if (isset($tutor['experience']) && $tutor['experience'] > 0): ?>
                                                    <span class="experience-badge">
                                                        <i class="fas fa-briefcase me-1"></i> 
                                                        <?php echo intval($tutor['experience']); ?> <?php echo intval($tutor['experience']) === 1 ? 'year' : 'years'; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="rating-stars">
                                                    <?php
                                                        $rating = isset($tutor['avg_rating']) ? round($tutor['avg_rating']) : 0;
                                                        echo str_repeat('<i class="fas fa-star"></i>', $rating) . str_repeat('<i class="far fa-star"></i>', 5 - $rating);
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="rate-badge">
                                                <?php
                                                    echo isset($tutor['hourly_rate']) ? '$' . htmlspecialchars(number_format($tutor['hourly_rate'], 2)) . '/hour' : 'N/A';
                                                ?>
                                            </td>
                                            <td>
                                                <a href="login.php" class="btn book-btn">
                                                    <i class="fas fa-calendar-check me-1"></i> Book
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="noResultsMessage" class="no-results" style="<?php echo empty($tutors) && !isset($error) ? 'display: block;' : 'display: none;'; ?>">
                        <i class="fas fa-user-graduate"></i>
                        <h5>No tutors found</h5>
                        <p><?php if (empty($tutors) && !isset($error)) echo "No tutors available at the moment."; ?></p>
                    </div>

                    <div id="jsNoResultsMessage" class="no-results" style="display: none;">
                        <i class="fas fa-search"></i>
                        <h5>No matching results</h5>
                        <p>No tutors found matching your criteria. Try different keywords or filters.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
      $(document).ready(function() {
        let currentSubjectFilter = 'all';
        let currentSearchTerm = '';

        function filterTutors() {
          let visibleRows = 0;          $('#tutorTableBody tr').each(function() {
            const row = $(this);
            const tutorSubject = row.data('subject') || '';
            const tutorName = row.find('td:first-child').text().toLowerCase();
            const subjectText = row.find('td:nth-child(2)').text().toLowerCase();

            const subjectMatches = (currentSubjectFilter === 'all' || tutorSubject === currentSubjectFilter);
            const searchMatches = (
                currentSearchTerm === '' ||
                tutorName.includes(currentSearchTerm) ||
                subjectText.includes(currentSearchTerm)
            );

            if (subjectMatches && searchMatches) {
              row.show();
              visibleRows++;
            } else {
              row.hide();
            }
          });

          // Show or hide the "No results" message
          if (visibleRows === 0) {
            $('#noResultsMessage').hide();
            $('#jsNoResultsMessage').show();
          } else {
            $('#noResultsMessage').hide();
            $('#jsNoResultsMessage').hide();
          }
        }

        // Filter tutors when a subject button is clicked
        $('.filter-btn').on('click', function() {
          const subject = $(this).data('subject');

          // Update active button
          $('.filter-btn').removeClass('active');
          $(this).addClass('active');

          currentSubjectFilter = subject;
          filterTutors();
        });

        // Search tutors when the search input changes
        $('#searchInput').on('input', function() {
          currentSearchTerm = $(this).val().toLowerCase();
          filterTutors();
        });

        // Check if there's a subject parameter in the URL
        const urlParams = new URLSearchParams(window.location.search);
        const subjectParam = urlParams.get('subject');
        
        if (subjectParam) {
          // Find the button with the matching subject and click it
          const subjectButton = $(`.filter-btn[data-subject="${subjectParam}"]`);
          if (subjectButton.length) {
            subjectButton.click();
          }
        } else {
          // Initial filter to show all tutors
          filterTutors();
        }
      });
    </script>
</body>
</html>
