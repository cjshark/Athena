<?php
require 'db.php';
try {
    $stmt = $conn->query('DESCRIBE students');
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
} catch(PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
