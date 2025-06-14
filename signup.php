<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Basic validation
        if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
            throw new Exception("All fields are required");
        }
        
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match");
        }

        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM tutor WHERE email = :email");
        $check->bindParam(':email', $email);
        $check->execute();
        if ($check->fetch()) {
            throw new Exception("Email already registered");
        }

        $stmt = $conn->prepare("INSERT INTO tutor (name, email, phone, password) VALUES (:name, :email, :phone, :password)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':password', $password);

        if ($stmt->execute()) {
            header("Location: login.php?registered=1");
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
    <title>Student Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        #navbar{
        margin-bottom: 2%;
        }
        #navbar a{
        font-weight: 600;
        }

        html, .body {
            height: 95%;
        }
        .body {
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .form-signin {
            max-width: 500px;
            padding: 20px;
            margin: auto;
        }
        .form-signin .form-floating:focus-within {
            z-index: 1;
        }
        .form-signin input[type="email"] {
            margin-bottom: 10px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
        .form-floating {
            margin-bottom: 15px; 
        }
        .checkbox {
            margin-bottom: 15px; 
        }
        .btn {
            margin-bottom: 10px; 
        }
    </style>
</head>
<body class="align-baseline">
    <!-- navigation -->
    <div id="navbar" class="navbar navbar-inverse navbar-expand-md px-3">

        <a class="navbar-brand" href="frontpage.html">Athena</a>
      
          <div class="navbar-nav collapse navbar-collapse">
            <a class="nav-item nav-link" href="frontpage.html">Home</a>
            <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              Find a Tutor
            </a>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="find-fl.html">Foreign Language</a>
                <a class="dropdown-item">Mathematics</a>
                <a class="dropdown-item">Art</a>
              </div>
            </div>
            <a class="nav-item nav-link" href="#become">Become a Tutor</a>
            <div class="navbar-nav ms-auto">
              <a class="nav-item nav-link " href="login.html">Login</a>
            </div>
          </div>
    
        </div>    
    <!-- end of navigation -->
    <main class="form-signin w-100 m-auto body">
        <div class="modal-content rounded-4 shadow modal-body p-5 pt-5">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="signup.php">
                <div class="align-baseline text-center">
                    <h1 class="h3 mb-4 fw-normal">Student Sign Up</h1>    
                </div>
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

                <div>
                    <button class="w-100 btn btn-lg btn-primary" type="submit">Sign Up</button>
                    <a href="login.php" class="w-100 btn btn-lg btn-secondary mt-2">Back to Login</a>
                </div>
                <div class="checkbox mb-3">
                    <label>
                        <input type="checkbox" value="remember-me"> Remember me
                    </label>
                </div>

                <div>
                    <button class="w-100 mb-2 btn btn-lg btn-primary" type="submit">Sign in</button>
                    <button class="w-100 btn btn-lg btn-secondary" type="submit">Sign up</button>
                </div>                
                
                <p class="mt-4 mb-3 text-muted text-center">© 2017–2024</p>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>