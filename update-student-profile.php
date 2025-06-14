<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'update_profile') {
        // --- Update Profile Details ---
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone_number = trim($_POST['phone_number'] ?? ''); // Get phone number
        
        // Basic Validation
        if (empty($name) || empty($email)) {
            throw new Exception('Name and Email cannot be empty.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format.');
        }

        // Check if email is already taken by another user (student or tutor)
        $stmt_check_email = $conn->prepare("
            SELECT id FROM students WHERE email = :email AND id != :user_id 
            UNION 
            SELECT id FROM tutors WHERE email = :email AND id != :user_id 
        ");
         // Note: We need to ensure we are not comparing the student's *own* email.
        $stmt_check_email->execute([':email' => $email, ':user_id' => $student_id]);
        if ($stmt_check_email->fetch()) {
             throw new Exception('Email address is already in use by another account.');
        }        // Handle profile picture upload if it exists
        $profile_image = null;
        
        // Debug: Log FILES data
        error_log("FILES array in update-student-profile: " . print_r($_FILES, true));
        
        if (isset($_FILES['profile_image'])) {
            // Debug: Log the specific file data
            error_log("profile_image details: " . print_r($_FILES['profile_image'], true));
            
            if ($_FILES['profile_image']['error'] == 0) {
                $upload_dir = 'uploads/profile_images/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        error_log("Failed to create directory: " . $upload_dir);
                        throw new Exception('Failed to create upload directory.');
                    }
                }
                
                // Make sure directory is writable
                if (!is_writable($upload_dir)) {
                    error_log("Upload directory is not writable: " . $upload_dir);
                    chmod($upload_dir, 0777);
                    if (!is_writable($upload_dir)) {
                        throw new Exception('Upload directory is not writable.');
                    }
                }

                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                    throw new Exception('Only JPG, JPEG, PNG & GIF files are allowed. Received: ' . $_FILES['profile_image']['type']);
                }

                // Validate file size
                if ($_FILES['profile_image']['size'] > 5000000) { // 5MB limit
                    throw new Exception('File size must be less than 5MB.');
                }

                $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $target_file = $upload_dir . 'student_' . $student_id . '_' . uniqid() . '.' . $file_extension;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $profile_image = $target_file;
                    error_log("File successfully uploaded to: " . $target_file);
                } else {
                    $upload_error = error_get_last();
                    error_log("Failed to move uploaded file. Error: " . ($upload_error ? $upload_error['message'] : "Unknown error"));
                    throw new Exception('Failed to upload profile image. Please check file permissions.');
                }
            } else {
                // Map error code to message
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
                $error_message = $error_codes[$error_code] ?? "Unknown error ($error_code)";
                
                // Only throw exception if error is not 4 (no file uploaded)
                if ($error_code != 4) {
                    throw new Exception('Upload failed: ' . $error_message);
                }
            }
        }

        // Prepare update statement including profile_image if it exists
        if ($profile_image) {
            $stmt = $conn->prepare("UPDATE students SET name = ?, email = ?, phone_number = ?, profile_image = ? WHERE id = ?");
            $success = $stmt->execute([$name, $email, $phone_number, $profile_image, $student_id]);
        } else {
            // Prepare update statement without profile_image
            $stmt = $conn->prepare("UPDATE students SET name = ?, email = ?, phone_number = ? WHERE id = ?");
            $success = $stmt->execute([$name, $email, $phone_number, $student_id]);
        }

        if ($success) {
            // Update session name if it changed
            $_SESSION['user_name'] = $name; // Assuming you store name in session
            echo json_encode(['success' => true, 'newName' => htmlspecialchars($name)]);
        } else {
            throw new Exception('Failed to update profile in database.');
        }

    } elseif ($action === 'change_password') {
        // --- Change Password ---
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Basic Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception('All password fields are required.');
        }
        if ($new_password !== $confirm_password) {
            throw new Exception('New passwords do not match.');
        }
         if (strlen($new_password) < 6) { // Example: Minimum length
             throw new Exception('New password must be at least 6 characters long.');
        }

        // Verify current password
        $stmt_pass = $conn->prepare("SELECT password FROM students WHERE id = ?");
        $stmt_pass->execute([$student_id]);
        $student_data = $stmt_pass->fetch();

        if (!$student_data || !password_verify($current_password, $student_data['password'])) {
            throw new Exception('Incorrect current password.');
        }

        // Hash the new password
        $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password
        $stmt_update_pass = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
        $success = $stmt_update_pass->execute([$new_password_hashed, $student_id]);

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Failed to update password in database.');
        }

    } else {
        throw new Exception('Invalid action specified.');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>