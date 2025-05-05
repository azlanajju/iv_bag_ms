<?php
require_once 'config.php';

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$deviceId = isset($data['deviceId']) ? sanitizeInput($data['deviceId']) : null;

if (!$deviceId) {
    http_response_code(400);
    echo json_encode(['error' => 'Device ID is required']);
    exit;
}

try {
    $conn = getDBConnection();

    // Get current weight from the device
    $stmt = $conn->prepare("SELECT IPAddress FROM IV_Device_Info WHERE DeviceID = :deviceId");
    $stmt->execute([':deviceId' => $deviceId]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$device) {
        throw new Exception('Device not found');
    }

    // Fetch current weight from device
    $response = file_get_contents("http://{$device['IPAddress']}/data");
    $weightData = json_decode($response, true);

    if (!$weightData || !isset($weightData['weight'])) {
        throw new Exception('Could not fetch current weight from device');
    }

    // Update the device with new start time and initial weight
    $stmt = $conn->prepare("
        UPDATE IV_Device_Info 
        SET startTime = CURRENT_TIMESTAMP,
            InitialWeight = :currentWeight,
            LastUpdated = CURRENT_TIMESTAMP
        WHERE DeviceID = :deviceId
    ");

    $stmt->execute([
        ':currentWeight' => $weightData['weight'],
        ':deviceId' => $deviceId
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
