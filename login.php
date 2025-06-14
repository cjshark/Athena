<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (empty($_POST['email']) || empty($_POST['password'])) {
            throw new Exception("Please enter both email and password");
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM students WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $role = 'student';

        if (!$user) {
            $stmt = $conn->prepare("SELECT * FROM tutors WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $role = 'tutor';
        }

        error_log("Login attempt - Email: $email, Role found: " . ($user ? $role : 'none'));

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $role;

            if ($role === 'student') {
                header("Location: student-dashboard.php");
            } else {
                header("Location: tutor-dashboard.php");
            }
            exit();
        } else {
            throw new Exception("Invalid email or password");
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
    <title>Athena - Login</title>

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
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(114, 9, 183, 0.05));
        }
        
        .form-signin {
            max-width: 450px;
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
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-container img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        
        .form-title {
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 25px;
            text-align: center;
            font-size: 1.8rem;
        }
        
        .form-floating {
            margin-bottom: 15px;
        }
        
        .form-floating input {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            height: 55px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-floating input:focus {
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
        
        .alert-success {
            background-color: #e5ffe5;
            border-color: #ccffcc;
            color: #00cc00;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 30px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .copyright {
            font-size: 0.85rem;
            color: #adb5bd;
            text-align: center;
            margin-top: 25px;
        }
        
        @media (max-width: 576px) {
            .form-card {
                padding: 30px 20px;
            }
            
            .form-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <main class="form-signin">
        <div class="form-card">
            <div class="form-content">
                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Registration successful! Please login.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="logo-container">
                    <img src="Logo.png" alt="Athena Logo">
                </div>
                
                <h1 class="form-title">Welcome to Athena</h1>

                <form method="POST" action="login.php">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="floatingInput" name="email" placeholder="name@example.com" required>
                        <label for="floatingInput">Email address</label>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required>
                        <label for="floatingPassword">Password</label>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-sign-in-alt me-2"></i> Sign in
                        </button>
                        <a href="SignUpTutor.php" class="btn btn-secondary">
                            <i class="fas fa-user-plus me-2"></i> Create an Account
                        </a>
                    </div>
                    
                    <div class="form-footer">
                        Don't have an account? <a href="SignUpTutor.php" class="text-decoration-none">Sign up</a>
                    </div>
                </form>
                
                <p class="copyright">&copy; 2024 Athena Learning Platform</p>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>