<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file for debugging
$logFile = 'twilio_debug.log';

function writeLog($message)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['to'] ?? '';
    $message = $_POST['message'] ?? '';

    writeLog("Received request - To: $to, Message: $message");

    // Validate inputs
    if (empty($to) || empty($message)) {
        writeLog("Error: Empty phone number or message");
        echo json_encode([
            'error_code' => 'INVALID_INPUT',
            'error_message' => 'Phone number and message are required'
        ]);
        exit;
    }

    // Your Twilio Account SID and Auth Token
    $account_sid = 'ACd0562d762f90fb280300df0fa07781c1';
    $auth_token = 'e1898158aa4aab06395cdb295cea9222';
    $from_number = '+16516503374';

    $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";

    // Format phone number to E.164 format
    $to = preg_replace('/[^0-9+]/', '', $to);
    if (!str_starts_with($to, '+')) {
        $to = '+' . $to;
    }

    $data = array(
        'To' => $to,
        'From' => $from_number,
        'Body' => $message
    );

    writeLog("Sending request to Twilio - URL: $url");
    writeLog("Request data: " . json_encode($data));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Set authentication header properly
    $auth_header = 'Basic ' . base64_encode($account_sid . ':' . $auth_token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
        'Authorization: ' . $auth_header
    ]);

    writeLog("Auth header: " . $auth_header);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    writeLog("Response code: $http_code");
    writeLog("Response body: $response");

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        writeLog("Curl error: $error");
        echo json_encode([
            'error_code' => 'CURL_ERROR',
            'error_message' => $error
        ]);
    } else {
        $response_data = json_decode($response, true);
        if ($http_code >= 200 && $http_code < 300) {
            writeLog("SMS sent successfully");
            echo $response;
        } else {
            writeLog("Twilio API error: " . $response);
            echo json_encode([
                'error_code' => $response_data['code'] ?? 'UNKNOWN_ERROR',
                'error_message' => $response_data['message'] ?? 'Failed to send SMS'
            ]);
        }
    }

    curl_close($ch);
} else {
    writeLog("Invalid method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode([
        'error_code' => 'INVALID_METHOD',
        'error_message' => 'Only POST method is allowed'
    ]);
}
