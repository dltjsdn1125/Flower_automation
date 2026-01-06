# Railway 배포 URL 확인 방법

## 방법 1: 프로젝트 대시보드에서 확인

1. [Railway 대시보드](https://railway.app)에 로그인
2. `Flower_automation` 프로젝트 클릭
3. **Settings** 탭 클릭
4. 왼쪽 메뉴에서 **Networking** 클릭
5. **Public Domain** 섹션에서 URL 확인
   - 예: `https://flower-automation-production.up.railway.app`

## 방법 2: 서비스 페이지에서 확인

1. 프로젝트 대시보드에서
2. 배포된 **Service** 클릭
3. 상단에 **Public URL** 또는 **Domain** 표시됨

## 방법 3: Deployments 탭에서 확인

1. 프로젝트 대시보드에서
2. **Deployments** 탭 클릭
3. 최신 배포 클릭
4. **View Logs** 또는 상세 정보에서 URL 확인

## 일반적인 Railway URL 형식

- `https://[프로젝트명]-production.up.railway.app`
- `https://[랜덤문자]-[프로젝트명].up.railway.app`
- `https://[서비스명].up.railway.app`

## URL이 보이지 않는 경우

1. **공개 도메인 생성**:
   - Settings > Networking > Generate Domain 클릭
   
2. **배포 상태 확인**:
   - Deployments 탭에서 배포가 성공했는지 확인
   - 빌드 로그 확인

3. **환경 변수 확인**:
   - Settings > Variables에서 `SUPABASE_URL`, `SUPABASE_KEY` 설정 확인
