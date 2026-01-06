<?php
$url = "https://jnpxwcmshukhkxdzicwv.supabase.co/rest/v1/admins?username=eq.admin";
$key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImpucHh3Y21zaHVraGt4ZHppY3d2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjcyMTQ0NzEsImV4cCI6MjA4Mjc5MDQ3MX0.C7ZXSR7t15qGShP8FhHlw0r7pLMYSDrmrR7ubb7ofOA";

$headers = [
    'apikey: ' . $key,
    'Authorization: Bearer ' . $key,
    'Content-Type: application/json',
    'Prefer: return=representation'
];

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'header' => implode("\r\n", $headers),
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);

echo "Testing file_get_contents...\n";
echo "URL: $url\n\n";

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    $error = error_get_last();
    echo "Error: " . ($error['message'] ?? 'Unknown') . "\n";
} else {
    echo "Success! Response length: " . strlen($response) . "\n";
    echo "Response: " . substr($response, 0, 200) . "\n";
}

if (isset($http_response_header)) {
    echo "\nHTTP Headers:\n";
    foreach ($http_response_header as $header) {
        echo "  $header\n";
    }
}
