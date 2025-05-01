<?php

$tokens = json_decode(file_get_contents(__DIR__ . '/tokens.json'), true);
$access_token = $tokens['access_token'] ?? null;

if (!$access_token) {
    http_response_code(401);
    echo 'Access token not found.';
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    parse_str($raw, $data);
}


file_put_contents(__DIR__ . '/webhook_log.json', json_encode($data, JSON_PRETTY_PRINT));


$entityTypes = ['leads', 'contacts'];

foreach ($entityTypes as $type) {
    foreach (['add', 'update'] as $action) {
        if (!empty($data[$type][$action])) {
            foreach ($data[$type][$action] as $item) {
                $entityId = $item['id'];
                $responsible = $item['responsible_user_id'] ?? 'неизвестен';
                $updated_at = date('Y-m-d H:i:s', $item['updated_at'] ?? time());
                if ($action === 'add') {
                    $text = ucfirst($type) . " добавлен. ID: $entityId, ответственный: $responsible, время: $updated_at";
                } else {
                    $changed = [];
                    foreach ($item['custom_fields'] ?? [] as $field) {
                        $field_name = $field['name'] ?? 'Поле';
                        $new_value = $field['values'][0]['value'] ?? '(значение недоступно)';
                        $changed[] = "$field_name: $new_value";
                    }
                    $text = ucfirst($type) . " изменён. ID: $entityId, поля: " . implode(', ', $changed) . ", время: $updated_at";
                }


                $note = [
                    'note_type' => 'common',
                    'params' => ['text' => $text],
                ];

                $url = "https://nikitalars1.amocrm.ru/api/v4/$type/$entityId/notes";

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode([$note]),
                    CURLOPT_HTTPHEADER => [
                        "Authorization: Bearer $access_token",
                        "Content-Type: application/json"
                    ],
                ]);

                $response = curl_exec($curl);
                $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);


                file_put_contents(__DIR__ . '/webhook_response.log', "[$http_code] $response\n", FILE_APPEND);
            }
        }
    }
}

http_response_code(200);
echo 'Webhook обработан';