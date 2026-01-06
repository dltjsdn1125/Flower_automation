<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    errorResponse('POST 메서드만 지원합니다.', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_ids']) || !is_array($data['order_ids']) || empty($data['order_ids'])) {
    errorResponse('주문 ID 배열이 필요합니다.');
}

if (!isset($data['status']) || empty($data['status'])) {
    errorResponse('변경할 상태가 필요합니다.');
}

$allowedStatuses = ['신규도착완료', '동일배송도착완료'];
if (!in_array($data['status'], $allowedStatuses)) {
    errorResponse('허용되지 않은 상태입니다.');
}

$db = getDB();
$placeholders = implode(',', array_fill(0, count($data['order_ids']), '?'));

$sql = "UPDATE orders SET status = ? WHERE id IN ($placeholders)";
$params = array_merge([$data['status']], $data['order_ids']);

$stmt = $db->prepare($sql);
$stmt->execute($params);

$affected = $stmt->rowCount();

successResponse("{$affected}건의 주문 상태가 변경되었습니다.", ['affected' => $affected]);
