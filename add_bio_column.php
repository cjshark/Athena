<?php
// This script adds the bio column to the tutors table

require 'db.php'; // Include database connection

try {
    // Check if the bio column already exists
    $check_sql = "SHOW COLUMNS FROM tutors LIKE 'bio'";
    $stmt = $conn->prepare($check_sql);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "The 'bio' column already exists in the tutors table.";
    } else {
        // Add the bio column to the tutors table
        $alter_sql = "ALTER TABLE tutors ADD COLUMN bio TEXT DEFAULT NULL COMMENT 'Tutor biography/profile description'";
        $conn->exec($alter_sql);
        echo "Success! The 'bio' column has been added to the tutors table.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>