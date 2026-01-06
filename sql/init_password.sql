-- 초기 관리자 비밀번호 설정
-- 기본 비밀번호: password
-- 로그인 후 반드시 변경하세요!

USE flower_order_system;

UPDATE admins 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE username = 'admin';

-- 비밀번호 변경 예시 (새 비밀번호: newpassword123)
-- UPDATE admins 
-- SET password = '$2y$10$YourHashedPasswordHere' 
-- WHERE username = 'admin';
