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

$db = getDB();

$placeholders = implode(',', array_fill(0, count($data['receipt_ids']), '?'));
$sql = "DELETE FROM receipts WHERE id IN ($placeholders)";
$params = $data['receipt_ids'];

$stmt = $db->prepare($sql);
$stmt->execute($params);

$affected = $stmt->rowCount();

successResponse("{$affected}건의 인수증이 삭제되었습니다.", ['affected' => $affected]);
