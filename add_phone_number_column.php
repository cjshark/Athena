<?php
// This script adds the phone_number column to the tutors table

require 'db.php'; // Include database connection

try {
    // Check if the phone_number column already exists
    $check_sql = "SHOW COLUMNS FROM tutors LIKE 'phone_number'";
    $stmt = $conn->prepare($check_sql);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "The 'phone_number' column already exists in the tutors table.";
    } else {
        // Add the phone_number column to the tutors table
        $alter_sql = "ALTER TABLE tutors ADD COLUMN phone_number VARCHAR(20) DEFAULT NULL COMMENT 'Tutors contact phone number'";
        $conn->exec($alter_sql);
        echo "Success! The 'phone_number' column has been added to the tutors table.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>