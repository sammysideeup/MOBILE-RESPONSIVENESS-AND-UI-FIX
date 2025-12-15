<?php
session_start();
include "connection.php"; 

// Assuming connection.php contains the time zone sync logic

// Get the message sent from send_forgot_password.php
$reset_message = $_SESSION['reset_message'] ?? null;
unset($_SESSION['reset_message']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    
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
            
            /* White background matching Nonmember.php */
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
        
        /* Styles from Loginpage.php <style> block (background/alignment already covered) */
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
             /* Applying the animation */
             animation: fadeInUp 0.6s ease forwards;
             opacity: 0;
        }
        
        /* Smooth scrolling */
        html {
             scroll-behavior: smooth;
        }

        /* ---------------------------------- */
        /* 2. WRAPPER (LOGIN BOX) STYLING */
        /* ---------------------------------- */
        .wrapper {
            width: 420px;
            
            /* White background matching Nonmember.php cards */
            background: #ffffff; 
            
            color: #333;
            padding: 30px 40px;
            border-radius: 1rem;
            /* Updated shadow to match Nonmember.php cards */
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

        .input-box {
            position: relative;
            width: 100%;
            height: 50px;
            margin: 20px 0;
        }

        .input-box input {
            width: 100%;
            height: 100%;
            /* Light grey input fields */
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

        .remember-forgot {
            display: flex;
            justify-content: flex-end; 
            font-size: 14px;
            margin: 15px 0 25px;
        }

        .remember-forgot a {
            color: #000000;
            text-decoration: none;
            font-weight: 500;
        }

        .remember-forgot a:hover {
            text-decoration: underline;
            color: #FFD700;
        }

        .btn {
            width: 100%;
            height: 45px;
            /* Black button matching Nonmember.php theme */
            background: #000000; 
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            /* Gold text on black button */
            color: #FFD700; 
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #333333;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        .nonMember-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .nonMember-link p {
            color: #666;
        }

        .nonMember-link a {
            color: #000000;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .nonMember-link a:hover {
            color: #FFD700;
            text-decoration: underline;
        }

        /* Toast Alert Styling - Updated to match theme */
        .toast-alert {
            position: fixed;
            top: 100px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #000000; /* Black background */
            color: #FFD700; /* Gold text */
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            max-width: 400px;
            z-index: 1000;
            font-family: "Poppins", sans-serif;
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

        .toast-alert.error {
            background-color: #000000;
            border-left: 4px solid #FFD700;
        }
        
        .toast-alert.success {
             background-color: #000000;
             border-left: 4px solid #FFD700; 
        }
        
        .toast-alert.info {
             background-color: #000000;
             border-left: 4px solid #FFD700;
        }

        #toast-message {
            flex-grow: 1;
            margin-right: 10px;
        }

        .toast-close {
            background: none;
            border: none;
            color: #FFD700;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }

        .toast-close:hover,
        .toast-close:focus {
            opacity: 1;
            outline: 2px solid #FFD700;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUpAndFadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px);
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        /* Mobile Navbar Styles from Loginpage.php <style> block */
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

        /* Responsive Design from Loginstyle.css */
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
            }
            
            .toast-alert.show {
                transform: translateY(0);
            }
            
            .nonMember-link {
                font-size: 13px;
            }
            
            .remember-forgot {
                font-size: 13px;
                margin: 15px 0 22px;
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
            
            .nonMember-link {
                font-size: 12px;
                margin-top: 18px;
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
            
            .nonMember-link {
                font-size: 11px;
            }
        }

        /* Animation for mobile navbar */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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
            .btn, .remember-forgot a, .nonMember-link a {
                min-height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

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
    <div class="wrapper login-container">
        <form action="send_forgot_password.php" method="POST"> 
            <h1 class="text-2xl md:text-3xl font-bold mb-6 md:mb-8 text-center" style="color: #000000;">Forgot Password</h1>

            <div class="input-box">
                <input type="email" placeholder="Enter your registered email" name="email" required>
                <i class='bx bxs-envelope'></i>
            </div>

            <button type="submit" class="btn">Send Reset Link</button>

            <div class="nonMember-link text-center mt-4">
                <p style="color: #666;">
                    Remembered your password? 
                    <a href="Loginpage.php" style="color: #000000; font-weight: 700;">Login Here</a> 
                </p>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toast = document.getElementById('toast-alert');
        const messageEl = document.getElementById('toast-message');
        const closeBtn = document.getElementById('toast-close');

        function showToast(message, type = 'error') {
            if (!toast || !messageEl || !message) return;
            
            messageEl.textContent = message;
            // Set the class based on the message type (error, success, info)
            toast.className = `toast-alert show ${type}`;
            toast.style.display = 'flex';
            
            // Re-apply show class after display is set
            setTimeout(() => {
                toast.focus(); 
            }, 10);
            
            const autoHideTimer = setTimeout(() => {
                hideToast();
            }, 5000); // 5 seconds for reset messages
            
            function hideToast() {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.style.display = 'none';
                    clearTimeout(autoHideTimer);
                }, 300);
            }

            closeBtn.onclick = hideToast;
            toast.onclick = (e) => {
                if (e.target === toast) hideToast(); 
            };
        }
       
        // Check for session messages from the submission script
        <?php if (isset($reset_message)): ?>
            showToast('<?php echo $reset_message['text']; ?>', '<?php echo $reset_message['type']; ?>');
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