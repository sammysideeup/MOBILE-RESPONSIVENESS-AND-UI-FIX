<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['trainer_id'])) {
    header("Location: Loginpage.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Booking ID is missing.");
}

$booking_id = $_GET['id'];

// Fetch booking including date and time
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Booking not found.");
}

// Use the actual date & time from database
$currentDate = $booking['date']; // should be in 'YYYY-MM-DD' format
$currentTime = $booking['time']; // should be in 'HH:MM' format

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_count = $_POST['session_count'];
    $total_price = $_POST['total_price'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    $update = $conn->prepare("UPDATE bookings SET session_count=?, total_price=?, date=?, time=? WHERE id=?");
    $update->bind_param("idssi", $session_count, $total_price, $date, $time, $booking_id);

    if ($update->execute()) {
        header("Location: update_success.php");
        exit();
    } else {
        echo "Update failed: " . $update->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Booking</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #000000, #333333);
            color: #FFFFFF;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background: #FFFFFF;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
            width: 350px;
            text-align: center;
            border: 3px solid #FFD700;
        }
        h2 {
            margin-bottom: 25px;
            color: #000000;
            font-family: 'Arial Black', sans-serif;
            border-bottom: 2px solid #FFD700;
            padding-bottom: 10px;
        }
        label {
            display: block;
            text-align: left;
            margin-bottom: 6px;
            font-weight: bold;
            color: #000000;
        }
        input {
            padding: 10px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #CCCCCC;
            margin-bottom: 15px;
            outline: none;
            font-size: 15px;
            background: #FFFFFF;
            color: #000000;
            box-sizing: border-box;
        }
        input[type="date"], input[type="time"], input[type="number"] {
            background: #FFFFFF;
            color: #000000;
            border: 1px solid #CCCCCC;
        }
        input:focus {
            border-color: #FFD700;
            box-shadow: 0 0 6px #FFD700;
        }
        button {
            padding: 12px 20px;
            background: #000000;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: 0.3s;
            margin-top: 10px;
        }
        button:hover {
            background: #FFD700;
            color: #000000;
            transform: translateY(-2px);
        }
        button:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Update Booking</h2>
        <form method="POST">
            <label>Session Count:</label>
            <input type="number" name="session_count" value="<?= htmlspecialchars($booking['session_count']) ?>" required>

            <label>Total Price:</label>
            <input type="number" name="total_price" step="0.01" value="<?= htmlspecialchars($booking['total_price']) ?>" required>

            <label>New Schedule (Date):</label>
            <input type="date" name="date" value="<?= htmlspecialchars($currentDate) ?>" required>

            <label>New Time:</label>
            <input type="time" name="time" value="<?= htmlspecialchars($currentTime) ?>" required>

            <button type="submit">Update Booking</button>
        </form>
    </div>
</body>
</html>