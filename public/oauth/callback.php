<?php

$client_id = 'dc037eeb-b0a5-46f7-ab0d-1bc3c06f808c';
$client_secret = 'eRU7NC86FigrOOlsKsETTbOQyeN0sxD9rGA0GcVqahxmPFlPelvYzoQsDmfdzQ6t';
$redirect_uri = 'https://6636-38-180-226-59.ngrok-free.app/oauth/callback.php';

if (!isset($_GET['code'])) {
    echo "Нет параметра code в URL.";
    exit;
}

$code = $_GET['code'];

$data = [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://nikitalars1.amocrm.ru/oauth2/access_token',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

file_put_contents(__DIR__ . '/tokens.json', $response);

header('Content-Type: application/json');
echo "HTTP code: $http_code\n\n";
echo $response;