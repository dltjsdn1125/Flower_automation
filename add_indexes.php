<?php
/**
 * 성능 개선을 위한 인덱스 추가 스크립트
 * 기존 데이터베이스에 인덱스를 추가합니다.
 */

require_once __DIR__ . '/config/config.php';
requireLogin();

$db = getDB();

echo "인덱스 추가를 시작합니다...\n";

try {
    // orders 테이블 인덱스
    $db->exec("CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at)");
    echo "✓ idx_orders_created_at 인덱스 추가 완료\n";
    
    // receipts 테이블 인덱스
    $db->exec("CREATE INDEX IF NOT EXISTS idx_receipts_created_at ON receipts(created_at)");
    echo "✓ idx_receipts_created_at 인덱스 추가 완료\n";
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_receipts_orderer_name ON receipts(orderer_name)");
    echo "✓ idx_receipts_orderer_name 인덱스 추가 완료\n";
    
    echo "\n모든 인덱스가 성공적으로 추가되었습니다!\n";
    echo "페이지 이동 속도가 개선되었습니다.\n";
    
} catch (PDOException $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
