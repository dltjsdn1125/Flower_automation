-- 인수증 테이블에 상세 필드 추가 마이그레이션
-- 기존 receipts 테이블이 있는 경우 실행

USE flower_order_system;

-- order_id를 NULL 허용으로 변경 (인수증만 단독 생성 가능)
ALTER TABLE receipts MODIFY COLUMN order_id INT NULL;

-- 주문자 정보 필드 추가
ALTER TABLE receipts 
ADD COLUMN orderer_name VARCHAR(100) AFTER template_id,
ADD COLUMN orderer_phone1 VARCHAR(20) AFTER orderer_name,
ADD COLUMN orderer_phone2 VARCHAR(20) AFTER orderer_phone1;

-- 수취인 정보 필드 추가
ALTER TABLE receipts 
ADD COLUMN recipient_name VARCHAR(100) AFTER orderer_phone2,
ADD COLUMN recipient_phone1 VARCHAR(20) AFTER recipient_name,
ADD COLUMN recipient_phone2 VARCHAR(20) AFTER recipient_phone1;

-- 배달 정보 필드 추가
ALTER TABLE receipts 
ADD COLUMN delivery_date DATE AFTER recipient_phone2,
ADD COLUMN delivery_time VARCHAR(50) AFTER delivery_date,
ADD COLUMN delivery_detail_time VARCHAR(50) AFTER delivery_time;

-- 기타 정보 필드 추가
ALTER TABLE receipts 
ADD COLUMN occasion_word VARCHAR(100) AFTER delivery_detail_time,
ADD COLUMN sender_name VARCHAR(100) AFTER occasion_word;

-- 배달 주소 필드 추가
ALTER TABLE receipts 
ADD COLUMN delivery_postcode VARCHAR(10) AFTER sender_name,
ADD COLUMN delivery_address VARCHAR(255) AFTER delivery_postcode,
ADD COLUMN delivery_detail_address VARCHAR(255) AFTER delivery_address,
ADD COLUMN delivery_request TEXT AFTER delivery_detail_address;

-- 인덱스 추가
ALTER TABLE receipts ADD INDEX idx_delivery_date (delivery_date);
