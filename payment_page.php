<?php


error_log("TEST LOGGING");
    session_start();
    require 'db.php';


    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        header("Location: login.php");
        exit();
    }

    $student_id = $_SESSION['user_id'];
    $booking_id = null;
    $booking_details = null;
    $error_message = '';
    $success_message = '';

    if (isset($_GET['booking_id'])) {
        $booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
        if ($booking_id) {
$stmt = $conn->prepare("SELECT b.*, t.name as tutor_name
                                    FROM bookings b 
                                    JOIN tutors t ON b.tutor_id = t.id 
                                    WHERE b.id = ? AND b.student_id = ? AND (b.status = 'accepted' OR b.status = 'pending' OR b.status IS NULL)");
$stmt->execute([$booking_id, $student_id]);
$booking_details = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking_details) {
                $error_message = "Invalid booking ID or booking not awaiting payment.";
                $booking_id = null; 
            }
        } else {
            $error_message = "Invalid booking ID specified.";
        }
    } else {
        $error_message = "No booking ID specified.";
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_payment']) && $booking_id && $booking_details) {
        $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
        
        if (empty($payment_method)) {
            $error_message = "Please select a payment method.";
        } elseif (!isset($_FILES['proof_of_payment']) || $_FILES['proof_of_payment']['error'] != UPLOAD_ERR_OK) {
            $error_message = "Please upload a proof of payment. Error code: " . ($_FILES['proof_of_payment']['error'] ?? 'Unknown');
        } else {
            $target_dir = "uploads/proofs/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true); 
            }
            $original_file_name = basename($_FILES["proof_of_payment"]["name"]);
            $file_extension = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));
            $safe_file_name = "booking_" . $booking_id . "_" . uniqid() . "." . $file_extension;
            $target_file = $target_dir . $safe_file_name;
            
            $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($file_extension, $allowed_types)) {
                $error_message = "Sorry, only JPG, JPEG, PNG & PDF files are allowed.";
            } elseif ($_FILES["proof_of_payment"]["size"] > 5000000) {
                $error_message = "Sorry, your file is too large (max 5MB).";
            } else {
                if (move_uploaded_file($_FILES["proof_of_payment"]["tmp_name"], $target_file)) {
                    $new_booking_status = 'confirmed';
                    $update_stmt = $conn->prepare("UPDATE bookings SET status = ?, payment_method = ?, proof_file_path = ? WHERE id = ? AND student_id = ?");
                    
                    error_log("[PAYMENT_PAGE_DEBUG] booking_id=$booking_id, student_id=$student_id, payment_method=$payment_method, target_file=$target_file, booking_details=" . print_r($booking_details, true));

                    if ($update_stmt->execute([$new_booking_status, $payment_method, $target_file, $booking_id, $student_id])) {
                        error_log("[PAYMENT_PAGE] Update succeeded for booking_id=$booking_id");
                        $success_message = "Payment proof uploaded successfully! Your session is confirmed. You will be redirected shortly.";
                        header("refresh:3;url=my-sessions.php?tab=upcoming");
                        $stmt_refetch = $conn->prepare("SELECT b.*, t.name as tutor_name FROM bookings b JOIN tutors t ON b.tutor_id = t.id WHERE b.id = ?");
                        $stmt_refetch->execute([$booking_id]);
                        $booking_details = $stmt_refetch->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $errorInfo = $update_stmt->errorInfo();
                        error_log("[PAYMENT_PAGE] Update failed: " . print_r($errorInfo, true));
                        $error_message = "Failed to update booking status. Please try again or contact support.";
                        if (isset($target_file) && file_exists($target_file)) {
                            unlink($target_file);
                        }
                    }
                } else {
                    $error_message = "Sorry, there was an error uploading your file.";
                }
            }
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Complete Payment</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <style>
            :root {
                --primary: #4361ee;
                --primary-light: #4895ef;
                --secondary: #3f37c9;
                --success: #4cc9f0;
                --info: #4895ef;
                --warning: #f72585;
                --danger: #f72585;
                --light: #f8f9fa;
                --dark: #212529;
                --gray: #6c757d;
                --border-radius: 10px;
            }
            
            body {
                font-family: 'Inter', sans-serif;
                background-color: #f5f7ff;
                color: #333;
            }
            
            .payment-page-container {
                max-width: 1000px;
                margin: 40px auto;
                padding: 0 20px;
            }
            
            .payment-header {
                color: var(--dark);
                font-weight: 600;
                text-align: center;
                margin-bottom: 40px;
                position: relative;
                padding-bottom: 15px;
            }
            
            .payment-header:after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 60px;
                height: 4px;
                background: var(--primary);
                border-radius: 2px;
            }
            
            .card {
                border: none;
                border-radius: var(--border-radius);
                box-shadow: 0 8px 20px rgba(0,0,0,0.06);
                overflow: hidden;
                transition: all 0.3s ease;
            }
            
            .booking-details-card {
                background: linear-gradient(145deg, #ffffff, #f9fbff);
                border-left: none;
                margin-bottom: 30px;
            }
            
            .booking-details-card .card-header {
                background-color: var(--primary);
                color: white;
                font-weight: 600;
                padding: 15px 20px;
                border-bottom: none;
                font-size: 1.1rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .booking-details-card .card-body {
                padding: 25px;
            }
            
            .booking-detail-row {
                padding: 12px 0;
                border-bottom: 1px solid rgba(0,0,0,0.05);
            }
            
            .booking-detail-row:last-child {
                border-bottom: none;
            }
            
            .booking-detail-label {
                font-weight: 600;
                color: var(--gray);
            }
            
            .booking-detail-value {
                font-weight: 500;
                color: var(--dark);
            }
            
            .payment-methods-container {
                margin-bottom: 30px;
            }
            
            .payment-method-card {
                border: 2px solid transparent;
                border-radius: var(--border-radius);
                overflow: hidden;
                cursor: pointer;
                height: 100%;
                background: #fff;
                position: relative;
                transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            }
            
            .payment-method-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            }
            
            .payment-method-card.selected {
                border-color: var(--primary);
                box-shadow: 0 10px 25px rgba(67, 97, 238, 0.25);
            }
            
            .payment-method-header {
                background-color: #f8f9fa;
                padding: 15px;
                text-align: center;
                font-weight: 600;
                color: var(--dark);
                border-bottom: 1px solid #eee;
            }
            
            .payment-method-body {
                padding: 20px;
                text-align: center;
            }
            
            .payment-method-body img {
                max-width: 150px;
                border: 1px solid #eee;
                padding: 10px;
                border-radius: 8px;
                margin-bottom: 15px;
                background-color: #fff;
            }
            
            .payment-method-footer {
                padding: 15px;
                background-color: #f8f9fa;
                text-align: center;
                border-top: 1px solid #eee;
            }
            
            .payment-method-radio {
                position: absolute;
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .payment-method-radio + label {
                cursor: pointer;
                padding: 0.5rem 1.5rem;
                border-radius: 30px;
                background-color: #e9ecef;
                color: #495057;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            
            .payment-method-radio:checked + label {
                background-color: var(--primary);
                color: white;
            }
            
            .upload-section {
                background: white;
                border-radius: var(--border-radius);
                padding: 25px;
                margin-top: 30px;
                box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            }
            
            .upload-section-header {
                font-weight: 600;
                margin-bottom: 20px;
                color: var(--dark);
                display: flex;
                align-items: center;
            }
            
            .upload-icon {
                margin-right: 10px;
                font-size: 1.2rem;
                color: var(--primary);
            }
            
            .file-upload-wrapper {
                position: relative;
                width: 100%;
                height: 150px;
                border: 2px dashed #ced4da;
                border-radius: var(--border-radius);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                background-color: #f8f9fa;
                transition: all 0.3s ease;
                margin-bottom: 15px;
            }
            
            .file-upload-wrapper:hover {
                border-color: var(--primary-light);
                background-color: rgba(67, 97, 238, 0.03);
            }
            
            .file-upload-input {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                opacity: 0;
                cursor: pointer;
                z-index: 10;
            }
            
            .file-upload-text {
                text-align: center;
            }
            
            .file-upload-icon {
                font-size: 2rem;
                color: var(--primary);
                margin-bottom: 10px;
            }
            
            .file-upload-filename {
                margin-top: 10px;
                font-weight: 500;
                color: var(--primary);
                display: none;
            }
            
            .submit-btn {
                background: linear-gradient(45deg, var(--primary), var(--secondary));
                border: none;
                border-radius: 30px;
                padding: 14px 30px;
                font-weight: 600;
                font-size: 1.1rem;
                color: white;
                transition: all 0.3s ease;
                box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
                margin-top: 20px;
            }
            
            .submit-btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 15px 25px rgba(67, 97, 238, 0.4);
            }
            
            .submit-btn:active {
                transform: translateY(1px);
            }
            
            .submit-btn i {
                margin-right: 10px;
            }
            
            .status-badge {
                padding: 8px 16px;
                border-radius: 30px;
                font-weight: 500;
                font-size: 0.9rem;
                display: inline-flex;
                align-items: center;
            }
            
            .status-badge i {
                margin-right: 6px;
            }
            
            .status-awaiting {
                background-color: rgba(72, 149, 239, 0.15);
                color: var(--info);
            }
            
            .status-review {
                background-color: rgba(67, 97, 238, 0.15);
                color: var(--primary);
            }
            
            .alert {
                border-radius: var(--border-radius);
                font-weight: 500;
                padding: 15px 20px;
                margin-bottom: 25px;
            }
            
            .alert-success {
                background-color: rgba(76, 201, 240, 0.15);
                border-left: 4px solid var(--success);
                color: #0a6b7d;
            }
            
            .alert-danger {
                background-color: rgba(247, 37, 133, 0.15);
                border-left: 4px solid var(--danger);
                color: #9c1356;
            }
            
            .alert-warning {
                background-color: rgba(255, 203, 119, 0.15);
                border-left: 4px solid #ffcb77;
                color: #9c6518;
            }
            
            .alert-info {
                background-color: rgba(72, 149, 239, 0.15);
                border-left: 4px solid var(--info);
                color: #0d47a1;
            }
            
            .back-btn {
                background-color: #6c757d;
                color: white;
                border: none;
                border-radius: 30px;
                padding: 10px 20px;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .back-btn:hover {
                background-color: #5a6268;
                color: white;
            }
            
            @media (max-width: 767.98px) {
                .payment-header {
                    font-size: 1.5rem;
                }
                
                .booking-detail-row {
                    padding: 10px 0;
                }
                
                .payment-method-body img {
                    max-width: 120px;
                }
                
                .file-upload-wrapper {
                    height: 120px;
                }
                
                .submit-btn {
                    padding: 12px 25px;
                    font-size: 1rem;
                }
            }
        </style>
    </head>
    <body>

    <div class="payment-page-container">
        <h1 class="payment-header">Complete Your Payment</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($booking_id && $booking_details && (strtolower($booking_details['status']) === 'accepted' || strtolower($booking_details['status']) === 'payment_uploaded' && !$success_message) ): ?>
            
            <?php if (strtolower($booking_details['status']) === 'payment_uploaded' && !$success_message): ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    You have already uploaded payment proof for this session. It is currently under review.
                </div>
            <?php endif; ?>

            <div class="card booking-details-card">
                <div class="card-header">
                    <i class="fas fa-file-invoice-dollar me-2"></i>Booking Details
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="booking-detail-row">
                                <div class="booking-detail-label">Tutor</div>
                                <div class="booking-detail-value"><?php echo htmlspecialchars($booking_details['tutor_name']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="booking-detail-row">
                                <div class="booking-detail-label">Booking ID</div>
                                <div class="booking-detail-value">#<?php echo $booking_id; ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="booking-detail-row">
                                <div class="booking-detail-label">Date</div>
                                <div class="booking-detail-value"><?php echo date('F j, Y', strtotime($booking_details['session_date'])); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="booking-detail-row">
                                <div class="booking-detail-label">Time</div>
                                <div class="booking-detail-value"><?php echo date('g:i A', strtotime($booking_details['session_time'])); ?></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="booking-detail-row">
                                <div class="booking-detail-label">Status</div>
                                <div class="booking-detail-value">
                                    <?php if (strtolower($booking_details['status']) === 'accepted'): ?>
                                        <span class="status-badge status-awaiting">
                                            <i class="fas fa-clock"></i> Awaiting Your Payment
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-review">
                                            <i class="fas fa-sync-alt"></i> Payment Under Review
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (strtolower($booking_details['status']) === 'accepted'): ?>
            <form action="payment_page.php?booking_id=<?php echo $booking_id; ?>" method="POST" enctype="multipart/form-data" id="paymentForm">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">

                <div class="payment-methods-container">
                    <div class="upload-section-header mb-4">
                        <i class="fas fa-credit-card upload-icon"></i> Select Payment Method
                    </div>
                    
                    <div class="row g-4">
                        <?php 
                        $payment_options = [
                            ['name' => 'GCash', 'icon' => 'fa-mobile-alt', 'qr' => 'placeholder_qr_gcash.png'],
                            ['name' => 'Maya', 'icon' => 'fa-money-bill-wave', 'qr' => 'placeholder_qr_maya.png'],
                            ['name' => 'Instapay', 'icon' => 'fa-university', 'qr' => 'placeholder_qr_instapay.png', 'subtext' => '(Bank Transfer)']
                        ];
                        ?>
                        
                        <?php foreach ($payment_options as $index => $option): ?>
                        <div class="col-md-4">
                            <div class="payment-method-card" id="payment_card_<?php echo $index; ?>">
                                <div class="payment-method-header">
                                    <i class="fas <?php echo $option['icon']; ?> me-2"></i><?php echo $option['name']; ?> <?php echo $option['subtext'] ?? ''; ?>
                                </div>
                                <div class="payment-method-body">
                                    <img src="<?php echo $option['qr']; ?>" alt="<?php echo $option['name']; ?> QR Code" class="img-fluid">
                                    <p class="text-muted mb-0"><small>Scan to pay with <?php echo $option['name']; ?></small></p>
                                </div>
                                <div class="payment-method-footer">
                                    <input type="radio" class="payment-method-radio" id="payment_<?php echo strtolower($option['name']); ?>" name="payment_method" value="<?php echo $option['name']; ?>" required>
                                    <label for="payment_<?php echo strtolower($option['name']); ?>">Select</label>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="upload-section">
                    <div class="upload-section-header">
                        <i class="fas fa-file-upload upload-icon"></i> Upload Proof of Payment
                    </div>
                    
                    <div class="file-upload-wrapper">
                        <input type="file" id="proof_of_payment" name="proof_of_payment" class="file-upload-input" accept=".jpg, .jpeg, .png, .pdf" required>
                        <div class="file-upload-text">
                            <div class="file-upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div>Drag & drop files or <span class="text-primary">browse</span></div>
                            <div class="text-muted small">Supported formats: JPG, JPEG, PNG, PDF. Max size: 5MB.</div>
                        </div>
                    </div>
                    <div class="file-upload-filename" id="fileNameDisplay"></div>
                    
                    <div class="d-grid">
                        <button type="submit" name="submit_payment" class="submit-btn">
                            <i class="fas fa-check-circle"></i> Submit Payment Proof
                        </button>
                    </div>
                </div>
            </form>
            <?php endif; ?>

        <?php elseif (!$success_message): ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                There was an issue loading payment details, or the booking is not awaiting payment. Please ensure you have a valid booking.
            </div>
            <a href="my-sessions.php" class="btn back-btn">
                <i class="fas fa-arrow-left me-2"></i> Go to My Sessions
            </a>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentCards = document.querySelectorAll('.payment-method-card');
        const paymentRadios = document.querySelectorAll('.payment-method-radio');
        
        paymentCards.forEach((card, index) => {
            card.addEventListener('click', function() {
                paymentCards.forEach(c => c.classList.remove('selected'));
                
                card.classList.add('selected');
                
                paymentRadios[index].checked = true;
            });
        });
        
        const fileInput = document.getElementById('proof_of_payment');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    fileNameDisplay.textContent = 'Selected file: ' + this.files[0].name;
                    fileNameDisplay.style.display = 'block';
                } else {
                    fileNameDisplay.style.display = 'none';
                }
            });
        }
    });
    </script>

    </body>
    </html>