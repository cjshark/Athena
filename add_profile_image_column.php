<?php
// filepath: c:\xampp\htdocs\project\add_profile_image_column.php
// This script adds a profile_image column to the students table if it doesn't exist

require 'db.php';

try {
    // Check if column exists
    $stmt = $conn->prepare("SHOW COLUMNS FROM students LIKE 'profile_image'");
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // Add the profile_image column
        $sql = "ALTER TABLE students ADD COLUMN profile_image VARCHAR(255) NULL";
        $conn->exec($sql);
        echo "Success: profile_image column added to students table.<br>";
    } else {
        echo "profile_image column already exists in students table.<br>";
    }
    
    // Check if default avatar exists
    $default_avatar = 'uploads/profile_images/default-avatar.png';
    if (!file_exists($default_avatar)) {
        echo "Note: Default avatar image doesn't exist. Please create a default avatar at: $default_avatar<br>";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

echo "Done.";
?>
