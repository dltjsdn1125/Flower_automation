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

## Vercel 배포

### 1. Vercel 계정 생성 및 로그인

1. [Vercel](https://vercel.com)에 접속
2. GitHub 계정으로 로그인

### 2. 프로젝트 배포

1. Vercel 대시보드에서 "Add New Project" 클릭
2. GitHub 저장소 선택
3. 프로젝트 설정:
   - **Framework Preset**: Other
   - **Root Directory**: `./` (기본값)
   - **Build Command**: (비워둠)
   - **Output Directory**: (비워둠)
4. 환경 변수 추가:
   - `SUPABASE_URL`: `https://jnpxwcmshukhkxdzicwv.supabase.co`
   - `SUPABASE_KEY`: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImpucHh3Y21zaHVraGt4ZHppY3d2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjcyMTQ0NzEsImV4cCI6MjA4Mjc5MDQ3MX0.C7ZXSR7t15qGShP8FhHlw0r7pLMYSDrmrR7ubb7ofOA`
5. "Deploy" 클릭

### 3. 배포 확인

- 배포가 완료되면 Vercel이 제공하는 URL로 접속
- 예: `https://your-project.vercel.app`

## 주의사항

### Vercel PHP 지원

Vercel은 PHP를 제한적으로 지원합니다. PHP 애플리케이션의 경우:

1. **@vercel/php** 런타임 사용 (vercel.json에 이미 설정됨)
2. 또는 다른 호스팅 서비스 고려:
   - **Railway**: PHP 완전 지원
   - **Render**: PHP 지원
   - **Heroku**: PHP 지원

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
