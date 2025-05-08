<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['to'] ?? '';
    $message = $_POST['message'] ?? '';

    $account_sid = 'ACd0562d762f90fb280300df0fa07781c1';
    $auth_token = '92cdab2925fbf46130c4771d0a74acdc';
    $from_number = '+16516503374';

    $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";

    $data = array(
        'To' => $to,
        'From' => $from_number,
        'Body' => $message
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$account_sid}:{$auth_token}");

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo json_encode(['error' => curl_error($ch)]);
    } else {
        echo $response;
    }

    curl_close($ch);
} else {
    echo json_encode(['error' => 'Method not allowed']);
}
