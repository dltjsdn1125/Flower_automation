<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    errorResponse('POST 메서드만 지원합니다.', 405);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg() . " - Input: " . substr($input, 0, 500));
    errorResponse('잘못된 JSON 형식입니다: ' . json_last_error_msg());
}

if (!isset($data['count']) || $data['count'] < 1 || $data['count'] > 100) {
    error_log("Invalid count: " . ($data['count'] ?? 'null'));
    errorResponse('수량은 1~100 사이여야 합니다.');
}

$orderId = $data['order_id'] ?? null;
$templateId = $data['template_id'] ?? null;

// 인수증 상세 정보 (빈 문자열을 NULL로 변환)
$ordererName = !empty($data['orderer_name']) ? $data['orderer_name'] : null;
$ordererPhone1 = !empty($data['orderer_phone1']) ? $data['orderer_phone1'] : null;
$ordererPhone2 = !empty($data['orderer_phone2']) ? $data['orderer_phone2'] : null;
$recipientName = !empty($data['recipient_name']) ? $data['recipient_name'] : null;
$recipientPhone1 = !empty($data['recipient_phone1']) ? $data['recipient_phone1'] : null;
$recipientPhone2 = !empty($data['recipient_phone2']) ? $data['recipient_phone2'] : null;
// 날짜 필드는 빈 문자열을 NULL로 변환 (Supabase는 날짜 타입에 빈 문자열을 허용하지 않음)
$deliveryDate = !empty($data['delivery_date']) ? $data['delivery_date'] : null;
$deliveryTime = !empty($data['delivery_time']) ? $data['delivery_time'] : null;
$deliveryDetailTime = !empty($data['delivery_detail_time']) ? $data['delivery_detail_time'] : null;
$occasionWord = !empty($data['occasion_word']) ? $data['occasion_word'] : null;
$senderName = !empty($data['sender_name']) ? $data['sender_name'] : null;
$deliveryPostcode = !empty($data['delivery_postcode']) ? $data['delivery_postcode'] : null;
$deliveryAddress = !empty($data['delivery_address']) ? $data['delivery_address'] : null;
$deliveryDetailAddress = !empty($data['delivery_detail_address']) ? $data['delivery_detail_address'] : null;
$deliveryRequest = !empty($data['delivery_request']) ? $data['delivery_request'] : null;

$db = getDB();
$receipts = [];

try {
    // 트랜잭션 시작 전에 최신 번호 조회 (트랜잭션 내부에서는 다른 세션의 INSERT를 볼 수 없음)
    $today = date('Ymd');
    $stmt = $db->query("SELECT receipt_number FROM receipts WHERE receipt_number LIKE 'RCP-{$today}-%' ORDER BY receipt_number DESC LIMIT 1");
    $lastReceipt = $stmt->fetch();
    $lastNumber = 0;
    
    if ($lastReceipt && isset($lastReceipt['receipt_number'])) {
        // RCP-20231223-0001 형식에서 마지막 번호 추출
        $parts = explode('-', $lastReceipt['receipt_number']);
        if (count($parts) >= 3 && $parts[0] === 'RCP' && $parts[1] === $today) {
            $lastNumber = intval($parts[2]);
        }
    }
    
    // 트랜잭션 시작
    $db->beginTransaction();
    
    // 필요한 모든 번호를 미리 계산 (트랜잭션 내부에서 조회하면 다른 세션의 INSERT를 볼 수 없음)
    for ($i = 0; $i < $data['count']; $i++) {
        $maxRetries = 10; // 최대 재시도 횟수
        $inserted = false;
        $insertedId = null;
        $receiptNumber = null;
        
        for ($retry = 0; $retry < $maxRetries && !$inserted; $retry++) {
            $lastNumber++;
            $receiptNumber = 'RCP-' . date('Ymd') . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);
            
            try {
                $sql = "INSERT INTO receipts (
                    receipt_number, order_id, template_id,
                    orderer_name, orderer_phone1, orderer_phone2,
                    recipient_name, recipient_phone1, recipient_phone2,
                    delivery_date, delivery_time, delivery_detail_time,
                    occasion_word, sender_name,
                    delivery_postcode, delivery_address, delivery_detail_address, delivery_request,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $db->prepare($sql);
                $now = date('Y-m-d H:i:s');
                $stmt->execute([
                    $receiptNumber,
                    $orderId,
                    $templateId,
                    $ordererName,
                    $ordererPhone1,
                    $ordererPhone2,
                    $recipientName,
                    $recipientPhone1,
                    $recipientPhone2,
                    $deliveryDate,
                    $deliveryTime,
                    $deliveryDetailTime,
                    $occasionWord,
                    $senderName,
                    $deliveryPostcode,
                    $deliveryAddress,
                    $deliveryDetailAddress,
                    $deliveryRequest,
                    $now
                ]);
                
                // Supabase의 경우 INSERT 응답에서 ID를 가져오기
                // execute() 후 fetchAll()을 호출하면 INSERT 응답에서 ID를 가져올 수 있음
                $insertResult = $stmt->fetchAll();
                
                if (!empty($insertResult) && isset($insertResult[0]['id'])) {
                    $insertedId = $insertResult[0]['id'];
                } else {
                    // fetchAll()로 ID를 가져올 수 없는 경우 lastInsertId() 시도
                    $insertedId = $db->lastInsertId();
                }
                
                if ($insertedId) {
                    $inserted = true;
                }
            } catch (Exception $e) {
                // 중복 키 오류인 경우 다음 번호로 재시도
                if (strpos($e->getMessage(), 'duplicate key') !== false || 
                    strpos($e->getMessage(), '23505') !== false ||
                    strpos($e->getMessage(), 'already exists') !== false) {
                    error_log("Duplicate receipt_number detected: $receiptNumber, retrying with next number...");
                    continue; // 다음 번호로 재시도
                } else {
                    // 다른 오류인 경우 예외를 다시 던짐
                    throw $e;
                }
            }
        }
        
        if (!$inserted) {
            throw new Exception("인수증 번호 생성에 실패했습니다. 최대 재시도 횟수를 초과했습니다.");
        }
        
        $receipts[] = [
            'id' => $insertedId,
            'receipt_number' => $receiptNumber
        ];
    }
    
    $db->commit();
    
    successResponse("{$data['count']}건의 인수증이 생성되었습니다.", ['receipts' => $receipts]);
    
} catch (Exception $e) {
    $db->rollBack();
    errorResponse('인수증 생성 중 오류가 발생했습니다: ' . $e->getMessage());
}
