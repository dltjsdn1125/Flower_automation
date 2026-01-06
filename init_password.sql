-- 기본 관리자 계정 비밀번호 설정
-- 비밀번호: password

USE flower_order_system;

-- 기존 관리자 계정이 없으면 생성
INSERT INTO admins (username, password, name, email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '관리자', 'admin@flower.com')
ON DUPLICATE KEY UPDATE 
    password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    name = '관리자',
    email = 'admin@flower.com';
