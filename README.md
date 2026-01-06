# 발주 관리 시스템

꽃 배송 발주 및 인수증 관리 시스템

## 기술 스택

- **Backend**: PHP 7.4+
- **Database**: Supabase (PostgreSQL)
- **Frontend**: Tailwind CSS, Vanilla JavaScript
- **Deployment**: Vercel

## 주요 기능

- 발주 관리 (생성, 수정, 조회)
- 인수증 관리 (복수 생성, 목록, 상태 변경)
- 템플릿 관리 (즐겨찾기 템플릿)
- 기본 코드 관리 (배송방법, 화분종류 등)
- 대시보드 (통계 및 현황)

## 환경 설정

### Supabase 설정

1. Supabase 프로젝트 생성
2. 환경 변수 설정:
   - `SUPABASE_URL`: Supabase 프로젝트 URL
   - `SUPABASE_KEY`: Supabase API Key

### 로컬 개발

```bash
# PHP 내장 서버 실행
php -S localhost:8000 -t .
```

### Vercel 배포

1. GitHub 저장소에 푸시
2. Vercel에 프로젝트 연결
3. 환경 변수 설정:
   - `SUPABASE_URL`
   - `SUPABASE_KEY`

## 데이터베이스 스키마

Supabase에서 다음 테이블들이 생성됩니다:

- `admins` - 관리자 계정
- `sales_channels` - 판매처
- `flower_shops` - 화원사
- `orders` - 주문
- `receipt_templates` - 인수증 템플릿
- `receipts` - 인수증
- `delivery_methods` - 배송방법 목록
- `order_statuses` - 주문 구분 목록
- `pot_sizes`, `pot_types`, `pot_colors` - 화분 정보 목록
- `plant_sizes`, `plant_types` - 식물 정보 목록
- `ribbons`, `policies`, `accessories` - 부자재 목록

## 기본 계정

- **Username**: admin
- **Password**: password (초기 설정 후 변경 권장)

## 라이선스

MIT
