# 배포 가이드

## GitHub에 푸시

### 1. GitHub 저장소 생성

1. [GitHub](https://github.com)에 로그인
2. "New repository" 클릭
3. 저장소 이름 입력 (예: `flower-order-system`)
4. "Create repository" 클릭

### 2. 로컬 저장소에 원격 추가 및 푸시

```bash
# 원격 저장소 추가 (YOUR_USERNAME과 YOUR_REPO_NAME을 실제 값으로 변경)
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git

# 브랜치 이름을 main으로 변경
git branch -M main

# GitHub에 푸시
git push -u origin main
```

## Railway 배포 (권장)

Railway는 PHP를 완전히 지원하며 설정이 간단합니다.

### 1. Railway 계정 생성 및 로그인

1. [Railway](https://railway.app)에 접속
2. GitHub 계정으로 로그인

### 2. 프로젝트 배포

1. Railway 대시보드에서 "New Project" 클릭
2. "Deploy from GitHub repo" 선택
3. `dltjsdn1125/Flower_automation` 저장소 선택
4. Railway가 자동으로 PHP를 감지하고 배포 시작
5. 환경 변수 추가 (Settings > Variables):
   - `SUPABASE_URL`: `https://jnpxwcmshukhkxdzicwv.supabase.co`
   - `SUPABASE_KEY`: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImpucHh3Y21zaHVraGt4ZHppY3d2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjcyMTQ0NzEsImV4cCI6MjA4Mjc5MDQ3MX0.C7ZXSR7t15qGShP8FhHlw0r7pLMYSDrmrR7ubb7ofOA`

### 3. 배포 확인

- 배포가 완료되면 Railway가 제공하는 URL로 접속
- 예: `https://your-project.up.railway.app`

## Vercel 배포 (PHP 미지원)

**참고**: Vercel은 PHP를 네이티브로 지원하지 않습니다. PHP 애플리케이션은 Railway나 Render를 사용하는 것을 권장합니다.

### 대안 호스팅 서비스

1. **Railway** (권장): PHP 완전 지원, 무료 티어 제공
2. **Render**: PHP 지원, 무료 티어 제공
3. **Heroku**: PHP 지원 (유료)

### Supabase 쿼리 변환

현재 Supabase 쿼리 파서는 기본적인 SELECT 쿼리만 지원합니다. 복잡한 쿼리(JOIN, 서브쿼리 등)는:

1. Supabase PostgREST API로 직접 변환
2. 또는 Supabase의 SQL 실행 기능 사용

## 문제 해결

### 배포 실패 시

1. Vercel 로그 확인
2. 환경 변수 확인
3. `vercel.json` 설정 확인

### 데이터베이스 연결 오류

1. Supabase 프로젝트 상태 확인
2. API 키 확인
3. 네트워크 연결 확인
