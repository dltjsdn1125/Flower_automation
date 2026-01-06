# 발주 관리 시스템

PHP/MySQL 기반의 꽃 배달 주문서 관리 시스템입니다.

## 주요 기능

1. **인수증 작성 즐겨찾기 기능**
   - 관리자가 템플릿 생성 (최대 10개)
   - 템플릿을 이용한 빠른 주문서 작성

2. **인수증 복수 생성 기능**
   - 수량 선택하여 여러 인수증 일괄 생성
   - 인수증 번호 자동 채번

3. **상태 변경 프론트 리스트 기능**
   - 복수 선택 후 일괄 상태 변경
   - 신규도착완료, 동일배송도착완료 지원

## 설치 방법

### 1. 데이터베이스 설정

```bash
# MySQL에 접속하여 데이터베이스 생성
mysql -u root -p < sql/schema.sql
```

### 2. 데이터베이스 연결 설정

`config/database.php` 파일을 열어 데이터베이스 정보를 수정하세요:

```php
private $host = 'localhost';
private $db_name = 'flower_order_system';
private $username = 'root';
private $password = '';
```

### 3. 웹 서버 설정

#### Apache
`.htaccess` 파일이 필요합니다 (프로젝트 루트에 생성).

#### Nginx
다음과 같은 설정을 추가하세요:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 4. 디렉토리 권한 설정

```bash
mkdir -p logs
chmod 755 logs
```

## 기본 관리자 계정

- 사용자명: `admin`
- 비밀번호: `password` (초기 비밀번호, 로그인 후 변경 권장)

## API 엔드포인트

### 주문 관리
- `GET /api/orders.php` - 주문 목록 조회
- `POST /api/orders.php` - 주문 생성
- `PUT /api/orders.php` - 주문 수정
- `DELETE /api/orders.php` - 주문 삭제

### 일괄 상태 변경
- `POST /api/orders_bulk_status.php` - 복수 주문 상태 변경

### 인수증 복수 생성
- `POST /api/receipts_bulk.php` - 인수증 일괄 생성

### 템플릿 관리
- `GET /api/templates.php` - 템플릿 목록
- `POST /api/templates.php` - 템플릿 생성
- `PUT /api/templates.php` - 템플릿 수정
- `DELETE /api/templates.php` - 템플릿 삭제

## 프로젝트 구조

```
.
├── config/          # 설정 파일
│   ├── config.php
│   └── database.php
├── includes/        # 공통 파일
│   ├── functions.php
│   ├── header.php
│   └── footer.php
├── api/            # API 엔드포인트
│   ├── orders.php
│   ├── orders_bulk_status.php
│   ├── receipts_bulk.php
│   └── templates.php
├── sql/            # 데이터베이스 스키마
│   └── schema.sql
├── logs/           # 로그 파일 (생성 필요)
├── index.php       # 메인 대시보드
└── login.php       # 로그인 페이지
```

## 요구사항

- PHP 7.4 이상
- MySQL 8.0 이상
- Apache/Nginx 웹 서버
- PDO 확장 모듈

## 보안 고려사항

1. 프로덕션 환경에서는 `config/config.php`의 에러 표시를 비활성화하세요.
2. 데이터베이스 비밀번호를 강력하게 설정하세요.
3. 세션 보안 설정을 확인하세요.
4. SQL 인젝션 방지를 위해 PDO Prepared Statements를 사용합니다.

## 라이선스

이 프로젝트는 내부 사용을 위한 것입니다.
