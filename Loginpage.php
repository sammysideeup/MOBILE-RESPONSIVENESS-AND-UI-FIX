<?php
session_start();
include "connection.php"; 

// Clear any previous error message
unset($_SESSION['error_message']);

$conn = new mysqli("localhost", "root", "", "ecg_fitness");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    //Admin Account
    $admin_email = "admin@ecg.com";
    $admin_password = "admin123";

    // Admin login
    if ($email === $admin_email && $password === $admin_password) {
        header("Location: Admindashboard.php");
        exit();
    }

    // Regular user login
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        // ✅ store email in session
        if (password_verify($password, $row['password'])) {
            $_SESSION['email'] = $email; 
            header("Location: membership.php");
            exit();
        } else {
            $_SESSION['error_message'] = 'Incorrect password';
        }
    } else {
        // 👤 Trainer login (check trainers table)
        $stmt_trainer = $conn->prepare("SELECT * FROM trainers WHERE email = ? AND password = ?");
        $stmt_trainer->bind_param("ss", $email, $password); // Assumes trainer passwords are stored in plaintext (not recommended—consider hashing)
        $stmt_trainer->execute();
        $trainer_result = $stmt_trainer->get_result();

        if ($trainer_result->num_rows === 1) {
            $trainer = $trainer_result->fetch_assoc();
            $_SESSION['trainer_id'] = $trainer['trainer_id'];
            $_SESSION['trainer_name'] = $trainer['name'];
            $_SESSION['role'] = 'trainer';
            header("Location: Trainerdashboard.php");
            exit();
        } else {
            $_SESSION['error_message'] = 'Email not found';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoginPage</title>
    <link rel="stylesheet" href="Loginstyle.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Additional styles matching Nonmember.php */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: #000000ff;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        /* Animation for card entrance */
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
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Mobile navbar styles - matching Nonmember.php */
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
        
        /* Mobile responsiveness improvements */
        @media (max-width: 768px) {
            main {
                width: 100%;
                max-width: 400px;
                margin: 0 auto;
            }
            
            .wrapper {
                width: 100%;
                margin: 0 auto;
            }
        }
        
        @media (max-width: 480px) {
            .wrapper {
                padding: 25px 30px;
            }
            
            .wrapper h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>

<!-- Mobile Top Navbar (matches Nonmember.php) -->
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

<!-- Toast Alert HTML -->
<div id="toast-alert" class="toast-alert" role="alert" aria-live="polite" style="display: none;">
    <span id="toast-message"></span>
    <button id="toast-close" class="toast-close" aria-label="Close alert">&times;</button>
</div>

<main>
<section>
<div class="wrapper login-container"> 
    <form method="POST">
        <h1 class="text-3xl font-bold mb-8 text-center" style="color: #000000;">Login</h1>

        <div class="input-box">
            <input type="text" placeholder="Email" name="email" required style="background: #f8f8f8; border: 2px solid #e5e5e5;">
            <i class='bx bxs-user' style="color: #000000;"></i>
        </div>

        <div class="input-box">
            <input type="password" id="password" placeholder="Password" name="password" required style="background: #f8f8f8; border: 2px solid #e5e5e5;">
            <i class='bx bx-show' id="togglePassword" style="cursor: pointer; color: #000000;"></i>
        </div>

        <div class="remember-forgot">
            <a href="Forgotpass.php" style="color: #000000; font-weight: 500;">Forgot password?</a>
        </div>

        <button type="submit" class="btn" style="background-color: #000000; color: #FFD700; font-weight: 700;">Login</button>

        <div class="nonMember-link">
            <p style="color: #666;">Not a member? 
                <a href="Nonmember.php" style="color: #000000; font-weight: 700;">Click Here</a> 
                <span style="color: #666;"> or </span>
                <a href="Register.php" style="color: #000000; font-weight: 700;">Register</a>
            </p>
        </div>

        
    </form>
</div>
</section>
</main>

<script>
    // Password Toggle
    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");

    togglePassword.addEventListener("click", function () {
        const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
        passwordInput.setAttribute("type", type);
        
        this.classList.toggle("bx-show");
        this.classList.toggle("bx-hide");
    });

    // Toast Alert
    document.addEventListener('DOMContentLoaded', function() {
        const toast = document.getElementById('toast-alert');
        const messageEl = document.getElementById('toast-message');
        const closeBtn = document.getElementById('toast-close');

        function showToast(message, type = 'error') {
            if (!toast || !messageEl || !message) return;
            
            messageEl.textContent = message;
            toast.className = `toast-alert ${type}`;
            toast.style.display = 'flex';
            
            setTimeout(() => {
                toast.classList.add('show');
                toast.focus(); 
            }, 10);
            
            const autoHideTimer = setTimeout(() => {
                hideToast();
            }, 3000);
            
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
       
        <?php if (isset($_SESSION['error_message'])): ?>
            showToast('<?php echo $_SESSION['error_message']; ?>');
            <?php unset($_SESSION['error_message']); ?>
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