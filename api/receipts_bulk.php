<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    errorResponse('POST 메서드만 지원합니다.', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['count']) || $data['count'] < 1 || $data['count'] > 100) {
    errorResponse('수량은 1~100 사이여야 합니다.');
}

$orderId = $data['order_id'] ?? null;
$templateId = $data['template_id'] ?? null;

// 인수증 상세 정보
$ordererName = $data['orderer_name'] ?? null;
$ordererPhone1 = $data['orderer_phone1'] ?? null;
$ordererPhone2 = $data['orderer_phone2'] ?? null;
$recipientName = $data['recipient_name'] ?? null;
$recipientPhone1 = $data['recipient_phone1'] ?? null;
$recipientPhone2 = $data['recipient_phone2'] ?? null;
$deliveryDate = $data['delivery_date'] ?? null;
$deliveryTime = $data['delivery_time'] ?? null;
$deliveryDetailTime = $data['delivery_detail_time'] ?? null;
$occasionWord = $data['occasion_word'] ?? null;
$senderName = $data['sender_name'] ?? null;
$deliveryPostcode = $data['delivery_postcode'] ?? null;
$deliveryAddress = $data['delivery_address'] ?? null;
$deliveryDetailAddress = $data['delivery_detail_address'] ?? null;
$deliveryRequest = $data['delivery_request'] ?? null;

$db = getDB();
$receipts = [];

try {
    $db->beginTransaction();
    
    // 마지막 인수증 번호 조회 (순차 채번을 위해)
    $stmt = $db->query("SELECT receipt_number FROM receipts ORDER BY id DESC LIMIT 1");
    $lastReceipt = $stmt->fetch();
    $lastNumber = 0;
    
    if ($lastReceipt) {
        // RCP-20231223-0001 형식에서 마지막 번호 추출
        $parts = explode('-', $lastReceipt['receipt_number']);
        if (count($parts) >= 3) {
            $lastNumber = intval($parts[2]);
        }
    }
    
    for ($i = 0; $i < $data['count']; $i++) {
        $lastNumber++;
        $receiptNumber = 'RCP-' . date('Ymd') . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO receipts (
            receipt_number, order_id, template_id,
            orderer_name, orderer_phone1, orderer_phone2,
            recipient_name, recipient_phone1, recipient_phone2,
            delivery_date, delivery_time, delivery_detail_time,
            occasion_word, sender_name,
            delivery_postcode, delivery_address, delivery_detail_address, delivery_request
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
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
            $deliveryRequest
        ]);
        
        $receipts[] = [
            'id' => $db->lastInsertId(),
            'receipt_number' => $receiptNumber
        ];
    }
    
    $db->commit();
    
    successResponse("{$data['count']}건의 인수증이 생성되었습니다.", ['receipts' => $receipts]);
    
} catch (Exception $e) {
    $db->rollBack();
    errorResponse('인수증 생성 중 오류가 발생했습니다: ' . $e->getMessage());
}
