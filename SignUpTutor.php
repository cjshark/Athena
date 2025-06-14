<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (
            empty($_POST['name']) || 
            empty($_POST['email']) || 
            empty($_POST['password']) || 
            empty($_POST['confirm_password']) ||
            empty($_POST['account_type'])
        ) {
            throw new Exception("All fields are required");
        }

        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match");
        }

        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $accountType = $_POST['account_type'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $table = ($accountType === 'student') ? 'students' : 'tutors';

        error_log("Inserting into table: " . $table);

        $check = $conn->prepare("SELECT id FROM $table WHERE email = :email");
        $check->bindParam(':email', $email);
        $check->execute();
        if ($check->fetch()) {
            throw new Exception("Email already registered");
        }

        $stmt = $conn->prepare("INSERT INTO $table (name, email, phone, password, status) VALUES (:name, :email, :phone, :password, 'active')");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':password', $password);

        if ($stmt->execute()) {
            session_start();
            $_SESSION['user_id'] = $conn->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['role'] = $accountType;

            if ($accountType === 'student') {
                header("Location: student-dashboard.php");
            } else {
                header("Location: tutor-dashboard.php");
            }
            exit();
        } else {
            throw new Exception("Registration failed");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Athena</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --accent: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-light: #e9ecef;
            --success: #38b000;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark);
            line-height: 1.6;
            background-color: var(--light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 0;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(114, 9, 183, 0.05));
        }
        
        .form-signup {
            max-width: 550px;
            width: 100%;
            padding: 15px;
        }
        
        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .form-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(67, 97, 238, 0.05);
            z-index: 0;
        }
        
        .form-card::after {
            content: '';
            position: absolute;
            bottom: -70px;
            left: -70px;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(114, 9, 183, 0.05);
            z-index: 0;
        }
        
        .form-content {
            position: relative;
            z-index: 1;
        }
        
        .form-title {
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 25px;
            text-align: center;
            font-size: 1.8rem;
        }
        
        .form-subtitle {
            text-align: center;
            margin-bottom: 25px;
            color: #6c757d;
        }
        
        .form-floating {
            margin-bottom: 15px;
        }
        
        .form-floating input, .form-floating select {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            height: 55px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-floating input:focus, .form-floating select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .btn {
            border-radius: 50px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
        }
        
        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
            transform: translateY(-2px);
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary));
        }
        
        .btn-secondary {
            background-color: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-secondary:hover {
            background-color: var(--primary);
            color: white;
            border: 2px solid var(--primary);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .alert-danger {
            background-color: #ffe5e5;
            border-color: #ffcccc;
            color: #cc0000;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 25px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .copyright {
            font-size: 0.85rem;
            color: #adb5bd;
            text-align: center;
            margin-top: 25px;
        }
        
        .account-type-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .account-type-option {
            flex: 1;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .account-type-option:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .account-type-option.selected {
            border-color: var(--primary);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .account-type-option i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .account-type-option h5 {
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .account-type-option p {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0;
        }
        
        @media (max-width: 576px) {
            .form-card {
                padding: 30px 20px;
            }
            
            .form-title {
                font-size: 1.5rem;
            }
            
            .account-type-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main class="form-signup">
        <div class="form-card">
            <div class="form-content">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <h1 class="form-title">Create Your Account</h1>
                <p class="form-subtitle">Join Athena's learning community and start your journey</p>

                <form method="POST" action="SignUpTutor.php">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="floatingFullName" name="name" placeholder="Full Name" required>
                        <label for="floatingFullName">Full Name</label>
                    </div>

                    <div class="form-floating">
                        <input type="email" class="form-control" id="floatingEmail" name="email" placeholder="Email" required>
                        <label for="floatingEmail">Email</label>
                    </div>

                    <div class="form-floating">
                        <input type="tel" class="form-control" id="floatingPhoneNumber" name="phone" placeholder="Phone Number">
                        <label for="floatingPhoneNumber">Phone Number</label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required>
                        <label for="floatingPassword">Password</label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="floatingConfirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                        <label for="floatingConfirmPassword">Confirm Password</label>
                    </div>
                    
                    <h6 class="mt-4 mb-3 text-center">I want to register as:</h6>
                    <div class="account-type-container">
                        <div class="account-type-option" id="studentOption" onclick="selectAccountType('student')">
                            <i class="fas fa-user-graduate"></i>
                            <h5>Student</h5>
                            <p>Find tutors and book sessions</p>
                        </div>
                        <div class="account-type-option" id="tutorOption" onclick="selectAccountType('tutor')">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <h5>Tutor</h5>
                            <p>Offer tutoring services</p>
                        </div>
                    </div>
                    <input type="hidden" id="accountTypeInput" name="account_type" value="" required>

                    <div class="d-grid gap-2 mt-4">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-user-plus me-2"></i> Create Account
                        </button>
                        <a href="login.php" class="btn btn-secondary">
                            <i class="fas fa-sign-in-alt me-2"></i> Already have an account?
                        </a>
                    </div>
                </form>
                
                <p class="copyright">&copy; 2024 Athena Learning Platform</p>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectAccountType(type) {
            document.getElementById('accountTypeInput').value = type;
            
            // Remove selected class from both options
            document.getElementById('studentOption').classList.remove('selected');
            document.getElementById('tutorOption').classList.remove('selected');
            
            // Add selected class to chosen option
            if (type === 'student') {
                document.getElementById('studentOption').classList.add('selected');
            } else {
                document.getElementById('tutorOption').classList.add('selected');
            }
        }
    </script>
</body>
</html>
