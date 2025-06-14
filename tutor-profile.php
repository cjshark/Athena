<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: login.php");
    exit();
}
require 'db.php';

$tutor_id = $_SESSION['user_id'];
$update_success = false;
$update_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $specialty = $_POST['specialty'] ?? '';
    $description = $_POST['description'] ?? '';
    $hourly_rate = $_POST['hourly_rate'] ?? null;
    $experience = $_POST['experience'] ?? 0;
    $bio = $_POST['bio'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';

    if (empty($name) || empty($email) || empty($specialty)) {
        $update_error = "Name, Email, and Specialty are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update_error = "Invalid email format.";
    } elseif ($hourly_rate !== null && (!is_numeric($hourly_rate) || $hourly_rate < 0)) {
        $update_error = "Hourly rate must be a non-negative number.";
    } else {
        try {
            $stmt_check = $conn->prepare("SELECT id FROM tutors WHERE email = ? AND id != ? UNION SELECT id FROM students WHERE email = ?");
            $stmt_check->execute([$email, $tutor_id, $email]);
            if ($stmt_check->fetch()) {
                $update_error = "Email address is already in use.";
            } else {
                // Handle profile image upload if a file was submitted
                $profile_image = null;
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $upload_dir = 'uploads/profile_images/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                        throw new Exception("Only JPG, PNG and GIF images are allowed");
                    }
                    
                    if ($_FILES['profile_image']['size'] > 5000000) { // 5MB limit
                        throw new Exception("Image is too large (max 5MB)");
                    }
                    
                    $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'tutor_' . $tutor_id . '_' . uniqid() . '.' . $file_extension;
                    $target_file = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                        $profile_image = $target_file;
                    } else {
                        throw new Exception("Failed to upload image. Please try again.");
                    }
                }
                  // Prepare the SQL statement based on whether we have a new profile image
                if ($profile_image) {
                    $stmt_update = $conn->prepare("UPDATE tutors SET name = ?, email = ?, specialty = ?, description = ?, hourly_rate = ?, profile_image = ?, experience = ?, bio = ?, phone_number = ? WHERE id = ?");
                    $rate_to_save = ($hourly_rate === '' || $hourly_rate === null) ? null : number_format((float)$hourly_rate, 2, '.', '');
                    $result = $stmt_update->execute([$name, $email, $specialty, $description, $rate_to_save, $profile_image, $experience, $bio, $phone_number, $tutor_id]);
                } else {
                    $stmt_update = $conn->prepare("UPDATE tutors SET name = ?, email = ?, specialty = ?, description = ?, hourly_rate = ?, experience = ?, bio = ?, phone_number = ? WHERE id = ?");
                    $rate_to_save = ($hourly_rate === '' || $hourly_rate === null) ? null : number_format((float)$hourly_rate, 2, '.', '');
                    $result = $stmt_update->execute([$name, $email, $specialty, $description, $rate_to_save, $experience, $bio, $phone_number, $tutor_id]);
                }
                
                if ($result) {
                    $update_success = true;
                    $_SESSION['name'] = $name;
                } else {
                    $update_error = "Failed to update profile. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $update_error = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $update_error = $e->getMessage();
        }
    }
}

$stmt = $conn->prepare("SELECT name, email, specialty, description, hourly_rate, profile_image, experience, bio, phone_number FROM tutors WHERE id = ?");
$stmt->execute([$tutor_id]);
$tutor = $stmt->fetch();

