<?php
/**
 * 전체 기능 테스트 스크립트
 * 로그인부터 모든 API 엔드포인트를 테스트
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== 전체 기능 테스트 ===\n\n";

// 테스트할 Supabase 설정
$supabaseUrl = 'https://jnpxwcmshukhkxdzicwv.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImpucHh3Y21zaHVraGt4ZHppY3d2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjcyMTQ0NzEsImV4cCI6MjA4Mjc5MDQ3MX0.C7ZXSR7t15qGShP8FhHlw0r7pLMYSDrmrR7ubb7ofOA';

$results = [];

/**
 * HTTP 응답 헤더에서 상태 코드 추출 (PHP 8.4+ 호환)
 */
function getHttpStatusCode($response) {
    if ($response === false) {
        return 500;
    }

    // PHP 8.4+: http_get_last_response_headers() 사용
    if (function_exists('http_get_last_response_headers')) {
        $headers = http_get_last_response_headers();
    } else {
        // PHP 8.3 이하: $http_response_header 사용
        global $http_response_header;
        $headers = $http_response_header ?? [];
    }

    if (!empty($headers) && isset($headers[0])) {
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $matches)) {
            return (int)$matches[1];
        }
    }

    return 200;
}

function testSupabaseApi($endpoint, $method = 'GET', $data = null) {
    global $supabaseUrl, $supabaseKey;

    $url = $supabaseUrl . '/rest/v1/' . $endpoint;

    $headers = [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];

    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $data ? json_encode($data) : null,
            'timeout' => 10,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    $httpCode = getHttpStatusCode($response);

    return [
        'status' => $httpCode,
        'response' => $response,
        'success' => ($httpCode >= 200 && $httpCode < 300)
    ];
}

// 1. admins 테이블 테스트 (로그인용)
echo "1. admins 테이블 테스트...\n";
$result = testSupabaseApi('admins?username=eq.admin');
if ($result['success']) {
    $data = json_decode($result['response'], true);
    if (!empty($data)) {
        echo "   [OK] admin 사용자 존재 확인\n";
        $results['admins'] = 'SUCCESS';
    } else {
        echo "   [FAIL] admin 사용자 없음\n";
        $results['admins'] = 'NO_DATA';
    }
} else {
    echo "   [FAIL] HTTP {$result['status']}\n";
    echo "   응답: " . substr($result['response'] ?? '', 0, 200) . "\n";
    $results['admins'] = 'FAILED';
}

// 2. orders 테이블 테스트
echo "\n2. orders 테이블 테스트...\n";
$result = testSupabaseApi('orders?limit=5');
if ($result['success']) {
    $data = json_decode($result['response'], true);
    echo "   [OK] orders 조회 성공 (레코드: " . count($data) . "개)\n";
    $results['orders'] = 'SUCCESS';
} else {
    echo "   [FAIL] HTTP {$result['status']}\n";
    $results['orders'] = 'FAILED';
}

// 3. templates 테이블 테스트
echo "\n3. templates 테이블 테스트...\n";
$result = testSupabaseApi('templates?limit=5');
if ($result['success']) {
    $data = json_decode($result['response'], true);
    echo "   [OK] templates 조회 성공 (레코드: " . count($data) . "개)\n";
    $results['templates'] = 'SUCCESS';
} else {
    echo "   [FAIL] HTTP {$result['status']}\n";
    $results['templates'] = 'FAILED';
}

// 4. receipts 테이블 테스트
echo "\n4. receipts 테이블 테스트...\n";
$result = testSupabaseApi('receipts?limit=5');
if ($result['success']) {
    $data = json_decode($result['response'], true);
    echo "   [OK] receipts 조회 성공 (레코드: " . count($data) . "개)\n";
    $results['receipts'] = 'SUCCESS';
} else {
    echo "   [FAIL] HTTP {$result['status']}\n";
    $results['receipts'] = 'FAILED';
}

// 5. list_items 테이블 테스트
echo "\n5. list_items 테이블 테스트...\n";
$result = testSupabaseApi('list_items?limit=5');
if ($result['success']) {
    $data = json_decode($result['response'], true);
    echo "   [OK] list_items 조회 성공 (레코드: " . count($data) . "개)\n";
    $results['list_items'] = 'SUCCESS';
} else {
    echo "   [FAIL] HTTP {$result['status']}\n";
    $results['list_items'] = 'FAILED';
}

// 6. receipts INSERT 테스트 (직접 REST API)
echo "\n6. receipts INSERT 테스트 (직접 REST API)...\n";
$testReceipt = [
    'receipt_number' => 'TEST-' . date('Ymd') . '-' . rand(1000, 9999),
    'orderer_name' => '테스트 주문자',
    'recipient_name' => '테스트 수령인',
    'delivery_date' => date('Y-m-d'),
    'delivery_time' => '오전'
];
$result = testSupabaseApi('receipts', 'POST', $testReceipt);
if ($result['success']) {
    $data = json_decode($result['response'], true);
    echo "   [OK] INSERT 성공\n";
    if (isset($data[0]['id'])) {
        echo "   [OK] 삽입된 ID: {$data[0]['id']}\n";
        $results['receipts_insert'] = 'SUCCESS';
    } else {
        echo "   [WARN] ID 반환 안됨: " . substr($result['response'], 0, 200) . "\n";
        $results['receipts_insert'] = 'NO_ID';
    }
} else {
    echo "   [FAIL] INSERT 실패: HTTP {$result['status']}\n";
    echo "   응답: " . substr($result['response'] ?? '', 0, 300) . "\n";
    $results['receipts_insert'] = 'FAILED';
}

// 결과 요약
echo "\n=== 테스트 결과 요약 ===\n";
$successCount = 0;
$failCount = 0;
foreach ($results as $test => $status) {
    $icon = ($status === 'SUCCESS') ? '[OK]' : '[FAIL]';
    echo "  $icon $test: $status\n";
    if ($status === 'SUCCESS') {
        $successCount++;
    } else {
        $failCount++;
    }
}
echo "\n총 {$successCount}개 성공, {$failCount}개 실패\n";

// PHP database_supabase.php 테스트
echo "\n=== database_supabase.php 테스트 ===\n";
require_once __DIR__ . '/config/database_supabase.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // SELECT 테스트
    echo "\n7. SupabaseQuery SELECT 테스트...\n";
    $stmt = $conn->query("SELECT * FROM admins WHERE username = 'admin'");
    $admin = $stmt->fetch();
    if ($admin) {
        echo "   [OK] SELECT 성공: admin 사용자 조회됨\n";
        echo "   이름: " . ($admin['name'] ?? 'N/A') . "\n";
    } else {
        echo "   [FAIL] SELECT 실패: admin 사용자 없음\n";
    }

    // INSERT 테스트
    echo "\n8. SupabaseQuery INSERT 테스트...\n";
    $receiptNumber = 'TEST2-' . date('Ymd') . '-' . rand(1000, 9999);
    $sql = "INSERT INTO receipts (receipt_number, orderer_name) VALUES (?, ?)";
    $insertStmt = $conn->prepare($sql);
    $insertStmt->execute([$receiptNumber, '테스트']);
    $lastId = $conn->lastInsertId();

    if ($lastId) {
        echo "   [OK] INSERT 성공: ID = $lastId\n";
    } else {
        echo "   [WARN] INSERT 실행됨, 하지만 lastInsertId 반환 안됨\n";
    }

} catch (Exception $e) {
    echo "   [ERROR] 예외 발생: " . $e->getMessage() . "\n";
}

echo "\n=== 테스트 완료 ===\n";
