<?php
/**
 * 발주하기 필드별 목록 목업 데이터 생성 스크립트
 * 각 목록에 10개씩 데이터를 생성합니다.
 */

// CLI 환경에서 직접 데이터베이스 연결
require_once __DIR__ . '/config/database_fast.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$db = getDB();

echo "목록 테이블 생성 및 목업 데이터 생성을 시작합니다...\n\n";

// 테이블 생성
$tables = [
    'delivery_methods' => "CREATE TABLE IF NOT EXISTS delivery_methods (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50) NOT NULL UNIQUE, display_order INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    'order_statuses' => "CREATE TABLE IF NOT EXISTS order_statuses (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50) NOT NULL UNIQUE, display_order INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    'pot_sizes' => "CREATE TABLE IF NOT EXISTS pot_sizes (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50) NOT NULL UNIQUE, display_order INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    'pot_types' => "CREATE TABLE IF NOT EXISTS pot_types (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50) NOT NULL UNIQUE, display_order INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    'pot_colors' => "CREATE TABLE IF NOT EXISTS pot_colors (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50) NOT NULL UNIQUE, display_order INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    'plant_sizes' => "CREATE TABLE IF NOT EXISTS plant_sizes (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50) NOT NULL UNIQUE, display_order INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    'plant_types' => "CREATE TABLE IF NOT EXISTS plant_types (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(100) NOT NULL UNIQUE, display_order INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    'ribbons' => "CREATE TABLE IF NOT EXISTS ribbons (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50) NOT NULL UNIQUE, display_order INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    'policies' => "CREATE TABLE IF NOT EXISTS policies (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50) NOT NULL UNIQUE, display_order INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    'accessories' => "CREATE TABLE IF NOT EXISTS accessories (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50) NOT NULL UNIQUE, display_order INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)"
];

foreach ($tables as $tableName => $createSql) {
    try {
        $db->exec($createSql);
        echo "✓ {$tableName} 테이블 생성 완료\n";
    } catch (PDOException $e) {
        echo "경고: {$tableName} 테이블 생성 중 오류 - " . $e->getMessage() . "\n";
    }
}
echo "\n";

// 목업 데이터
$mockupData = [
    'delivery_methods' => [
        '특송', '일반배송', '직접배송', '특급배송', '당일배송', 
        '익일배송', '택배', '퀵서비스', '화물', '직접수령'
    ],
    'order_statuses' => [
        '신규', '신규도착완료', '동일배송', '동일배송도착완료', '배송준비중',
        '배송중', '배송완료', '취소', '환불', '교환'
    ],
    'pot_sizes' => [
        '소형', '중형', '대형', '특대형', '미니',
        '스몰', '미디엄', '라지', '엑스라지', '커스텀'
    ],
    'pot_types' => [
        '화분', '꽃바구니', '라탄바구니', '플라스틱화분', '도자기화분',
        '세라믹화분', '유리화분', '나무화분', '메탈화분', '기타'
    ],
    'pot_colors' => [
        '흰색', '검정', '갈색', '빨강', '파랑',
        '초록', '노랑', '핑크', '보라', '혼합'
    ],
    'plant_sizes' => [
        '소형(S)', '중형(M)', '대형(L)', '특대형(XL)', '미니',
        '스몰', '미디엄', '라지', '엑스라지', '커스텀'
    ],
    'plant_types' => [
        '장미', '난', '관엽식물', '다육식물', '화환',
        '꽃 바구니(공통)', 'S-핑크장미혼합', '빨간장미', '핑크장미', '혼합꽃다발'
    ],
    'ribbons' => [
        '없음', '리본(소,중)', '리본(대,VIP)', '리본(특대)', '골드리본',
        '실버리본', '레드리본', '블루리본', '그린리본', '커스텀리본'
    ],
    'policies' => [
        '선택', '없음', '필수', '옵션', '권장',
        '기본', '프리미엄', 'VIP', '특별', '기타'
    ],
    'accessories' => [
        '리본(소,중)', '리본(대,VIP)', '리본(특대)', '빨강(소,중)', '빨강(대,VIP)',
        '빨강(특대)', '★당일★', '@', '카드메세지', '베베로'
    ]
];

$totalInserted = 0;

foreach ($mockupData as $tableName => $items) {
    echo "{$tableName} 테이블 데이터 생성 중...\n";
    $inserted = 0;
    
    $stmt = $db->prepare("INSERT OR IGNORE INTO {$tableName} (name, display_order) VALUES (?, ?)");
    
    foreach ($items as $index => $item) {
        try {
            $stmt->execute([$item, $index + 1]);
            if ($stmt->rowCount() > 0) {
                $inserted++;
                echo "  ✓ '{$item}' 추가\n";
            } else {
                echo "  - '{$item}' (이미 존재)\n";
            }
        } catch (PDOException $e) {
            echo "  ✗ '{$item}' 오류: " . $e->getMessage() . "\n";
        }
    }
    
    $totalInserted += $inserted;
    echo "  완료: {$inserted}개 추가됨\n\n";
}

echo "총 {$totalInserted}개의 목록 항목이 생성되었습니다.\n";
echo "설정 페이지에서 확인하실 수 있습니다.\n";
