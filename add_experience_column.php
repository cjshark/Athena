<?php
// This script adds the experience column to the tutors table

require 'db.php'; // Include database connection

try {
    // Check if the experience column already exists
    $check_sql = "SHOW COLUMNS FROM tutors LIKE 'experience'";
    $stmt = $conn->prepare($check_sql);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "The 'experience' column already exists in the tutors table.";
    } else {
        // Add the experience column to the tutors table
        $alter_sql = "ALTER TABLE tutors ADD COLUMN experience INT DEFAULT 0 COMMENT 'Years of teaching experience'";
        $conn->exec($alter_sql);
        echo "Success! The 'experience' column has been added to the tutors table.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>