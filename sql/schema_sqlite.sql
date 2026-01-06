-- 발주 관리 시스템 데이터베이스 스키마 (SQLite 버전)
-- SQLite 3.x

-- 관리자 테이블
CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 판매처 테이블
CREATE TABLE IF NOT EXISTS sales_channels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 화원사 테이블
CREATE TABLE IF NOT EXISTS flower_shops (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    contact VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 주문 테이블
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sales_channel_id) REFERENCES sales_channels(id),
    FOREIGN KEY (flower_shop_id) REFERENCES flower_shops(id),
    FOREIGN KEY (created_by) REFERENCES admins(id)
);

CREATE INDEX IF NOT EXISTS idx_order_date ON orders(order_date);
CREATE INDEX IF NOT EXISTS idx_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_created_at ON orders(created_at);

-- 인수증 템플릿 테이블 (즐겨찾기 기능)
CREATE TABLE IF NOT EXISTS receipt_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id)
);

CREATE INDEX IF NOT EXISTS idx_created_by ON receipt_templates(created_by);

-- 인수증 테이블
CREATE TABLE IF NOT EXISTS receipts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES receipt_templates(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_receipt_number ON receipts(receipt_number);
CREATE INDEX IF NOT EXISTS idx_order_id ON receipts(order_id);
CREATE INDEX IF NOT EXISTS idx_delivery_date ON receipts(delivery_date);
CREATE INDEX IF NOT EXISTS idx_receipt_created_at ON receipts(created_at);
CREATE INDEX IF NOT EXISTS idx_receipt_orderer_name ON receipts(orderer_name);

-- 기본 데이터 삽입
INSERT OR IGNORE INTO admins (username, password, name, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '관리자', 'admin@flower.com');

INSERT OR IGNORE INTO sales_channels (name, description) VALUES
('스토어팜', '온라인 쇼핑몰'),
('온라인몰', '웹사이트 주문'),
('오프라인', '매장 주문'),
('VIP', '프리미엄 주문');

INSERT OR IGNORE INTO flower_shops (name, contact) VALUES
('농원청년(힐라)', '010-1234-5678'),
('꽃나라', '010-2345-6789'),
('꽃마을', '010-3456-7890'),
('프리미엄플라워', '010-4567-8901');
