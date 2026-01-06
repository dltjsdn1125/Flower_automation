# 빠른 시작 가이드

## 🚀 개발 서버 실행 방법

### 방법 1: XAMPP 사용 (가장 쉬움) ⭐

1. **XAMPP 다운로드 및 설치**
   - https://www.apachefriends.org/download.html
   - Windows 버전 다운로드 및 설치

2. **XAMPP 시작**
   - XAMPP Control Panel 실행
   - Apache와 MySQL 시작

3. **프로젝트 복사**
   ```
   프로젝트 폴더를 C:\xampp\htdocs\flower-order 에 복사
   ```

4. **데이터베이스 설정**
   - 브라우저에서 http://localhost/phpmyadmin 접속
   - 좌측에서 "새로 만들기" 클릭
   - 데이터베이스 이름: `flower_order_system`
   - 인코딩: `utf8mb4_unicode_ci`
   - 만들기 클릭
   - 상단 "SQL" 탭 클릭
   - `sql/schema.sql` 파일 내용 복사하여 붙여넣기
   - 실행 버튼 클릭

5. **설정 파일 수정**
   - `config/database.php` 파일 열기
   - 비밀번호가 있다면 수정:
   ```php
   private $password = 'your_password'; // 기본값은 ''
   ```

6. **접속**
   - 브라우저에서 http://localhost/flower-order 접속
   - 로그인: admin / password

---

### 방법 2: PHP 직접 설치

1. **PHP 다운로드**
   - https://windows.php.net/download/
   - Thread Safe 버전 ZIP 파일 다운로드

2. **압축 해제**
   - 예: `C:\php` 폴더에 압축 해제

3. **환경 변수 추가**
   - 시스템 속성 > 환경 변수
   - Path에 `C:\php` 추가

4. **서버 실행**
   ```powershell
   php -S localhost:8000
   ```

5. **접속**
   - http://localhost:8000

---

### 방법 3: Chocolatey로 설치 (관리자 권한 필요)

관리자 권한 PowerShell에서:
```powershell
choco install php -y
```

설치 후:
```powershell
php -S localhost:8000
```

---

## 📋 체크리스트

- [ ] PHP 설치 또는 XAMPP 설치
- [ ] MySQL 설치 (XAMPP 포함)
- [ ] 데이터베이스 생성 (`sql/schema.sql` 실행)
- [ ] `config/database.php` 설정 확인
- [ ] 서버 실행
- [ ] 브라우저에서 접속 테스트

---

## ⚠️ 문제 해결

### PHP를 찾을 수 없는 경우
- 환경 변수 PATH에 PHP 경로가 추가되었는지 확인
- 새 터미널 창을 열어서 다시 시도

### 데이터베이스 연결 오류
- MySQL이 실행 중인지 확인
- `config/database.php`의 비밀번호 확인
- 데이터베이스가 생성되었는지 확인

### 포트가 사용 중인 경우
다른 포트 사용:
```powershell
php -S localhost:8080
```

---

## 🎯 다음 단계

1. XAMPP 설치 완료 후 알려주시면 데이터베이스 설정을 도와드리겠습니다.
2. 또는 PHP가 설치되었다면 서버를 바로 실행할 수 있습니다.
