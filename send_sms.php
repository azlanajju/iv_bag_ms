<?php
require_once 'config.php';

// Function to send SMS using a hypothetical SMS gateway
function sendSMS($phoneNumber, $message)
{
    // Replace these with your actual SMS gateway credentials
    $apiKey = 'YOUR_SMS_GATEWAY_API_KEY';
    $senderId = 'YOUR_SENDER_ID';

    // Example using a hypothetical SMS API
    $url = "https://api.smsgateway.com/send";
    $data = [
        'api_key' => $apiKey,
        'sender_id' => $senderId,
        'to' => $phoneNumber,
        'message' => $message
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get POST data
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['deviceId']) || !isset($data['status'])) {
            throw new Exception('Missing required parameters');
        }

        $deviceId = $data['deviceId'];
        $status = $data['status'];

        // Get database connection
        $conn = getDBConnection();

        // Get device information
        $stmt = $conn->prepare("SELECT DeviceName, NurseCallNumber FROM IV_Device_Info WHERE DeviceID = ?");
        $stmt->execute([$deviceId]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$device) {
            throw new Exception('Device not found');
        }

        if (empty($device['NurseCallNumber'])) {
            throw new Exception('Nurse phone number not configured');
        }

        // Prepare message based on status
        $message = '';
        if ($status === 'half') {
            $message = "WARNING: IV Bag {$device['DeviceName']} (ID: $deviceId) has reached 50% of its initial weight. Please check the device.";
        } elseif ($status === 'empty') {
            $message = "CRITICAL: IV Bag {$device['DeviceName']} (ID: $deviceId) is now empty. Immediate attention required!";
        } else {
            throw new Exception('Invalid status');
        }

        // Send SMS
        $result = sendSMS($device['NurseCallNumber'], $message);

        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'SMS notification sent successfully',
            'device' => [
                'id' => $deviceId,
                'name' => $device['DeviceName'],
                'status' => $status
            ]
        ]);
    } catch (Exception $e) {
        // Return error response
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    // Return method not allowed
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}
