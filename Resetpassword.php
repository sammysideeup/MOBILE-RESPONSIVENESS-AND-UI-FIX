<?php
session_start();
include "connection.php"; 

$error_message = null;
$show_form = false;
$token = $_GET['token'] ?? null;

if ($token) {
    // 1. Validate the token against the database
    // The query checks for a matching token that has NOT expired yet (thanks to the time zone fix in connection.php)
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Token is valid and not expired
        $user_row = $result->fetch_assoc();
        $_SESSION['reset_user_id'] = $user_row['id'];
        $show_form = true;
    } else {
        $error_message = 'Invalid or expired reset link. Please request a new one.';
    }
    $stmt->close();
} else {
    $error_message = 'Access denied. Missing reset token.';
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $show_form) {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['reset_user_id'] ?? null;
    $current_token = $_POST['token'] ?? null; // Pass the token via hidden field for final check

    // Simple backend check (Password complexity is handled primarily by JS for instant feedback)
    if ($new_password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else if (strlen($new_password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } else if (!$user_id) {
        $error_message = 'Session error. Please restart the reset process.';
    } else {
        // Double-check the token validity one last time
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE id = ? AND reset_token = ? AND reset_token_expires_at > NOW()");
        $stmt_check->bind_param("is", $user_id, $current_token);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows === 0) {
            $error_message = 'Token re-validation failed or expired during submission.';
        } else {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password and clear token
            $stmt_update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
            $stmt_update->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt_update->execute()) {
                $_SESSION['reset_success'] = 'Your password has been successfully reset. Please log in.';
                // Clear the session variable used to hold the user ID
                unset($_SESSION['reset_user_id']); 
                header("Location: Loginpage.php");
                exit();
            } else {
                $error_message = 'Database update failed.';
            }
            $stmt_update->close();
        }
        $stmt_check->close();
    }
}

