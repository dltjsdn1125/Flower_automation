<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    errorResponse('POST 메서드만 지원합니다.', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['receipt_ids']) || !is_array($data['receipt_ids']) || empty($data['receipt_ids'])) {
    errorResponse('인수증 ID 배열이 필요합니다.');
}

if (!isset($data['status']) || empty($data['status'])) {
    errorResponse('변경할 상태가 필요합니다.');
}

$allowedStatuses = ['대기', '배송중', '배송완료', '취소'];
if (!in_array($data['status'], $allowedStatuses)) {
    errorResponse('허용되지 않은 상태입니다.');
}

$db = getDB();

// receipts 테이블에 status 컬럼이 있는지 확인하고 없으면 추가
try {
    $db->exec("ALTER TABLE receipts ADD COLUMN status VARCHAR(50) DEFAULT '대기'");
} catch (PDOException $e) {
    // 컬럼이 이미 존재하는 경우 무시
    if (strpos($e->getMessage(), 'duplicate column') === false && 
        strpos($e->getMessage(), 'already exists') === false) {
        error_log("Status column check error: " . $e->getMessage());
    }
}

$placeholders = implode(',', array_fill(0, count($data['receipt_ids']), '?'));
$sql = "UPDATE receipts SET status = ? WHERE id IN ($placeholders)";
$params = array_merge([$data['status']], $data['receipt_ids']);

$stmt = $db->prepare($sql);
$stmt->execute($params);

$affected = $stmt->rowCount();

successResponse("{$affected}건의 인수증 상태가 변경되었습니다.", ['affected' => $affected]);
