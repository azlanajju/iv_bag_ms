<?php
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php'; // Twilio SDK

use Twilio\Rest\Client;

// Function to send SMS using Twilio
function sendSMS($phoneNumber, $message)
{
    // Your Twilio credentials
    $accountSid = 'ACd9889979abc04d9714261e6a4c757f15';
    $authToken = '61e8be9c9e473f0084e20bfc1aad2523';
    $twilioNumber = '+916361557581'; // Your Twilio phone number in E.164 format

    try {
        // Initialize Twilio client
        $client = new Client($accountSid, $authToken);

        // Format phone number to E.164 format if needed
        if (!preg_match('/^\+/', $phoneNumber)) {
            $phoneNumber = '+' . $phoneNumber;
        }

        // Send SMS
        $message = $client->messages->create(
            $phoneNumber, // To
            [
                'from' => $twilioNumber,
                'body' => $message
            ]
        );

        return [
            'success' => true,
            'message_sid' => $message->sid,
            'status' => $message->status
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
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

        // Send SMS using Twilio
        $result = sendSMS($device['NurseCallNumber'], $message);

        if (!$result['success']) {
            throw new Exception('Failed to send SMS: ' . $result['error']);
        }

        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'SMS notification sent successfully',
            'twilio' => [
                'message_sid' => $result['message_sid'],
                'status' => $result['status']
            ],
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
