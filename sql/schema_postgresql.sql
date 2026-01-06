-- 발주 관리 시스템 데이터베이스 스키마 (PostgreSQL/Supabase 버전)

-- 관리자 테이블
CREATE TABLE IF NOT EXISTS admins (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 판매처 테이블
CREATE TABLE IF NOT EXISTS sales_channels (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 화원사 테이블
CREATE TABLE IF NOT EXISTS flower_shops (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    contact VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 주문 테이블
CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    order_date DATE NOT NULL,
    sales_channel_id INTEGER NOT NULL,
    flower_shop_id INTEGER NOT NULL,
    manager_name VARCHAR(100) NOT NULL,
    delivery_method VARCHAR(50) NOT NULL,
    pot_size VARCHAR(50),
    pot_type VARCHAR(50),
    pot_color VARCHAR(50),
    plant_size VARCHAR(50),
    plant_type VARCHAR(100),
    ribbon VARCHAR(50),
    policy VARCHAR(50),
    accessories TEXT,
    status VARCHAR(50) NOT NULL DEFAULT '신규',
    delivery_fee INTEGER DEFAULT 0,
    sales_amount INTEGER NOT NULL,
    order_amount INTEGER NOT NULL,
    created_by INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sales_channel_id) REFERENCES sales_channels(id),
    FOREIGN KEY (flower_shop_id) REFERENCES flower_shops(id),
    FOREIGN KEY (created_by) REFERENCES admins(id)
);

CREATE INDEX IF NOT EXISTS idx_order_date ON orders(order_date);
CREATE INDEX IF NOT EXISTS idx_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_created_at ON orders(created_at);

-- 인수증 템플릿 테이블 (즐겨찾기 기능)
CREATE TABLE IF NOT EXISTS receipt_templates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    delivery_method VARCHAR(50),
    pot_size VARCHAR(50),
    pot_type VARCHAR(50),
    pot_color VARCHAR(50),
    plant_size VARCHAR(50),
    plant_type VARCHAR(100),
    ribbon VARCHAR(50),
    policy VARCHAR(50),
    accessories TEXT,
    created_by INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id)
);

CREATE INDEX IF NOT EXISTS idx_created_by ON receipt_templates(created_by);

-- 인수증 테이블
CREATE TABLE IF NOT EXISTS receipts (
    id SERIAL PRIMARY KEY,
    receipt_number VARCHAR(50) NOT NULL UNIQUE,
    order_id INTEGER,
    template_id INTEGER,
    -- 주문자 정보
    orderer_name VARCHAR(100),
    orderer_phone1 VARCHAR(20),
    orderer_phone2 VARCHAR(20),
    -- 수취인 정보
    recipient_name VARCHAR(100),
    recipient_phone1 VARCHAR(20),
    recipient_phone2 VARCHAR(20),
    -- 배달 정보
    delivery_date DATE,
    delivery_time VARCHAR(50),
    delivery_detail_time VARCHAR(50),
    -- 기타 정보
    occasion_word VARCHAR(100),
    sender_name VARCHAR(100),
    delivery_postcode VARCHAR(10),
    delivery_address VARCHAR(255),
    delivery_detail_address VARCHAR(255),
    delivery_request TEXT,
    status VARCHAR(50) DEFAULT '대기',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES receipt_templates(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_receipt_number ON receipts(receipt_number);
CREATE INDEX IF NOT EXISTS idx_order_id ON receipts(order_id);
CREATE INDEX IF NOT EXISTS idx_delivery_date ON receipts(delivery_date);
CREATE INDEX IF NOT EXISTS idx_receipt_created_at ON receipts(created_at);
CREATE INDEX IF NOT EXISTS idx_receipt_orderer_name ON receipts(orderer_name);

-- 배송방법 목록
CREATE TABLE IF NOT EXISTS delivery_methods (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 주문 구분 목록
CREATE TABLE IF NOT EXISTS order_statuses (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 화분사이즈 목록
CREATE TABLE IF NOT EXISTS pot_sizes (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 화분종류 목록
CREATE TABLE IF NOT EXISTS pot_types (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 화분색상 목록
CREATE TABLE IF NOT EXISTS pot_colors (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 식물사이즈 목록
CREATE TABLE IF NOT EXISTS plant_sizes (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 식물종류 목록
CREATE TABLE IF NOT EXISTS plant_types (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 리본 목록
CREATE TABLE IF NOT EXISTS ribbons (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 받침 목록
CREATE TABLE IF NOT EXISTS policies (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 부자재 목록
CREATE TABLE IF NOT EXISTS accessories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- updated_at 자동 업데이트 트리거 함수
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- updated_at 트리거 생성
CREATE TRIGGER update_admins_updated_at BEFORE UPDATE ON admins
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_orders_updated_at BEFORE UPDATE ON orders
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_receipt_templates_updated_at BEFORE UPDATE ON receipt_templates
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
