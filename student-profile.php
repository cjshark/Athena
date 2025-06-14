<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
require 'db.php';

// Get flash messages if they exist
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? 'success';
// Clear flash messages so they don't appear again on refresh
unset($_SESSION['flash_message']);
unset($_SESSION['flash_type']);

$stmt = $conn->prepare("SELECT name, email, phone_number, profile_image FROM students WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    echo "Error: Student data not found.";
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
        
        .nav-profile-img {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 1px solid var(--light);
            object-fit: cover;
            margin-right: 5px;
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
        
        /* Profile Picture Styles */
        .profile-picture-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto;
            border: 5px solid var(--gray-light);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .profile-picture {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: all 0.3s ease;
        }
        
        .profile-picture:hover {
            transform: scale(1.05);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
        }
        
        #file-name-display {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
            margin: 0 auto;
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
            
            .profile-picture-container {
                width: 120px;
                height: 120px;
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
                </ul>                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php if (isset($student['profile_image']) && !empty($student['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="nav-profile-img me-1">
                            <?php else: ?>
                                <i class="fas fa-user-circle me-1"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($student['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="student-profile.php">
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
    </nav>    <div id="alert-container">
        <?php if ($flash_message): ?>
            <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="profile-container">
        <div class="card profile-card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-user-edit"></i> Edit Your Profile</h4>
            </div>            <div class="card-body">                <!-- Fixed version with direct form for profile image -->
                <div class="text-center mb-4">
                    <div class="profile-picture-container">
                        <?php if (isset($student['profile_image']) && !empty($student['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile Picture" class="profile-picture" id="current-profile-image">
                        <?php else: ?>
                            <img src="uploads/profile_images/default-avatar.png" alt="Default Profile" class="profile-picture" id="current-profile-image">
                        <?php endif; ?>
                    </div>
                    <div class="mt-3 mb-4">
                        <form id="image-upload-form" enctype="multipart/form-data" method="post" action="upload-profile-image.php" style="display:inline;">
                            <input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/*" onChange="this.form.submit()">
                            <label for="profile_image" class="btn btn-outline-primary">
                                <i class="fas fa-camera me-2"></i> Change Profile Picture
                            </label>
                        </form>
                        <div id="file-name-display" class="small text-muted mt-2"></div>
                    </div>
                </div>
                
                <form id="profile-form" method="post">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-user text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="name" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-envelope text-primary"></i>
                                </span>
                                <input type="email" class="form-control border-start-0" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0">
                                <i class="fas fa-phone text-primary"></i>
                            </span>
                            <input type="tel" class="form-control border-start-0" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($student['phone_number'] ?? ''); ?>" placeholder="Your contact number">
                        </div>
                        <small class="text-muted">This will be used to contact you about your sessions</small>
                    </div>
                    
                    <div class="text-end mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Profile Changes
                        </button>
                    </div>
                </form>

                <div class="password-section">
                    <h5><i class="fas fa-lock"></i> Change Password</h5>
                    <form id="password-form">
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
            }            // Display selected filename when a file is selected
            $('#profile_image').change(function() {
                const file = this.files[0];
                if (file) {
                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                    if (!allowedTypes.includes(file.type)) {
                        showAlert('<i class="fas fa-exclamation-triangle me-2"></i> Please select a valid image file (JPEG, PNG, or GIF).', 'warning');
                        $(this).val(''); // Clear the file input
                        $('#file-name-display').text('');
                        return false; // Prevent form submission
                    }
                    
                    // Validate file size (5MB max)
                    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                    if (file.size > maxSize) {
                        showAlert('<i class="fas fa-exclamation-triangle me-2"></i> Image file size must be less than 5MB.', 'warning');
                        $(this).val(''); // Clear the file input
                        $('#file-name-display').text('');
                        return false; // Prevent form submission
                    }
                    
                    // Show upload indicator
                    $('#file-name-display').html('<span class="text-primary">Uploading ' + file.name + '... <i class="fas fa-spinner fa-spin"></i></span>');
                    
                    // Preview the image before submitting
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#current-profile-image').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(file);
                    
                    // Let the form submission continue
                    console.log('File validated, submitting form...');
                    return true;
                }
            });
              $('#profile-form').submit(function(e) {
                e.preventDefault();
                
                // Debugging: Check file input
                const fileInput = document.getElementById('profile_image');
                console.log("File input element:", fileInput);
                console.log("Files property:", fileInput.files);
                if (fileInput.files.length > 0) {
                    console.log("Selected file:", fileInput.files[0].name, "Size:", fileInput.files[0].size, "Type:", fileInput.files[0].type);
                }
                
                // Use FormData to handle file uploads
                const formData = new FormData(this);
                formData.append('action', 'update_profile');
                
                // Debugging: Verify FormData content
                console.log("FormData object created");
                formData.forEach((value, key) => {
                    console.log(key + ':', value instanceof File ? `File: ${value.name}` : value);
                });
                  // Debug: Log the form data to console
                console.log("Form data being submitted:", formData);
                console.log("File selected:", $('#profile_image')[0].files[0] ? $('#profile_image')[0].files[0].name : 'No file selected');
                
                $.ajax({
                    type: 'POST',
                    url: 'update-student-profile.php',
                    data: formData,
                    processData: false, // Don't process data (needed for FormData)
                    contentType: false, // Don't set content type (needed for FormData)
                    dataType: 'json',
                    success: function(response) {
                        console.log("Response received:", response); // Debug: Log the response
                        if (response.success) {
                            showAlert('<i class="fas fa-check-circle me-2"></i> Profile updated successfully!', 'success');
                            if (response.newName) {
                                // If there was a profile image change, reload the page to show the new image
                                if ($('#profile_image').val()) {
                                    showAlert('<i class="fas fa-check-circle me-2"></i> Profile picture updated! Reloading page...', 'success');
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 1500);
                                } else {
                                    // Just update the name in the navbar
                                    $('#navbarDropdown').html('<i class="fas fa-user-circle me-1"></i> ' + response.newName);
                                }
                            }
                        } else {
                            showAlert('<i class="fas fa-exclamation-circle me-2"></i> Error: ' + response.error, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error); // Debug
                        console.log("Response text:", xhr.responseText); // Show any error response
                        showAlert('<i class="fas fa-times-circle me-2"></i> An unexpected error occurred while updating the profile.', 'danger');
                    }
                });
            });

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
                    url: 'update-student-profile.php',
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
            });
        });
    </script>
</body>
</html>