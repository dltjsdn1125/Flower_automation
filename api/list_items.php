<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$tableName = $_GET['table'] ?? '';

// 허용된 테이블 목록
$allowedTables = [
    'delivery_methods', 'order_statuses', 'pot_sizes', 'pot_types', 'pot_colors',
    'plant_sizes', 'plant_types', 'ribbons', 'policies', 'accessories'
];

if (!in_array($tableName, $allowedTables)) {
    errorResponse('유효하지 않은 테이블 이름입니다.');
}

$db = getDB();

switch ($method) {
    case 'GET':
        // 목록 조회
        $stmt = $db->query("SELECT * FROM {$tableName} ORDER BY display_order ASC, name ASC");
        $items = $stmt->fetchAll();
        successResponse('목록 조회 성공', ['items' => $items]);
        break;
        
    case 'POST':
        // 항목 추가
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['name']) || empty($data['name'])) {
            errorResponse('이름이 필요합니다.');
        }
        
        $displayOrder = $data['display_order'] ?? 0;
        
        $stmt = $db->prepare("INSERT INTO {$tableName} (name, display_order) VALUES (?, ?)");
        $stmt->execute([$data['name'], $displayOrder]);
        
        $id = $db->lastInsertId();
        successResponse('항목이 추가되었습니다.', ['id' => $id]);
        break;
        
    case 'PUT':
        // 항목 수정
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id']) || !isset($data['name'])) {
            errorResponse('ID와 이름이 필요합니다.');
        }
        
        $displayOrder = $data['display_order'] ?? 0;
        
        $stmt = $db->prepare("UPDATE {$tableName} SET name = ?, display_order = ? WHERE id = ?");
        $stmt->execute([$data['name'], $displayOrder, $data['id']]);
        
        successResponse('항목이 수정되었습니다.');
        break;
        
    case 'DELETE':
        // 항목 삭제
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            errorResponse('ID가 필요합니다.');
        }
        
        $stmt = $db->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->execute([$id]);
        
        successResponse('항목이 삭제되었습니다.');
        break;
        
    default:
        errorResponse('지원하지 않는 메서드입니다.', 405);
}
