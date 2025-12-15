<?php
session_start();
header('Content-Type: application/json');
require_once "connection.php";

// This will now work because loginpage.php sets $_SESSION['user_id']
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "msg" => "Session expired",
        "redirect" => "Loginpage.php"
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "msg" => "Invalid request method"]);
    exit;
}

$trainer_id   = intval($_POST['trainer_id'] ?? 0);
$date         = $_POST['date'] ?? null;
$time         = $_POST['time'] ?? null;
$sessions     = intval($_POST['sessions'] ?? 1);
$total_price  = floatval($_POST['total_price'] ?? 0);

if (!$trainer_id || !$date || !$time) {
    echo json_encode(["status" => "error", "msg" => "Missing required fields"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// slot check
$check = $conn->prepare(
    "SELECT 1 FROM bookings WHERE trainer_id = ? AND date = ? AND time = ? LIMIT 1"
);
$check->bind_param("iss", $trainer_id, $date, $time);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["status" => "error", "msg" => "Slot already taken"]);
    exit;
}

// insert
$stmt = $conn->prepare(
    "INSERT INTO bookings (user_id, trainer_id, session_count, total_price, date, time)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("iiidss", $user_id, $trainer_id, $sessions, $total_price, $date, $time);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "booking_id" => $stmt->insert_id
    ]);
} else {
    echo json_encode(["status" => "error", "msg" => $stmt->error]);
}
