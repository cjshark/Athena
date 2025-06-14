<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Check if tutor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$tutor_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'update_profile') {
        // --- Update Profile Details ---
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $hourly_rate = isset($_POST['hourly_rate']) ? trim($_POST['hourly_rate']) : null;
        $experience = isset($_POST['experience']) ? trim($_POST['experience']) : null;
        $bio = isset($_POST['bio']) ? trim($_POST['bio']) : null;
        $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : null;
        
        // Handle specialty field (might be a string or an array depending on form structure)
        $specialty = '';
        if (isset($_POST['specialties']) && is_array($_POST['specialties'])) {
            // Handle array input
            $specialties = array_map('trim', $_POST['specialties']);
            $specialties = array_filter($specialties, function($value) { return $value !== ''; });
            $specialty = implode(', ', $specialties);
        } elseif (isset($_POST['specialty'])) {
            // Handle string input
            $specialty = trim($_POST['specialty']);
        }

        // Basic Validation
        if (empty($name) || empty($email) || empty($specialty)) {
            throw new Exception('Name, Email, and at least one Specialty are required.');
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
        // Note: For students check, we assume student IDs are different from tutor IDs.
        // If IDs could overlap, a more complex check might be needed.
        // We also need to ensure we are not comparing the tutor's *own* email.
        $stmt_check_email->execute([':email' => $email, ':user_id' => $tutor_id]);
        if ($stmt_check_email->fetch()) {
             throw new Exception('Email address is already in use by another account.');
        }

        // Prepare update statement
        $stmt = $conn->prepare("UPDATE tutors SET name = ?, email = ?, specialty = ?, description = ?, hourly_rate = ?, experience = ?, bio = ?, phone_number = ? WHERE id = ?");
        $success = $stmt->execute([$name, $email, $specialty, $description, $hourly_rate, $experience, $bio, $phone_number, $tutor_id]);

        if ($success) {
            // Update session name if it changed
            $_SESSION['user_name'] = $name;
            echo json_encode(['success' => true, 'newName' => htmlspecialchars($name)]);
        } else {
            throw new Exception('Failed to update profile in database.');
        }

    } elseif ($action === 'update_picture') {
        // --- Update Profile Picture ---
        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] != 0) {
            throw new Exception('No image uploaded or upload error occurred.');
        }
        
        $upload_dir = 'uploads/profile_images/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            throw new Exception('Only JPG, PNG and GIF images are allowed');
        }
        
        if ($_FILES['profile_image']['size'] > 5000000) { // 5MB limit
            throw new Exception('Image is too large (max 5MB)');
        }
        
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'tutor_' . $tutor_id . '_' . uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            // Update database with new profile image path
            $stmt = $conn->prepare("UPDATE tutors SET profile_image = ? WHERE id = ?");
            $success = $stmt->execute([$target_file, $tutor_id]);
            
            if ($success) {
                echo json_encode(['success' => true, 'image' => $target_file]);
            } else {
                // If database update fails, try to delete the uploaded file
                @unlink($target_file);
                throw new Exception('Failed to update profile image in database.');
            }
        } else {
            throw new Exception('Failed to upload image. Please try again.');
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
        $stmt_pass = $conn->prepare("SELECT password FROM tutors WHERE id = ?");
        $stmt_pass->execute([$tutor_id]);
        $tutor_data = $stmt_pass->fetch();

        if (!$tutor_data || !password_verify($current_password, $tutor_data['password'])) {
            throw new Exception('Incorrect current password.');
        }

        // Hash the new password
        $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password
        $stmt_update_pass = $conn->prepare("UPDATE tutors SET password = ? WHERE id = ?");
        $success = $stmt_update_pass->execute([$new_password_hashed, $tutor_id]);

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