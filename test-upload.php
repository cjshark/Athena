<?php
// filepath: c:\xampp\htdocs\project\test-upload.php
// Simple script to test file upload functionality

// Display server configuration
echo "<h2>Server Upload Configuration</h2>";
echo "<pre>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "</pre>";

// Check uploads directory
echo "<h2>Upload Directory Check</h2>";
$upload_dir = 'uploads/profile_images/';
if (file_exists($upload_dir)) {
    echo "<p style='color:green'>✓ Directory exists: {$upload_dir}</p>";
    if (is_writable($upload_dir)) {
        echo "<p style='color:green'>✓ Directory is writable</p>";
    } else {
        echo "<p style='color:red'>✗ Directory is NOT writable</p>";
    }
} else {
    echo "<p style='color:red'>✗ Directory does not exist: {$upload_dir}</p>";
}

// Process upload if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Upload Test Results</h2>";
    
    // Debug info
    echo "<h3>POST Data</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>FILES Data</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    // Process upload
    if (isset($_FILES['test_image']) && $_FILES['test_image']['error'] == 0) {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (!in_array($_FILES['test_image']['type'], $allowed_types)) {
            echo "<p style='color:red'>✗ Invalid file type. Only JPEG, PNG & GIF allowed.</p>";
        } else {
            // Validate size
            if ($_FILES['test_image']['size'] > 5000000) {
                echo "<p style='color:red'>✗ File too large. Max 5MB allowed.</p>";
            } else {
                $file_extension = pathinfo($_FILES['test_image']['name'], PATHINFO_EXTENSION);
                $target_file = $upload_dir . 'test_' . uniqid() . '.' . $file_extension;
                
                if (move_uploaded_file($_FILES['test_image']['tmp_name'], $target_file)) {
                    echo "<p style='color:green'>✓ File uploaded successfully to: {$target_file}</p>";
                    echo "<p><img src='{$target_file}' style='max-width:300px; max-height:300px;'></p>";
                } else {
                    echo "<p style='color:red'>✗ Upload failed. Error: " . error_get_last()['message'] . "</p>";
                }
            }
        }
    } else if (isset($_FILES['test_image'])) {
        // Show error code meaning
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
        
        $error_code = $_FILES['test_image']['error'];
        echo "<p style='color:red'>✗ Upload error: " . ($error_codes[$error_code] ?? "Unknown error ($error_code)") . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        button { padding: 10px 15px; background: #4361ee; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>File Upload Test Form</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="test_image">Select Image to Upload:</label>
            <input type="file" name="test_image" id="test_image" accept="image/*">
        </div>
        <button type="submit">Upload Test Image</button>
    </form>
</body>
</html>
