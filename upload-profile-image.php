<?php
// filepath: c:\xampp\htdocs\project\upload-profile-image.php
session_start();
require 'db.php';

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$redirect_url = "student-profile.php";
$message = '';

try {
    // Debug: Log upload attempt
    error_log("Profile image upload attempt for student ID: $student_id");
    error_log("FILES array: " . print_r($_FILES, true));
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $upload_dir = 'uploads/profile_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            throw new Exception('Only JPG, JPEG, PNG & GIF files are allowed.');
        }

        // Validate file size
        if ($_FILES['profile_image']['size'] > 5000000) { // 5MB limit
            throw new Exception('File size must be less than 5MB.');
        }

        // Generate a unique filename
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $target_file = $upload_dir . 'student_' . $student_id . '_' . uniqid() . '.' . $file_extension;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            // Update database with new profile image path
            $stmt = $conn->prepare("UPDATE students SET profile_image = ? WHERE id = ?");
            
            if ($stmt->execute([$target_file, $student_id])) {
                $message = "Profile picture updated successfully!";
                // Set a session flash message to display after redirect
                $_SESSION['flash_message'] = $message;
                $_SESSION['flash_type'] = 'success';
            } else {
                throw new Exception('Database update failed.');
            }
        } else {
            $error_info = error_get_last();
            throw new Exception('Failed to upload file. ' . ($error_info ? $error_info['message'] : ''));
        }
    } else if (isset($_FILES['profile_image'])) {
        // Show error if there was an upload issue
        $error_codes = [
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk',
            8 => 'A PHP extension stopped the file upload'
        ];
        
        $error_code = $_FILES['profile_image']['error'];
        throw new Exception('Upload error: ' . ($error_codes[$error_code] ?? "Unknown error ($error_code)"));
    } else {
        throw new Exception('No file was uploaded.');
    }
} catch (Exception $e) {
    error_log("Profile image upload error: " . $e->getMessage());
    $_SESSION['flash_message'] = "Error: " . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

// Redirect back to student profile
header("Location: $redirect_url");
exit();
?>
