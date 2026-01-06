<?php
/**
 * Supabase 목업 데이터 생성 스크립트
 * 배포 환경에서 목업 데이터를 생성합니다.
 */

require_once __DIR__ . '/config/config.php';

// 관리자 ID 조회
$db = getDB();
$stmt = $db->query("SELECT id FROM admins LIMIT 1");
$admin = $stmt->fetch();
if (!$admin) {
    die("관리자 계정이 없습니다.\n");
}
$adminId = $admin['id'];

echo "목업 데이터 생성을 시작합니다...\n\n";

// 1. 리스트 테이블 목업 데이터
$listData = [
    'delivery_methods' => ['특송', '일반배송', '직접배송', '특급배송', '당일배송', '익일배송', '택배', '퀵서비스', '화물', '직접수령'],
    'order_statuses' => ['신규', '신규도착완료', '동일배송', '동일배송도착완료', '배송준비중', '배송중', '배송완료', '취소', '환불', '교환'],
    'pot_sizes' => ['소형', '중형', '대형', '특대형', '미니', '스몰', '미디엄', '라지', '엑스라지', '커스텀'],
    'pot_types' => ['화분', '꽃바구니', '라탄바구니', '플라스틱화분', '도자기화분', '세라믹화분', '유리화분', '나무화분', '메탈화분', '기타'],
    'pot_colors' => ['흰색', '검정', '갈색', '빨강', '파랑', '초록', '노랑', '핑크', '보라', '혼합'],
    'plant_sizes' => ['소형(S)', '중형(M)', '대형(L)', '특대형(XL)', '미니', '스몰', '미디엄', '라지', '엑스라지', '커스텀'],
    'plant_types' => ['장미', '난', '관엽식물', '다육식물', '화환', '꽃 바구니(공통)', 'S-핑크장미혼합', '빨간장미', '핑크장미', '혼합꽃다발'],
    'ribbons' => ['없음', '리본(소,중)', '리본(대,VIP)', '리본(특대)', '골드리본', '실버리본', '레드리본', '블루리본', '그린리본', '커스텀리본'],
    'policies' => ['선택', '없음', '필수', '옵션', '권장', '기본', '프리미엄', 'VIP', '특별', '기타'],
    'accessories' => ['리본(소,중)', '리본(대,VIP)', '리본(특대)', '빨강(소,중)', '빨강(대,VIP)', '빨강(특대)', '★당일★', '@', '카드메세지', '베베로']
];

foreach ($listData as $table => $items) {
    echo "{$table} 데이터 생성 중...\n";
    $count = 0;
    foreach ($items as $index => $name) {
        try {
            $stmt = $db->prepare("INSERT INTO {$table} (name, display_order) VALUES (?, ?) ON CONFLICT (name) DO NOTHING");
            $stmt->execute([$name, $index + 1]);
            if ($stmt->rowCount() > 0) {
                $count++;
            }
        } catch (Exception $e) {
            // 무시
        }
    }
    echo "  ✓ {$count}개 추가됨\n";
}

// 2. 템플릿 목업 데이터
echo "\n템플릿 데이터 생성 중...\n";
$templates = [
    ['장미꽃다발', '특송', '중', '화분', '흰색', 'S', '장미', '리본(소,중)', '선택', '리본(소,중), 카드메세지'],
    ['프리미엄 난 바구니', '특급배송', '대', '라탄바구니', '자연색', 'L', '난', '리본(대,VIP)', '필수', '리본(대,VIP), ★당일★, 카드메세지'],
    ['VIP 장미 혼합', '특송', '특대', '화분', '골드', 'XL', 'S-핑크장미혼합', '리본(특대)', '필수', '리본(특대), 빨강(특대), ★당일★, 카드메세지, 베베로'],
    ['일반 꽃바구니', '일반배송', '소', '꽃바구니', '갈색', 'M', '꽃 바구니(공통)', '리본(소,중)', '선택', '리본(소,중)'],
    ['생일 축하 화분', '직접배송', '중', '화분', '파란색', 'M', '관엽식물', '빨강(소,중)', '선택', '빨강(소,중), 카드메세지, @'],
    ['결혼식 축하 화환', '특급배송', '대', '화분', '흰색', 'L', '화환', '리본(대,VIP)', '필수', '리본(대,VIP), 빨강(대,VIP), 카드메세지'],
    ['장례식 화환', '특송', '대', '화분', '검정', 'L', '화환', '빨강(대,VIP)', '필수', '빨강(대,VIP), 카드메세지'],
    ['소형 다육식물', '일반배송', '소', '화분', '테라코타', 'S', '다육식물', '없음', '없음', ''],
    ['고급 난 화분 세트', '특급배송', '중', '도자기화분', '청자색', 'M', '난', '리본(소,중)', '선택', '리본(소,중), 카드메세지'],
    ['당일 배송 장미', '특급배송', '중', '화분', '빨간색', 'M', '빨간장미', '빨강(소,중)', '선택', '빨강(소,중), ★당일★, 카드메세지']
];

$templateCount = 0;
$stmt = $db->prepare("INSERT INTO receipt_templates (name, delivery_method, pot_size, pot_type, pot_color, plant_size, plant_type, ribbon, policy, accessories, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($templates as $t) {
    try {
        $stmt->execute([$t[0], $t[1], $t[2], $t[3], $t[4], $t[5], $t[6], $t[7], $t[8], $t[9], $adminId]);
        $templateCount++;
    } catch (Exception $e) {
        // 무시
    }
}
echo "  ✓ {$templateCount}개 템플릿 추가됨\n";

// 3. 주문 목업 데이터
echo "\n주문 데이터 생성 중...\n";
$salesChannels = $db->query("SELECT id FROM sales_channels LIMIT 4")->fetchAll();
$flowerShops = $db->query("SELECT id FROM flower_shops LIMIT 4")->fetchAll();

if (count($salesChannels) > 0 && count($flowerShops) > 0) {
    $orderCount = 0;
    $stmt = $db->prepare("INSERT INTO orders (order_number, order_date, sales_channel_id, flower_shop_id, manager_name, delivery_method, status, sales_amount, order_amount, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    for ($i = 1; $i <= 20; $i++) {
        $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
        $orderDate = date('Y-m-d', strtotime("-" . rand(0, 30) . " days"));
        $salesChannelId = $salesChannels[rand(0, count($salesChannels) - 1)]['id'];
        $flowerShopId = $flowerShops[rand(0, count($flowerShops) - 1)]['id'];
        $deliveryMethod = ['특송', '일반배송', '직접배송', '특급배송'][rand(0, 3)];
        $status = ['신규', '신규도착완료', '동일배송', '배송완료'][rand(0, 3)];
        $salesAmount = rand(50000, 200000);
        $orderAmount = $salesAmount + rand(0, 10000);
        
        try {
            $stmt->execute([$orderNumber, $orderDate, $salesChannelId, $flowerShopId, '관리자', $deliveryMethod, $status, $salesAmount, $orderAmount, $adminId]);
            $orderCount++;
        } catch (Exception $e) {
            // 무시
        }
    }
    echo "  ✓ {$orderCount}개 주문 추가됨\n";
}

echo "\n목업 데이터 생성이 완료되었습니다!\n";
