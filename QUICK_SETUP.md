# 빠른 설정 가이드

## 1단계: MySQL 시작

### 방법 A: XAMPP 사용 (권장)
1. XAMPP Control Panel 실행
   - 시작 메뉴에서 "XAMPP Control Panel" 검색
   - 또는 `C:\xampp\xampp-control.exe` 실행
2. MySQL 옆의 "Start" 버튼 클릭
3. MySQL이 녹색으로 표시되면 성공!

### 방법 B: MySQL 서비스 직접 시작
1. PowerShell을 관리자 권한으로 실행
2. 다음 명령 실행:
   ```powershell
   Get-Service | Where-Object {$_.Name -like "*mysql*"}
   Start-Service -Name "MySQL80"  # 서비스 이름은 다를 수 있음
   ```

## 2단계: 데이터베이스 자동 생성

MySQL이 시작되면:

1. 브라우저에서 다음 주소 열기:
   ```
   http://localhost:8000/setup_database.php
   ```

2. 페이지가 자동으로:
   - 데이터베이스 생성
   - 모든 테이블 생성
   - 기본 데이터 삽입

3. 완료 메시지가 표시되면 성공!

## 3단계: 로그인

기본 로그인 정보:
- **사용자명**: `admin`
- **비밀번호**: `password`

로그인 페이지: http://localhost:8000/login.php

## 문제 해결

### MySQL이 시작되지 않을 때
- 포트 3306이 이미 사용 중일 수 있습니다
- 다른 MySQL 인스턴스가 실행 중일 수 있습니다
- XAMPP를 재시작해보세요

### 데이터베이스 생성 실패 시
- MySQL이 정상적으로 실행 중인지 확인
- `config/database.php`에서 비밀번호 확인
- 에러 메시지를 확인하고 알려주세요
