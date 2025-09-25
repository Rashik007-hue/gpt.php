<?php
$OIVSCode = [
    "apiBase" => "https://oi-vscode-server-2.onrender.com/v1",
    "model" => "gpt-4o-mini-2024-07-18",
    "modelAliases" => [
        "gpt-4o-mini" => "gpt-4o-mini-2024-07-18"
    ]
];

function resolveModelName($inputModel, $OIVSCode) {
    $key = strtolower($inputModel ?? '');
    return $OIVSCode['modelAliases'][$key] ?? $OIVSCode['model'];
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = $_GET['q'] ?? '';
    $model = $_GET['model'] ?? null;
    $max_tokens = $_GET['max_tokens'] ?? 2000;
    $temperature = $_GET['temperature'] ?? 0.7;

    if (empty($query)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required query (?q=your question)']);
        exit;
    }

    $resolvedModel = resolveModelName($model, $OIVSCode);
    $messages = [[ "role" => "user", "content" => $query ]];

    $payload = json_encode([
        "model" => $resolvedModel,
        "messages" => $messages,
        "stream" => false,
        "max_tokens" => (int)$max_tokens,
        "temperature" => (float)$temperature
    ]);

    $ch = curl_init("{$OIVSCode['apiBase']}/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10 // Speed up with timeout
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        $reply = $data['choices'][0]['message']['content'] ?? null;
        echo json_encode([
            "status" => "success",
            "reply" => $reply
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Request failed', 'details' => $error]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Only GET method is allowed']);
}
