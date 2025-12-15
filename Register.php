<?php
session_start();
// NOTE: Assuming connection.php and the database schema are correct.
include "connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    // 💡 UPDATED: Capture First Name and Last Name separately
    $firstname = trim($_POST["firstname"]);
    $lastname = trim($_POST["lastname"]);
    $fullname = $firstname . " " . $lastname; // Combine for database storage
    
    $email = trim($_POST["email"]);
    $password = $_POST['password'];

    // --- Server-side Password Validation Check ---
    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $password)) {
        $_SESSION['error_message'] = 'Password must be at least 8 characters long and include one uppercase letter, one lowercase letter, and one number.';
        header("Location: Register.php");
        exit();
    }

    // Collect additional form data
    $age = intval($_POST['age']);
    $gender = $_POST['gender'];
    $focus = $_POST['focus'];
    $cp_no = trim($_POST['cp_no']);
    $goal = $_POST['goal'];
    $activity = $_POST['activity'];
    $training_days = isset($_POST['training_days']) ? implode(", ", $_POST['training_days']) : '';
    $weight_kg = floatval($_POST['weight']);
    $height_cm = floatval($_POST['height']);

    // --- Server-side Contact Number Validation Check ---
    if (!preg_match("/^\d{11}$/", $cp_no)) {
        $_SESSION['error_message'] = 'Contact number must be exactly 11 digits.';
        header("Location: Register.php");
        exit();
    }

    // Compute BMI (height in meters)
    $bmi = ($height_cm > 0) ? round($weight_kg / (($height_cm / 100) ** 2), 2) : 0;

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check for existing email
    $checkStmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        $_SESSION['error_message'] = 'This email is already registered.';
        header("Location: Register.php");
        exit();
    }
    $checkStmt->close();

    // Insert new user
    // The database column name is still 'fullname', so we pass the combined name.
    $stmt = $conn->prepare("
        INSERT INTO users (
            fullname, email, password,
            age, gender, focus, goal,
            activity, training_days, 
            weight_kg, height_cm, bmi, cp_no
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssisssssddds",  // 13 types: s=string, i=int, d=double
        $fullname, // Pass the combined name
        $email,
        $hashedPassword,
        $age,
        $gender,
        $focus,
        $goal,
        $activity,
        $training_days,
        $weight_kg,
        $height_cm,
        $bmi,
        $cp_no
    );


    if ($stmt->execute()) {
        // ✅ Set session for logged-in client
        $client_id = $stmt->insert_id;
        $_SESSION['client_id'] = $client_id;
        $_SESSION['client_name'] = $fullname;

        $stmt->close();
        $conn->close();

        $_SESSION['success_message'] = 'Registration successful!';
        header("Location: MembershipPayment.php");
        exit();
    } else {
        $errorMsg = 'Error during registration: ' . $stmt->error;
        $stmt->close();
        $conn->close();
        $_SESSION['error_message'] = $errorMsg;
        header("Location: Register.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title>Member Registration</title>
  <link rel="stylesheet" href="Registerstyle.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Additional styles matching Loginpage.php */
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
    
    .register-container {
        animation: fadeInUp 0.6s ease forwards;
        opacity: 0;
    }
    
    /* Smooth scrolling */
    html {
        scroll-behavior: smooth;
    }
    
    /* Mobile navbar styles - matching Loginpage.php */
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
    
    /* Desktop responsive width */
    @media (min-width: 1024px) {
        .wrapper {
            width: 800px !important;
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
        .btn, .register-link a {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    }
  </style>
</head>

<body>

<!-- Mobile Top Navbar (matches Loginpage.php) -->
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

<div class="wrapper register-container">
  <form method="POST" id="registerForm">
    <a href="Nonmember.php" class="back-icon"><i class='bx bx-arrow-back'></i></a>
    <h1 class="text-3xl font-bold mb-4 text-center" style="color: #000000;">Membership Form</h1>
    <p class="text-center text-gray-600 mb-8 px-4">Want to become a member? Please fill out the form to complete the registration.</p>

    <!-- Error/Warning Messages -->
    <div id="passwordWarning" class="alert-box error-alert hidden">
        Password must be at least 8 characters long and include one uppercase letter, one lowercase letter, and one number.
    </div>
    
    <div id="contactWarning" class="alert-box error-alert hidden">
        Contact number must be exactly 11 digits.
    </div>

    <div id="step1">
      <!-- Responsive grid for desktop -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 💡 UPDATED: Separated Full Name into First Name and Last Name -->
        <div class="input-box">
          <input type="text" name="firstname" id="firstname" placeholder="First Name" required value="<?php echo isset($firstname) ? htmlspecialchars($firstname) : ''; ?>" style="background: #f8f8f8; border: 2px solid #e5e5e5;" />
          <i class='bx bxs-user' style="color: #000000;"></i>
        </div>
        <div class="input-box">
          <input type="text" name="lastname" id="lastname" placeholder="Last Name" required value="<?php echo isset($lastname) ? htmlspecialchars($lastname) : ''; ?>" style="background: #f8f8f8; border: 2px solid #e5e5e5;" />
          <i class='bx bxs-user' style="color: #000000;"></i>
        </div>

        <div class="input-box" id="contactBox">
          <input type="tel" name="cp_no" id="cp_no" placeholder="Contact Number (11 digits)" required 
                value="<?php echo isset($cp_no) ? htmlspecialchars($cp_no) : ''; ?>"
                maxlength="11"
                pattern="\d{11}"
                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);"
                style="background: #f8f8f8; border: 2px solid #e5e5e5;" /> 
          <i class='bx bxs-phone' style="color: #000000;"></i>
        </div>

        <div class="input-box">
          <input type="email" name="email" id="email" placeholder="Email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" style="background: #f8f8f8; border: 2px solid #e5e5e5;" />
          <i class='bx bxs-envelope' style="color: #000000;"></i>
        </div>

        <!-- Password and Confirm Password fields -->
        <div class="input-box" id="passwordBox"> 
          <input type="password" name="password" id="password" placeholder="Password" required style="background: #f8f8f8; border: 2px solid #e5e5e5;" />
          <i class='bx bx-show' id="togglePassword" style="cursor: pointer; color: #000000;"></i>
        </div>

        <div class="input-box" id="repasswordBox">
          <input type="password" id="repassword" placeholder="Confirm Password" required style="background: #f8f8f8; border: 2px solid #e5e5e5;" />
          <i class='bx bx-show' id="tgPassword" style="cursor: pointer; color: #000000;"></i>
        </div>

        <!-- Verification Code - Full width -->
        <div class="input-box md:col-span-2">
          <input type="text" id="verification_code" placeholder="Enter verification code" required style="background: #f8f8f8; border: 2px solid #e5e5e5;" />
          <i class='bx bxs-check-shield' style="color: #000000;"></i>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-4 mt-6">
        <button type="button" class="btn flex-1" onclick="sendVerification()" style="background-color: #000000; color: #FFD700; font-weight: 700;">Send Verification Code</button>
        <button type="button" class="btn flex-1" onclick="showStep2()" style="background-color: #000000; color: #FFD700; font-weight: 700;">Next</button>
      </div>
    </div>

    <div id="step2" class="hidden">
      <!-- Responsive grid for step 2 -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Gender -->
        <div class="form-card">
          <div class="group-label">Gender:</div>
          <div class="options">
            <span><input type="radio" name="gender" value="Male" required> Male</span>
            <span><input type="radio" name="gender" value="Female" required> Female</span>
          </div>
        </div>

        <!-- Focus Area -->
        <div class="form-card">
          <div class="group-label">Select Focus Area:</div>
          <div class="options">
            <span><input type="radio" name="focus" value="Arms" required> Arms</span>
            <span><input type="radio" name="focus" value="Chest" required> Chest</span>
            <span><input type="radio" name="focus" value="Legs" required> Legs</span>
            <span><input type="radio" name="focus" value="Full Body" required> Full Body</span>
          </div>
        </div>

        <!-- Main Goal -->
        <div class="form-card">
          <div class="group-label">Main Goal:</div>
          <div class="options">
            <span><input type="radio" name="goal" value="Lose Weight" required> Lose Weight</span>
            <span><input type="radio" name="goal" value="Gain Muscle" required> Gain Muscle</span>
            <span><input type="radio" name="goal" value="Stay Fit" required> Stay Fit</span>
          </div>
        </div>

        <!-- Activity Level -->
        <div class="form-card">
          <div class="group-label">Activity Level:</div>
          <div class="options">
            <span><input type="radio" name="activity" value="Low" required> Low</span>
            <span><input type="radio" name="activity" value="Moderate" required> Moderate</span>
            <span><input type="radio" name="activity" value="High" required> High</span>
          </div>
        </div>

        <!-- Training Days - Full width -->
        <div class="form-card md:col-span-2">
          <div class="group-label">Training Days per Week:</div>
          <div class="options grid grid-cols-2 sm:grid-cols-3 md:grid-cols-7 gap-2">
            <span><input type="checkbox" name="training_days[]" value="Monday"> Monday</span>
            <span><input type="checkbox" name="training_days[]" value="Tuesday"> Tuesday</span>
            <span><input type="checkbox" name="training_days[]" value="Wednesday"> Wednesday</span>
            <span><input type="checkbox" name="training_days[]" value="Thursday"> Thursday</span>
            <span><input type="checkbox" name="training_days[]" value="Friday"> Friday</span>
            <span><input type="checkbox" name="training_days[]" value="Saturday"> Saturday</span>
            <span><input type="checkbox" name="training_days[]" value="Sunday"> Sunday</span>
          </div>
        </div>

        <!-- BMI Info - Full width -->
        <div class="form-card md:col-span-2">
          <div class="group-label">BMI Info:</div>
          <div class="options grid grid-cols-1 sm:grid-cols-3 gap-4">
            <span><input type="number" name="age" placeholder="Age" required style="background: #f8f8f8; border: 2px solid #e5e5e5;"></span>
            <span><input type="number" name="weight" placeholder="Weight (kg)" step="0.1" required style="background: #f8f8f8; border: 2px solid #e5e5e5;"></span>
            <span><input type="number" name="height" placeholder="Height (cm)" step="0.1" required style="background: #f8f8f8; border: 2px solid #e5e5e5;"></span>
          </div>
        </div>
      </div>

      <button type="submit" class="btn mt-8" name="register" style="background-color: #000000; color: #FFD700; font-weight: 700;">Select Membership Plan</button>
    </div>

    <div class="register-link mt-6">
      <p style="color: #666;">Already a member? <a href="Loginpage.php" style="color: #000000; font-weight: 700;">Login here</a></p>
    </div>
  </form>
</div>

<!-- ✅ Toast Container -->
<div id="toast-alert" class="toast-alert" role="alert" aria-live="polite" style="display:none;">
  <span id="toast-message"></span>
  <button id="toast-close" class="toast-close" aria-label="Close alert">&times;</button>
</div>

<script>
function sendVerification() {
  const email = document.getElementById("email").value.trim();
  if (!email) {
    showToast("Please enter your email first.");
    return;
  }

  // Show loading state
  showToast("Sending verification code...", "info");
  
  fetch("send_verification.php", {
    method: "POST",
    headers: { 
      "Content-Type": "application/x-www-form-urlencoded",
      "Accept": "text/plain"
    },
    body: "email=" + encodeURIComponent(email)
  })
  .then(response => response.text())
  .then(result => {
    if (result.trim() === "sent") {
      showToast("Verification code sent.", "success");
    } else if (result.trim() === "failed") {
      showToast("Failed to send verification code. Try again.");
    } else if (result.trim() === "no_email") {
      showToast("Please enter a valid email address.");
    } else {
      showToast("Failed to send verification code. Try again.");
    }
  })
  .catch(() => showToast("Failed to send verification code. Try again."));
}


function showStep2() {
  // 💡 UPDATED: Check both first and last name
  const firstname = document.getElementById("firstname").value.trim();
  const lastname = document.getElementById("lastname").value.trim();
  const cp_no = document.getElementById("cp_no").value.trim(); 
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;
  const repassword = document.getElementById("repassword").value;
  const enteredCode = document.getElementById("verification_code").value.trim();
  
  // Warnings and boxes
  const passwordWarning = document.getElementById("passwordWarning");
  const contactWarning = document.getElementById("contactWarning"); 
  const passwordBox = document.getElementById("passwordBox");
  const repasswordBox = document.getElementById("repasswordBox");
  const contactBox = document.getElementById("contactBox"); 

  // 1. Hide all warnings and clear previous errors
  passwordWarning.classList.add("hidden"); 
  contactWarning.classList.add("hidden"); 
  passwordBox.classList.remove("input-error");
  repasswordBox.classList.remove("input-error");
  contactBox.classList.remove("input-error"); 

  // 2. Check for required fields (including new first/last name)
  if (!firstname || !lastname || !cp_no || !email || !password || !repassword || !enteredCode) {
    showToast("Please complete all fields.");
    return;
  }
  
  // 3. Contact Number Validation
  if (cp_no.length !== 11 || !/^\d{11}$/.test(cp_no)) {
    contactWarning.classList.remove("hidden");
    contactBox.classList.add("input-error");
    showToast("Contact number must be exactly 11 digits.");
    
    setTimeout(() => {
        contactWarning.classList.add("hidden"); 
        contactBox.classList.remove("input-error");
    }, 4500);
    return;
  }

  // 4. Check Password Match
  if (password !== repassword) {
    showToast("Passwords do not match.");
    passwordBox.classList.add("input-error"); 
    repasswordBox.classList.add("input-error");
    return;
  }

  const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

  // 5. Check Password Strength
  if (!passwordPattern.test(password)) {
    passwordWarning.classList.remove("hidden"); 
    passwordBox.classList.add("input-error");
    repasswordBox.classList.add("input-error");

    setTimeout(() => {
        passwordWarning.classList.add("hidden"); 
        passwordBox.classList.remove("input-error");
        repasswordBox.classList.remove("input-error");
    }, 4500); 

    return;
  }
  
  // If all client-side checks pass, proceed to verification
  
  // Show loading state
  showToast("Verifying code...", "info");
  
  fetch("verify_code.php", {
    method: "POST",
    headers: { 
      "Content-Type": "application/x-www-form-urlencoded",
      "Accept": "text/plain"
    },
    body: "code=" + encodeURIComponent(enteredCode)
  })
  .then(res => res.text())
  .then(data => {
    console.log("Verification response:", data); // Debug log
    if (data.trim() === "verified") {
      document.getElementById("step1").classList.add("hidden");
      document.getElementById("step2").classList.remove("hidden");
      showToast("Verification successful!", "success");
    } else if (data.trim() === "expired") {
      showToast("Verification code has expired. Please request a new one.");
    } else if (data.trim() === "invalid") {
      showToast("Invalid verification code. Please try again.");
    } else if (data.trim() === "no_code") {
      showToast("No verification code found. Please send a new code.");
    } else if (data.trim() === "no_post") {
      showToast("Verification failed. Please try again.");
    } else {
      showToast("Verification failed. Please try again.");
    }
  })
  .catch((error) => {
    console.error("Verification error:", error);
    showToast("Verification failed. Try again.");
  });
}

// Password Toggles
document.getElementById("togglePassword").addEventListener("click", function() {
  const passwordInput = document.getElementById("password");
  const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
  passwordInput.setAttribute("type", type);
  this.classList.toggle("bx-show");
  this.classList.toggle("bx-hide");
});

document.getElementById("tgPassword").addEventListener("click", function() {
  const passwordInput = document.getElementById("repassword");
  const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
  passwordInput.setAttribute("type", type);
  this.classList.toggle("bx-show");
  this.classList.toggle("bx-hide");
});

// Mobile responsiveness: Prevent zoom on input focus
document.addEventListener('touchstart', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
        document.body.style.zoom = "100%";
    }
}, { passive: true });
</script>

<!-- ✅ Toast Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const toast = document.getElementById('toast-alert');
  const messageEl = document.getElementById('toast-message');
  const closeBtn = document.getElementById('toast-close');

  window.showToast = function(message, type = 'error') {
    if (!toast || !messageEl || !message) return;
    messageEl.textContent = message;
    toast.className = `toast-alert ${type}`;
    toast.style.display = 'flex';
    setTimeout(() => toast.classList.add('show'), 10);

    const autoHideTimer = setTimeout(() => hideToast(), 5000);
    function hideToast() {
      toast.classList.remove('show');
      setTimeout(() => {
        toast.style.display = 'none';
        clearTimeout(autoHideTimer);
      }, 300);
    }

    closeBtn.onclick = hideToast;
    toast.onclick = (e) => { if (e.target === toast) hideToast(); };
  };

  <?php if (isset($_SESSION['error_message'])): ?>
    showToast('<?php echo str_replace("'", "\'", $_SESSION['error_message']); ?>');
    <?php unset($_SESSION['error_message']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['success_message'])): ?>
    showToast('<?php echo str_replace("'", "\'", $_SESSION['success_message']); ?>', 'success');
    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>
});
</script>

</body>
</html>