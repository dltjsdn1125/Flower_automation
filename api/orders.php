<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {
    case 'GET':
        // 주문 목록 조회
        $status = $_GET['status'] ?? '';
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $where = [];
        $params = [];
        
        if (!empty($status)) {
            $where[] = "o.status = ?";
            $params[] = $status;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT o.*, sc.name as sales_channel_name, fs.name as flower_shop_name 
                FROM orders o 
                LEFT JOIN sales_channels sc ON o.sales_channel_id = sc.id 
                LEFT JOIN flower_shops fs ON o.flower_shop_id = fs.id 
                $whereClause
                ORDER BY o.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        
        // 전체 개수
        $countSql = "SELECT COUNT(*) as total FROM orders o $whereClause";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute(array_slice($params, 0, -2));
        $total = $countStmt->fetch()['total'];
        
        successResponse('조회 성공', [
            'orders' => $orders,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        break;
        
    case 'POST':
        // 주문 생성
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['order_date', 'sales_channel_id', 'flower_shop_id', 'manager_name', 'delivery_method', 'sales_amount', 'order_amount'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                errorResponse("필수 필드가 누락되었습니다: $field");
            }
        }
        
        $orderNumber = generateOrderNumber();
        
        $sql = "INSERT INTO orders (
            order_number, order_date, sales_channel_id, flower_shop_id, manager_name,
            delivery_method, pot_size, pot_type, pot_color, plant_size, plant_type,
            ribbon, policy, accessories, status, delivery_fee, sales_amount, order_amount, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $orderNumber,
            $data['order_date'],
            $data['sales_channel_id'],
            $data['flower_shop_id'],
            $data['manager_name'],
            $data['delivery_method'],
            $data['pot_size'] ?? null,
            $data['pot_type'] ?? null,
            $data['pot_color'] ?? null,
            $data['plant_size'] ?? null,
            $data['plant_type'] ?? null,
            $data['ribbon'] ?? null,
            $data['policy'] ?? null,
            $data['accessories'] ?? null,
            $data['status'] ?? '신규',
            $data['delivery_fee'] ?? 0,
            $data['sales_amount'],
            $data['order_amount'],
            $_SESSION['admin_id']
        ]);
        
        successResponse('주문이 생성되었습니다.', ['id' => $db->lastInsertId(), 'order_number' => $orderNumber]);
        break;
        
    case 'PUT':
        // 주문 수정
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            errorResponse('주문 ID가 필요합니다.');
        }
        
        $sql = "UPDATE orders SET 
            order_date = ?, sales_channel_id = ?, flower_shop_id = ?, manager_name = ?,
            delivery_method = ?, pot_size = ?, pot_type = ?, pot_color = ?, plant_size = ?,
            plant_type = ?, ribbon = ?, policy = ?, accessories = ?, status = ?,
            delivery_fee = ?, sales_amount = ?, order_amount = ?
            WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['order_date'],
            $data['sales_channel_id'],
            $data['flower_shop_id'],
            $data['manager_name'],
            $data['delivery_method'],
            $data['pot_size'] ?? null,
            $data['pot_type'] ?? null,
            $data['pot_color'] ?? null,
            $data['plant_size'] ?? null,
            $data['plant_type'] ?? null,
            $data['ribbon'] ?? null,
            $data['policy'] ?? null,
            $data['accessories'] ?? null,
            $data['status'] ?? '신규',
            $data['delivery_fee'] ?? 0,
            $data['sales_amount'],
            $data['order_amount'],
            $data['id']
        ]);
        
        successResponse('주문이 수정되었습니다.');
        break;
        
    case 'DELETE':
        // 주문 삭제
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            errorResponse('주문 ID가 필요합니다.');
        }
        
        $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        
        successResponse('주문이 삭제되었습니다.');
        break;
        
    default:
        errorResponse('지원하지 않는 메서드입니다.', 405);
}
