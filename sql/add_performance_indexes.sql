-- 성능 개선을 위한 추가 인덱스
-- 기존 데이터베이스에 인덱스를 추가하는 마이그레이션 스크립트

-- orders 테이블 인덱스
CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at);

-- receipts 테이블 인덱스
CREATE INDEX IF NOT EXISTS idx_receipts_created_at ON receipts(created_at);
CREATE INDEX IF NOT EXISTS idx_receipts_orderer_name ON receipts(orderer_name);