if (!$tutor) {
    echo "Error: Tutor data not found.";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Athena</title>
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
        
        /* Profile Styles */
        .profile-container {
            max-width: 950px;
            margin: 0 auto;
            padding: 2rem 0;
        }
        
        .profile-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .profile-card .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem;
            border-bottom: none;
            position: relative;
        }
        
        .profile-card .card-header h4 {
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .profile-card .card-header h4 i {
            margin-right: 0.75rem;
            font-size: 1.5rem;
        }
        
        .profile-card .card-body {
            padding: 2rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 12px;
            padding: 0.75rem 1.25rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 3px 10px rgba(67, 97, 238, 0.1);
        }
        
        .form-select {
            border-radius: 12px;
            padding: 0.75rem 1.25rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }
        
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 3px 10px rgba(67, 97, 238, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.2);
        }
        
        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }
        
        .password-section {
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .password-section h5 {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .password-section h5 i {
            color: var(--primary);
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .profile-pic-section {
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .profile-pic-section h5 {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .profile-pic-section h5 i {
            color: var(--primary);
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .current-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        
        .file-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        
        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-upload-button {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 50px;
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
        }
        
        .file-upload:hover .file-upload-button {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
        }
        
        #alert-container {
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
        }
        
        .alert {
            border: none;
            border-radius: 15px;
            padding: 1rem 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .alert-success {
            background-color: rgba(56, 176, 0, 0.15);
            color: var(--success);
        }
        
        .alert-danger {
            background-color: rgba(247, 37, 133, 0.15);
            color: var(--accent);
        }
        
        .alert-warning {
            background-color: rgba(255, 193, 7, 0.15);
            color: #d6a100;
        }
        
        .input-group-text {
            border-radius: 12px 0 0 12px;
            border: 1px solid #e9ecef;
            background-color: #f8f9fa;
        }
        
        .input-group .form-control {
            border-radius: 0 12px 12px 0;
        }
        
        @media (max-width: 768px) {
            .profile-card .card-header {
                padding: 1.25rem;
            }
            
            .profile-card .card-body {
                padding: 1.5rem;
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
                        <a class="nav-link" href="tutor-schedule.php">My Sessions</a>
                    </li>
                </ul>                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php if (!empty($tutor['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($tutor['profile_image']); ?>" alt="Profile" class="rounded-circle me-1" width="24" height="24" style="object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle me-1"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($tutor['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="tutor-profile.php">
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

    <div id="alert-container">
        <?php if ($update_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> Your profile has been updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($update_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($update_error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="profile-container">
        <div class="card profile-card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-user-edit"></i> Edit Your Profile</h4>
            </div>
            <div class="card-body">
                <form id="profile-form" method="POST" action="tutor-profile.php" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-user text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="name" name="name" value="<?php echo htmlspecialchars($tutor['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-envelope text-primary"></i>
                                </span>
                                <input type="email" class="form-control border-start-0" id="email" name="email" value="<?php echo htmlspecialchars($tutor['email']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone_number" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-phone text-primary"></i>
                                </span>
                                <input type="tel" class="form-control border-start-0" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($tutor['phone_number'] ?? ''); ?>" placeholder="Your contact number">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="specialty" class="form-label">Subject Specialty</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-graduation-cap text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="specialty" name="specialty" value="<?php echo htmlspecialchars($tutor['specialty']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="hourly_rate" class="form-label">Hourly Rate (USD)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-dollar-sign text-primary"></i>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control border-start-0" id="hourly_rate" name="hourly_rate" value="<?php echo htmlspecialchars($tutor['hourly_rate'] ?? '0.00'); ?>" required>
                            </div>
                            <small class="text-muted">Set your hourly teaching rate in USD</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="experience" class="form-label">Years of Experience</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-briefcase text-primary"></i>
                                </span>
                                <input type="number" min="0" class="form-control border-start-0" id="experience" name="experience" value="<?php echo htmlspecialchars($tutor['experience'] ?? '0'); ?>" required>
                            </div>
                        </div>
                    </div>
                      <div class="mb-3">
                        <label for="bio" class="form-label">Biography</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0">
                                <i class="fas fa-info-circle text-primary"></i>
                            </span>
                            <textarea class="form-control border-start-0" id="bio" name="bio" rows="4" placeholder="Tell students about yourself, your experience, and teaching style..."><?php echo htmlspecialchars($tutor['bio'] ?? ''); ?></textarea>
                        </div>
                        <small class="text-muted">This will be visible to students on your profile page</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Detailed Description</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0">
                                <i class="fas fa-file-alt text-primary"></i>
                            </span>
                            <textarea class="form-control border-start-0" id="description" name="description" rows="6" placeholder="Provide more details about your teaching methods, education background, and expertise..."><?php echo htmlspecialchars($tutor['description'] ?? ''); ?></textarea>
                        </div>
                        <small class="text-muted">This detailed description helps students understand your teaching approach and expertise</small>
                    </div>
                    
                    <div class="text-end mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Profile Changes
                        </button>
                    </div>
                </form>

                <!-- Profile Picture Section -->
                <div class="profile-pic-section">
                    <h5><i class="fas fa-camera"></i> Profile Picture</h5>
                    <div class="row">
                        <div class="col-md-4 text-center text-md-start">
                            <?php if (isset($tutor['profile_image']) && !empty($tutor['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($tutor['profile_image']); ?>" alt="Profile Picture" class="current-photo">
                            <?php else: ?>
                                <img src="person-circle.svg" alt="Default Profile" class="current-photo">
                            <?php endif; ?>
                        </div>                        <div class="col-md-8 mt-3 mt-md-0">
                            <p class="mb-3">Upload a professional photo to make your profile more appealing to students.</p>
                            <form id="picture-form" method="POST" action="tutor-profile.php" enctype="multipart/form-data">
                                <div class="file-upload mb-3">
                                    <label class="file-upload-button">
                                        <i class="fas fa-upload me-2"></i> Choose Photo
                                        <input type="file" name="profile_image" class="file-upload-input" accept="image/*">
                                    </label>
                                    <span id="file-name" class="ms-2 text-muted">No file selected</span>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-cloud-upload-alt me-2"></i> Upload Photo
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Password Section -->                <div class="password-section">
                    <h5><i class="fas fa-lock"></i> Change Password</h5>
                    <form id="password-form" method="POST" action="tutor-profile.php">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-key text-primary"></i>
                                    </span>
                                    <input type="password" class="form-control border-start-0" id="current_password" name="current_password" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-lock text-primary"></i>
                                    </span>
                                    <input type="password" class="form-control border-start-0" id="new_password" name="new_password" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-lock text-primary"></i>
                                    </span>
                                    <input type="password" class="form-control border-start-0" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-key me-2"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            function showAlert(message, type = 'success') {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>`;
                $('#alert-container').html(alertHtml);
                
                // Auto dismiss after 5 seconds
                setTimeout(() => {
                    $('#alert-container .alert').alert('close');
                }, 5000);
            }            // Profile Form Submit
            $('#profile-form').submit(function(e) {
                // Allow regular form submission to process on the server side
                // We've updated the PHP code to handle the form submission directly
            });

            // Password Form Submit
            $('#password-form').submit(function(e) {
                e.preventDefault();
                const formData = $(this).serialize();

                const newPassword = $('#new_password').val();
                const confirmPassword = $('#confirm_password').val();
                if (newPassword !== confirmPassword) {
                    showAlert('<i class="fas fa-exclamation-triangle me-2"></i> New passwords do not match.', 'warning');
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: 'update-tutor-profile.php',
                    data: formData + '&action=change_password',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert('<i class="fas fa-check-circle me-2"></i> Password changed successfully!', 'success');
                            $('#password-form')[0].reset();
                        } else {
                            showAlert('<i class="fas fa-exclamation-circle me-2"></i> Error: ' + response.error, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('<i class="fas fa-times-circle me-2"></i> An unexpected error occurred while changing the password.', 'danger');
                    }
                });
            });            // Profile Picture Upload
            $('input[name="profile_image"]').change(function() {
                const fileName = $(this).val().split('\\').pop();
                $('#file-name').text(fileName || 'No file selected');
            });

            $('#picture-form').submit(function(e) {
                // Allow regular form submission to process on the server side
                // We've updated the PHP code to handle the form submission directly
            });
        });
    </script>
</body>
</html>