// If an error occurred during POST, make sure the form is still shown
if ($_SERVER["REQUEST_METHOD"] == "POST" && $error_message) {
    $show_form = true;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        /* Base styles from Loginstyle.css */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        /* ---------------------------------- */
        /* 1. BODY & BACKGROUND STYLING */
        /* ---------------------------------- */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #000000ff; 
            animation: fadeIn 0.8s ease-out forwards;
            position: relative; 
            z-index: 0;
            padding: 20px;
        }
        
        /* Mobile responsiveness improvements */
        @media (max-width: 768px) {
            body {
                padding-top: 80px;
                align-items: flex-start;
                min-height: 100vh;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding-top: 70px;
                padding-left: 15px;
                padding-right: 15px;
            }
        }
        
        @keyframes fadeInUp {
             from {
                 opacity: 0;
                 transform: translateY(20px);
             }
             to {
                 opacity: 1;
                 transform: translateY(0);
             }
        }
        
        .login-container {
             animation: fadeInUp 0.6s ease forwards;
             opacity: 0;
        }
        
        html { scroll-behavior: smooth; }

        /* ---------------------------------- */
        /* 2. WRAPPER (LOGIN BOX) STYLING */
        /* ---------------------------------- */
        .wrapper {
            width: 420px;
            background: #ffffff; 
            color: #333;
            padding: 30px 40px;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08); 
            animation: slideUpAndFadeIn 0.8s ease-out 0.2s forwards;
            opacity: 0;
            z-index: 2; 
            position: relative;
            border: 1px solid #e5e5e5;
            transition: all 0.3s ease;
        }

        .wrapper:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border: 2px solid #FFD700;
        }

        .wrapper h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #000000;
            font-weight: 900;
            position: relative;
            padding-bottom: 15px;
        }

        .wrapper h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: #FFD700;
            border-radius: 2px;
        }
        
        /* ---------------------------------- */
        /* INPUT & BUTTON STYLING */
        /* ---------------------------------- */
        .input-box {
            position: relative;
            width: 100%;
            height: 50px;
            margin: 20px 0;
        }

        .input-box input {
            width: 100%;
            height: 100%;
            background: #f8f8f8; 
            border: 2px solid #e5e5e5;
            outline: none;
            border-radius: 8px;
            padding: 0 45px 0 15px;
            font-size: 16px;
            color: #333;
            transition: all 0.3s ease;
        }

        .input-box input:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }

        .input-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #000000; 
            font-size: 20px;
        }
        
        .btn {
            width: 100%;
            height: 45px;
            background: #000000; 
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            color: #FFD700; 
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #333333;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* Password Validation Feedback */
        #password-feedback {
            margin-top: 5px;
            margin-bottom: 10px;
            padding-left: 5px;
        }
        .validation-item {
            font-size: 0.9rem;
            color: #666;
            transition: color 0.3s ease;
        }
        .validation-item i {
            margin-right: 5px;
        }
        .valid {
            color: #28a745 !important; /* Green */
        }
        .invalid {
            color: #dc3545 !important; /* Red */
        }

        /* ---------------------------------- */
        /* TOAST & NAVIGATION STYLING */
        /* ---------------------------------- */
        .toast-alert {
            position: fixed;
            top: 100px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #000000; 
            color: #FFD700; 
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            max-width: 400px;
            z-index: 1000;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            opacity: 0;
            transform: translateX(-50%) translateY(-100%);
            transition: opacity 0.3s ease, transform 0.3s ease;
            border: 1px solid #FFD700;
        }

        .toast-alert.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        
        .toast-alert.error { border-left: 4px solid #dc3545; }
        .toast-alert.success { border-left: 4px solid #28a745; }
        .toast-alert.info { border-left: 4px solid #FFD700; }

        #toast-message { flex-grow: 1; margin-right: 10px; }

        .toast-close {
            background: none; border: none; color: #FFD700; font-size: 24px; cursor: pointer;
            opacity: 0.7; transition: opacity 0.2s ease;
        }

        /* Animations */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUpAndFadeIn { 
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Mobile Navbar Styles */
        .mobile-navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #000000;
            color: #ffffff;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: none;
        }
         
        @media (max-width: 768px) {
            .mobile-navbar {
                display: block;
            }
        }
         
        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
        }
         
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
         
        .navbar-brand h2 {
            color: #FFD700;
            font-weight: 700;
            font-size: 1.3rem;
            margin: 0;
        }
         
        .navbar-brand i {
            color: #FFD700;
        }
         
        /* Back to home button styling */
        .back-home {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 100;
        }
         
        .back-home-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: #000000;
            color: #FFD700;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
         
        .back-home-btn:hover {
            background-color: #FFD700;
            color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 215, 0, 0.3);
        }
         
        @media (max-width: 768px) {
            .back-home {
                display: none;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body { 
                padding-top: 80px;
                padding-left: 20px;
                padding-right: 20px;
            }
            
            .wrapper { 
                width: 100%; 
                max-width: 400px; 
                padding: 25px 30px; 
                margin: 0 auto;
            }
            
            .wrapper h1 {
                font-size: 1.8rem;
                margin-bottom: 25px;
            }
            
            .toast-alert { 
                top: 90px; 
                left: 20px; 
                right: 20px; 
                max-width: none; 
                transform: translateY(-100%);
                width: calc(100% - 40px);
            }
            
            .toast-alert.show { 
                transform: translateY(0); 
            }
            
            #password-feedback {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding-top: 70px;
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .wrapper {
                padding: 22px 25px;
                border-radius: 12px;
            }
            
            .wrapper h1 {
                font-size: 1.6rem;
                margin-bottom: 22px;
            }
            
            .input-box {
                height: 48px;
                margin: 15px 0;
            }
            
            .input-box input {
                font-size: 15px;
                padding: 0 40px 0 15px;
            }
            
            .input-box i {
                font-size: 18px;
                right: 12px;
            }
            
            .btn {
                height: 44px;
                font-size: 15px;
            }
            
            #password-feedback {
                margin-bottom: 8px;
            }
            
            .validation-item {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 360px) {
            body {
                padding-top: 65px;
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .wrapper {
                padding: 20px;
            }
            
            .wrapper h1 {
                font-size: 1.5rem;
                margin-bottom: 20px;
            }
            
            .input-box {
                height: 46px;
            }
            
            .input-box input {
                font-size: 14px;
            }
            
            .btn {
                height: 42px;
                font-size: 14px;
            }
            
            .validation-item {
                font-size: 0.75rem;
            }
        }
        
        /* Fix for mobile input zoom */
        @media screen and (max-width: 768px) {
            input, select, textarea {
                font-size: 16px !important;
            }
        }
        
        /* Prevent horizontal scrolling */
        html, body {
            overflow-x: hidden;
            width: 100%;
        }
        
        /* Touch-friendly tap targets */
        @media (max-width: 768px) {
            .btn, .nonMember-link a {
                min-height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- Mobile Navbar -->
<nav class="mobile-navbar" id="mobileNavbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <i class='bx bx-dumbbell text-yellow-500 text-2xl'></i>
            <h2>ECG Fitness</h2>
        </div>
        <a href="website.php" class="text-yellow-500 text-lg">
            <i class='bx bx-home'></i>
        </a>
    </div>
</nav>

<!-- Back to Home Button (Desktop) -->
<div class="back-home">
    <a href="website.php" class="back-home-btn">
        <i class='bx bx-arrow-back'></i>
        Back to Home
    </a>
</div>

<div id="toast-alert" class="toast-alert" role="alert" aria-live="polite" style="display: none;">
    <span id="toast-message"></span>
    <button id="toast-close" class="toast-close" aria-label="Close alert">&times;</button>
</div>

<main class="w-full max-w-md mx-auto">
    <?php if ($show_form): ?>
    <div class="wrapper login-container">
        <form action="Resetpassword.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" onsubmit="return validateForm()"> 
            <h1 class="text-2xl md:text-3xl font-bold mb-6 md:mb-8 text-center">Set New Password</h1>

            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="input-box">
                <input type="password" id="password" placeholder="New Password" name="password" required>
                <i class='bx bx-show' id="togglePassword" style="cursor: pointer;"></i> 
            </div>
            
            <div id="password-feedback" class="text-sm">
                <p id="length" class="validation-item"><i class='bx bx-x-circle'></i> 8+ characters</p>
                <p id="uppercase" class="validation-item"><i class='bx bx-x-circle'></i> Uppercase letter</p>
                <p id="lowercase" class="validation-item"><i class='bx bx-x-circle'></i> Lowercase letter</p>
                <p id="number" class="validation-item"><i class='bx bx-x-circle'></i> One number</p>
            </div>

            <div class="input-box">
                <input type="password" id="confirm_password" placeholder="Confirm New Password" name="confirm_password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>

            <button type="submit" class="btn">Reset Password</button>

            <div class="nonMember-link text-center mt-4">
                <p style="color: #666;">
                    <a href="Loginpage.php" style="color: #000000; font-weight: 700;">Back to Login</a> 
                </p>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="wrapper login-container text-center">
        <h1 class="text-2xl md:text-3xl font-bold mb-6 md:mb-8 text-center">Error</h1>
        <p class="mb-4 text-red-500">
            <?php echo htmlspecialchars($error_message); ?>
        </p>
        <a href="Forgotpass.php" class="btn">Request New Link</a>
    </div>
    <?php endif; ?>
</main>

<script>
    // --- Front-end Security Validation ---

    const passwordInput = document.getElementById("password");
    const confirmInput = document.getElementById("confirm_password");
    const togglePassword = document.getElementById("togglePassword");
    
    // Validation rules elements
    const lengthRule = document.getElementById("length");
    const uppercaseRule = document.getElementById("uppercase");
    const lowercaseRule = document.getElementById("lowercase");
    const numberRule = document.getElementById("number");

    // 1. Password Toggle Logic
    if (togglePassword) {
        togglePassword.addEventListener("click", function () {
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);
            
            // Toggle the icon
            this.classList.toggle("bx-show");
            this.classList.toggle("bx-hide");
        });
    }

    // 2. Real-time Password Strength Feedback
    if (passwordInput) {
        passwordInput.addEventListener('keyup', function() {
            const value = passwordInput.value;

            // Check 1: Length (8+ characters)
            const isLengthValid = value.length >= 8;
            updateRule(lengthRule, isLengthValid);

            // Check 2: Uppercase
            const isUppercaseValid = /[A-Z]/.test(value);
            updateRule(uppercaseRule, isUppercaseValid);

            // Check 3: Lowercase
            const isLowercaseValid = /[a-z]/.test(value);
            updateRule(lowercaseRule, isLowercaseValid);

            // Check 4: Number
            const isNumberValid = /[0-9]/.test(value);
            updateRule(numberRule, isNumberValid);
        });
    }

    function updateRule(element, isValid) {
        if (element) {
            element.classList.toggle('valid', isValid);
            element.classList.toggle('invalid', !isValid);
            element.querySelector('i').className = isValid ? 'bx bx-check-circle' : 'bx bx-x-circle';
        }
    }

    // 3. Form Submission Validation
    function validateForm() {
        if (!passwordInput || !confirmInput) return true;
        
        const password = passwordInput.value;
        const confirmPassword = confirmInput.value;

        // Re-check all rules for submission
        const isLengthValid = password.length >= 8;
        const isUppercaseValid = /[A-Z]/.test(password);
        const isLowercaseValid = /[a-z]/.test(password);
        const isNumberValid = /[0-9]/.test(password);

        if (!isLengthValid || !isUppercaseValid || !isLowercaseValid || !isNumberValid) {
            showToast('Password must meet all complexity requirements.', 'error');
            return false;
        }

        if (password !== confirmPassword) {
            showToast('New password and confirm password do not match.', 'error');
            return false;
        }

        // Optional: Check if the user is submitting the same password
        // This is complex without knowing the old hash, so we omit it here but note it as a security best practice.

        return true; // All checks passed
    }
    
    // --- Toast Alert Script ---
    function showToast(message, type = 'error') {
        const toast = document.getElementById('toast-alert');
        const messageEl = document.getElementById('toast-message');
        const closeBtn = document.getElementById('toast-close');
        
        if (!toast || !messageEl || !message) return;
        
        messageEl.textContent = message;
        toast.className = `toast-alert show ${type}`;
        toast.style.display = 'flex';
        
        setTimeout(() => { toast.focus(); }, 10);
        
        const autoHideTimer = setTimeout(() => { hideToast(); }, 5000); 
        
        function hideToast() {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.style.display = 'none';
                clearTimeout(autoHideTimer);
            }, 300);
        }

        if (closeBtn) {
            closeBtn.onclick = hideToast;
        }
        toast.onclick = function(e) {
            if (e.target === toast) hideToast(); 
        };
    }
    
    // Display PHP error message if present
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($error_message): ?>
            showToast('<?php echo htmlspecialchars($error_message); ?>', 'error');
        <?php endif; ?>
        
        // Mobile responsiveness: Prevent zoom on input focus
        document.addEventListener('touchstart', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                document.body.style.zoom = "100%";
            }
        }, { passive: true });
    });
</script>

</body>
</html>