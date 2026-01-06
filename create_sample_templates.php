<?php
/**
 * 샘플 템플릿 생성 스크립트
 * 다양한 구성의 템플릿 10개를 생성합니다.
 */

require_once __DIR__ . '/config/config.php';

// CLI 실행 시 세션 없이 실행
if (php_sapi_name() === 'cli') {
    // 관리자 ID 직접 조회
    $db = getDB();
    $stmt = $db->query("SELECT id FROM admins LIMIT 1");
    $admin = $stmt->fetch();
    if (!$admin) {
        echo "관리자 계정이 없습니다. 먼저 데이터베이스를 설정해주세요.\n";
        exit(1);
    }
    $adminId = $admin['id'];
} else {
    requireLogin();
    $db = getDB();
    $adminId = $_SESSION['admin_id'];
}

// 기존 템플릿 개수 확인
$stmt = $db->prepare("SELECT COUNT(*) as count FROM receipt_templates WHERE created_by = ?");
$stmt->execute([$adminId]);
$currentCount = $stmt->fetch()['count'];

if ($currentCount >= 10) {
    echo "이미 최대 10개의 템플릿이 등록되어 있습니다.\n";
    echo "기존 템플릿을 삭제한 후 다시 실행해주세요.\n";
    exit;
}

// 추가 가능한 개수 계산
$remaining = 10 - $currentCount;
$templatesToCreate = min($remaining, 10);

echo "현재 템플릿 개수: {$currentCount}개\n";
echo "추가 가능한 개수: {$remaining}개\n";
echo "생성할 템플릿 개수: {$templatesToCreate}개\n\n";

// 다양한 구성의 템플릿 데이터
$templates = [
    [
        'name' => '장미꽃다발',
        'delivery_method' => '특송',
        'pot_size' => '중',
        'pot_type' => '화분',
        'pot_color' => '흰색',
        'plant_size' => 'S',
        'plant_type' => '장미',
        'ribbon' => '리본(소,중)',
        'policy' => '선택',
        'accessories' => '리본(소,중), 카드메세지'
    ],
    [
        'name' => '프리미엄 난 바구니',
        'delivery_method' => '특급배송',
        'pot_size' => '대',
        'pot_type' => '라탄바구니',
        'pot_color' => '자연색',
        'plant_size' => 'L',
        'plant_type' => '난',
        'ribbon' => '리본(대,VIP)',
        'policy' => '필수',
        'accessories' => '리본(대,VIP), ★당일★, 카드메세지'
    ],
    [
        'name' => 'VIP 장미 혼합',
        'delivery_method' => '특송',
        'pot_size' => '특대',
        'pot_type' => '화분',
        'pot_color' => '골드',
        'plant_size' => 'XL',
        'plant_type' => 'S-핑크장미혼합',
        'ribbon' => '리본(특대)',
        'policy' => '필수',
        'accessories' => '리본(특대), 빨강(특대), ★당일★, 카드메세지, 베베로'
    ],
    [
        'name' => '일반 꽃바구니',
        'delivery_method' => '일반배송',
        'pot_size' => '소',
        'pot_type' => '꽃바구니',
        'pot_color' => '갈색',
        'plant_size' => 'M',
        'plant_type' => '꽃 바구니(공통)',
        'ribbon' => '리본(소,중)',
        'policy' => '선택',
        'accessories' => '리본(소,중)'
    ],
    [
        'name' => '생일 축하 화분',
        'delivery_method' => '직접배송',
        'pot_size' => '중',
        'pot_type' => '화분',
        'pot_color' => '파란색',
        'plant_size' => 'M',
        'plant_type' => '관엽식물',
        'ribbon' => '빨강(소,중)',
        'policy' => '선택',
        'accessories' => '빨강(소,중), 카드메세지, @'
    ],
    [
        'name' => '결혼식 축하 화환',
        'delivery_method' => '특급배송',
        'pot_size' => '대',
        'pot_type' => '화분',
        'pot_color' => '흰색',
        'plant_size' => 'L',
        'plant_type' => '화환',
        'ribbon' => '리본(대,VIP)',
        'policy' => '필수',
        'accessories' => '리본(대,VIP), 빨강(대,VIP), 카드메세지'
    ],
    [
        'name' => '장례식 화환',
        'delivery_method' => '특송',
        'pot_size' => '대',
        'pot_type' => '화분',
        'pot_color' => '검은색',
        'plant_size' => 'L',
        'plant_type' => '화환',
        'ribbon' => '빨강(대,VIP)',
        'policy' => '필수',
        'accessories' => '빨강(대,VIP), 카드메세지'
    ],
    [
        'name' => '소형 다육식물',
        'delivery_method' => '일반배송',
        'pot_size' => '소',
        'pot_type' => '화분',
        'pot_color' => '테라코타',
        'plant_size' => 'S',
        'plant_type' => '다육식물',
        'ribbon' => '없음',
        'policy' => '없음',
        'accessories' => ''
    ],
    [
        'name' => '고급 난 화분 세트',
        'delivery_method' => '특급배송',
        'pot_size' => '중',
        'pot_type' => '도자기화분',
        'pot_color' => '청자색',
        'plant_size' => 'M',
        'plant_type' => '난',
        'ribbon' => '리본(소,중)',
        'policy' => '선택',
        'accessories' => '리본(소,중), 카드메세지'
    ],
    [
        'name' => '당일 배송 장미',
        'delivery_method' => '특급배송',
        'pot_size' => '중',
        'pot_type' => '화분',
        'pot_color' => '빨간색',
        'plant_size' => 'M',
        'plant_type' => '빨간장미',
        'ribbon' => '빨강(소,중)',
        'policy' => '선택',
        'accessories' => '빨강(소,중), ★당일★, 카드메세지'
    ]
];

// 템플릿 삽입
$inserted = 0;
$sql = "INSERT INTO receipt_templates (name, delivery_method, pot_size, pot_type, pot_color, plant_size, plant_type, ribbon, policy, accessories, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $db->prepare($sql);

foreach (array_slice($templates, 0, $templatesToCreate) as $template) {
    try {
        $stmt->execute([
            $template['name'],
            $template['delivery_method'],
            $template['pot_size'],
            $template['pot_type'],
            $template['pot_color'],
            $template['plant_size'],
            $template['plant_type'],
            $template['ribbon'],
            $template['policy'],
            $template['accessories'],
            $adminId
        ]);
        $inserted++;
        echo "✓ '{$template['name']}' 템플릿 생성 완료\n";
    } catch (PDOException $e) {
        echo "✗ '{$template['name']}' 템플릿 생성 실패: " . $e->getMessage() . "\n";
    }
}

echo "\n총 {$inserted}개의 템플릿이 생성되었습니다.\n";
echo "템플릿 관리 페이지에서 확인하실 수 있습니다.\n";
