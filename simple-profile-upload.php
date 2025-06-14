<?php
// filepath: c:\xampp\htdocs\project\simple-profile-upload.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
require 'db.php';

$student_id = $_SESSION['user_id'];

$message = '';
$error = '';

// Process form if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile picture upload if it exists
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $upload_dir = 'uploads/profile_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $error = 'Only JPG, JPEG, PNG & GIF files are allowed.';
        } else {
            // Validate file size
            if ($_FILES['profile_image']['size'] > 5000000) { // 5MB limit
                $error = 'File size must be less than 5MB.';
            } else {
                $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $target_file = $upload_dir . 'student_' . $student_id . '_' . uniqid() . '.' . $file_extension;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    // Update the database
                    $stmt = $conn->prepare("UPDATE students SET profile_image = ? WHERE id = ?");
                    $success = $stmt->execute([$target_file, $student_id]);
                    
                    if ($success) {
                        $message = 'Profile picture uploaded successfully!';
                    } else {
                        $error = 'Failed to update database.';
                    }
                } else {
                    $error = 'Failed to upload profile image.';
                }
            }
        }
    } else if (isset($_FILES['profile_image'])) {
        $error_codes = [
            0 => 'No error',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk',
            8 => 'A PHP extension stopped the file upload'
        ];
        
        $error_code = $_FILES['profile_image']['error'];
        $error = 'Upload error: ' . ($error_codes[$error_code] ?? "Unknown error ($error_code)");
    }
}

// Get current profile info
$stmt = $conn->prepare("SELECT name, profile_image FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Profile Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ddd;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Simple Profile Picture Upload</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success mb-4"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger mb-4"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <div class="text-center mb-4">
                            <h4 class="mb-3">Current Profile: <?php echo htmlspecialchars($student['name']); ?></h4>
                            <?php if (isset($student['profile_image']) && !empty($student['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile" class="profile-image mb-3">
                            <?php else: ?>
                                <img src="uploads/profile_images/default-avatar.png" alt="Default Avatar" class="profile-image mb-3">
                            <?php endif; ?>
                        </div>
                        
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Select Profile Picture</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" required>
                                <div class="form-text">JPG, JPEG, PNG or GIF. Max 5MB.</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Upload Profile Picture</button>
                            </div>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <a href="student-profile.php" class="btn btn-outline-secondary">Back to Full Profile</a>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow mt-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Debug Information</h5>
                    </div>
                    <div class="card-body">
                        <h6>Server Configuration</h6>
                        <ul class="small">
                            <li>upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?></li>
                            <li>post_max_size: <?php echo ini_get('post_max_size'); ?></li>
                            <li>max_file_uploads: <?php echo ini_get('max_file_uploads'); ?></li>
                        </ul>
                        
                        <h6>Upload Directory</h6>
                        <ul class="small">
                            <li>Path: uploads/profile_images/</li>
                            <li>Exists: <?php echo file_exists('uploads/profile_images/') ? 'Yes' : 'No'; ?></li>
                            <li>Writable: <?php echo is_writable('uploads/profile_images/') ? 'Yes' : 'No'; ?></li>
                        </ul>
                        
                        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                            <h6>Form Data</h6>
                            <pre class="small"><?php print_r($_FILES); ?></pre>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
