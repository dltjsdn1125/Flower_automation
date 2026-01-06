# 개발 서버 실행 가이드

## 방법 1: XAMPP 사용 (권장)

### 1. XAMPP 설치
1. https://www.apachefriends.org/ 에서 XAMPP 다운로드
2. 설치 후 Apache와 MySQL 시작

### 2. 프로젝트 설정
1. 프로젝트 폴더를 `C:\xampp\htdocs\flower-order`에 복사
2. 또는 심볼릭 링크 생성

### 3. 데이터베이스 설정
1. phpMyAdmin 접속: http://localhost/phpmyadmin
2. `sql/schema.sql` 파일을 import
3. `config/database.php`에서 데이터베이스 정보 확인

### 4. 서버 접속
- http://localhost/flower-order 접속

---

## 방법 2: PHP 내장 서버 사용

### 1. PHP 설치 확인
```powershell
php --version
```

### 2. 서버 실행
프로젝트 루트 디렉토리에서:

**PowerShell:**
```powershell
.\start_server.ps1
```

**또는 직접 실행:**
```powershell
php -S localhost:8000
```

**CMD:**
```cmd
start_server.bat
```

### 3. 브라우저 접속
- http://localhost:8000

---

## 방법 3: Docker 사용

### 1. Dockerfile 생성 (선택사항)
```dockerfile
FROM php:8.2-apache
COPY . /var/www/html/
RUN docker-php-ext-install pdo pdo_mysql
```

### 2. docker-compose.yml 생성
```yaml
version: '3.8'
services:
  web:
    build: .
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - db
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: flower_order_system
    ports:
      - "3306:3306"
```

### 3. 실행
```bash
docker-compose up
```

---

## 데이터베이스 설정

### 1. MySQL 접속
```bash
mysql -u root -p
```

### 2. 데이터베이스 생성
```sql
source sql/schema.sql
```

### 3. 설정 파일 수정
`config/database.php` 파일을 열어 다음 정보 수정:
```php
private $host = 'localhost';
private $db_name = 'flower_order_system';
private $username = 'root';
private $password = 'your_password';
```

---

## 기본 계정

- 사용자명: `admin`
- 비밀번호: `password`

**⚠️ 보안을 위해 로그인 후 비밀번호를 변경하세요!**

---

## 문제 해결

### PHP를 찾을 수 없는 경우
1. PHP가 PATH에 추가되었는지 확인
2. XAMPP/WAMP를 사용하는 경우 해당 경로 확인
3. `start_server.ps1` 또는 `start_server.bat` 파일에서 PHP 경로 수정

### 데이터베이스 연결 오류
1. MySQL이 실행 중인지 확인
2. `config/database.php`의 연결 정보 확인
3. 데이터베이스가 생성되었는지 확인

### 포트가 이미 사용 중인 경우
다른 포트 사용:
```powershell
php -S localhost:8080
```
