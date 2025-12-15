<?php
session_start();
header("Content-Type: application/json");
require_once "connection.php";

// Check if user is logged in (either as user or trainer)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['trainer_id'])) {
    echo json_encode([]);
    exit;
}

$trainer_id = intval($_GET['trainer_id'] ?? 0);

if ($trainer_id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT date, time FROM bookings WHERE trainer_id = ?"
);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();

$result = $stmt->get_result();
$bookings = [];

while ($row = $result->fetch_assoc()) {
    $bookings[] = [
        "date" => $row["date"],
        "time" => $row["time"]
    ];
}

echo json_encode($bookings);
?>