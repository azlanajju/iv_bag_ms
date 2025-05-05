<?php
session_start();

if (!isset($_SESSION['weight'])) {
    $_SESSION['weight'] = 0.50; // initial weight in kg
}

header('Content-Type: application/json');

// Simulate weight decrease
$decreaseRate = 0.01; // decrease per second (customize as needed)
$_SESSION['weight'] -= $decreaseRate;

if ($_SESSION['weight'] < 0) {
    $_SESSION['weight'] = 0.00;
}

echo json_encode(['weight' => round($_SESSION['weight'], 2)]);
?>
