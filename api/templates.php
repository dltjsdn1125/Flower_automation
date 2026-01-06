<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {
    case 'GET':
        // 템플릿 목록 조회
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            // 특정 템플릿 조회
            $stmt = $db->prepare("SELECT * FROM receipt_templates WHERE id = ? AND created_by = ?");
            $stmt->execute([$id, $_SESSION['admin_id']]);
            $template = $stmt->fetch();
            
            if ($template) {
                successResponse('조회 성공', $template);
            } else {
                errorResponse('템플릿을 찾을 수 없습니다.', 404);
            }
        } else {
            // 전체 템플릿 목록
            $stmt = $db->prepare("SELECT * FROM receipt_templates WHERE created_by = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$_SESSION['admin_id']]);
            $templates = $stmt->fetchAll();
            
            successResponse('조회 성공', $templates);
        }
        break;
        
    case 'POST':
        // 템플릿 생성
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['name']) || empty($data['name'])) {
            errorResponse('템플릿 이름이 필요합니다.');
        }
        
        // 최대 10개 제한 체크
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM receipt_templates WHERE created_by = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $count = $stmt->fetch()['count'];
        
        if ($count >= 10) {
            errorResponse('템플릿은 최대 10개까지 생성할 수 있습니다.');
        }
        
        $sql = "INSERT INTO receipt_templates (
            name, delivery_method, pot_size, pot_type, pot_color,
            plant_size, plant_type, ribbon, policy, accessories, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['delivery_method'] ?? null,
            $data['pot_size'] ?? null,
            $data['pot_type'] ?? null,
            $data['pot_color'] ?? null,
            $data['plant_size'] ?? null,
            $data['plant_type'] ?? null,
            $data['ribbon'] ?? null,
            $data['policy'] ?? null,
            $data['accessories'] ?? null,
            $_SESSION['admin_id']
        ]);
        
        successResponse('템플릿이 생성되었습니다.', ['id' => $db->lastInsertId()]);
        break;
        
    case 'PUT':
        // 템플릿 수정
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            errorResponse('템플릿 ID가 필요합니다.');
        }
        
        $sql = "UPDATE receipt_templates SET 
            name = ?, delivery_method = ?, pot_size = ?, pot_type = ?, pot_color = ?,
            plant_size = ?, plant_type = ?, ribbon = ?, policy = ?, accessories = ?
            WHERE id = ? AND created_by = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['delivery_method'] ?? null,
            $data['pot_size'] ?? null,
            $data['pot_type'] ?? null,
            $data['pot_color'] ?? null,
            $data['plant_size'] ?? null,
            $data['plant_type'] ?? null,
            $data['ribbon'] ?? null,
            $data['policy'] ?? null,
            $data['accessories'] ?? null,
            $data['id'],
            $_SESSION['admin_id']
        ]);
        
        successResponse('템플릿이 수정되었습니다.');
        break;
        
    case 'DELETE':
        // 템플릿 삭제
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            errorResponse('템플릿 ID가 필요합니다.');
        }
        
        $stmt = $db->prepare("DELETE FROM receipt_templates WHERE id = ? AND created_by = ?");
        $stmt->execute([$id, $_SESSION['admin_id']]);
        
        successResponse('템플릿이 삭제되었습니다.');
        break;
        
    default:
        errorResponse('지원하지 않는 메서드입니다.', 405);
}
