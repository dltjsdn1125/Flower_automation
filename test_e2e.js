const { chromium } = require('playwright');

const BASE_URL = 'http://localhost:8080';

async function runTests() {
    console.log('=== E2E 테스트 시작 ===\n');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    const errors = [];
    const successes = [];

    // 콘솔 에러 수집
    page.on('console', msg => {
        if (msg.type() === 'error') {
            errors.push(`[Console Error] ${msg.text()}`);
        }
    });

    // 네트워크 에러 수집
    page.on('response', response => {
        if (response.status() >= 400) {
            errors.push(`[HTTP ${response.status()}] ${response.url()}`);
        }
    });

    try {
        // 1. 로그인 페이지 테스트
        console.log('1. 로그인 페이지 테스트...');
        await page.goto(`${BASE_URL}/login.php`);
        await page.waitForLoadState('networkidle');

        const loginTitle = await page.title();
        if (loginTitle.includes('로그인')) {
            successes.push('로그인 페이지 로드 성공');
        }

        // 2. 로그인 시도
        console.log('2. 로그인 시도...');
        await page.fill('input[name="username"]', 'admin');
        await page.fill('input[name="password"]', 'admin123');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        const currentUrl = page.url();
        if (currentUrl.includes('index.php') || !currentUrl.includes('login.php')) {
            successes.push('로그인 성공');
        } else {
            errors.push('로그인 실패 - index.php로 리다이렉트 안됨');
        }

        // 3. 대시보드/메인 페이지 테스트
        console.log('3. 대시보드 테스트...');
        await page.goto(`${BASE_URL}/index.php`);
        await page.waitForLoadState('networkidle');
        successes.push('대시보드 페이지 로드');

        // 4. 주문 목록 테스트
        console.log('4. 주문 목록 테스트...');
        await page.goto(`${BASE_URL}/order_create.php`);
        await page.waitForLoadState('networkidle');
        successes.push('주문 생성 페이지 로드');

        // 5. 주문 편집 페이지 테스트
        console.log('5. 주문 편집 페이지 테스트...');
        await page.goto(`${BASE_URL}/order_edit.php`);
        await page.waitForLoadState('networkidle');
        successes.push('주문 편집 페이지 로드');

        // 6. 템플릿 페이지 테스트
        console.log('6. 템플릿 페이지 테스트...');
        await page.goto(`${BASE_URL}/template.php`);
        await page.waitForLoadState('networkidle');
        successes.push('템플릿 페이지 로드');

        // 7. 인수증 목록 테스트
        console.log('7. 인수증 목록 테스트...');
        await page.goto(`${BASE_URL}/receipt_list.php`);
        await page.waitForLoadState('networkidle');
        successes.push('인수증 목록 페이지 로드');

        // 8. 인수증 생성 테스트
        console.log('8. 인수증 생성 페이지 테스트...');
        await page.goto(`${BASE_URL}/receipt_create.php`);
        await page.waitForLoadState('networkidle');
        successes.push('인수증 생성 페이지 로드');

        // 9. 설정 페이지 테스트
        console.log('9. 설정 페이지 테스트...');
        await page.goto(`${BASE_URL}/settings.php`);
        await page.waitForLoadState('networkidle');
        successes.push('설정 페이지 로드');

        // 10. 고객 페이지 테스트
        console.log('10. 고객 페이지 테스트...');
        await page.goto(`${BASE_URL}/customer.php`);
        await page.waitForLoadState('networkidle');
        successes.push('고객 페이지 로드');

        // 11. API 테스트 - 주문 목록
        console.log('11. API 테스트 - 주문 목록...');
        const ordersResponse = await page.goto(`${BASE_URL}/api/orders.php`);
        if (ordersResponse.status() === 200) {
            successes.push('API: 주문 목록 조회 성공');
        }

        // 12. API 테스트 - 템플릿 목록
        console.log('12. API 테스트 - 템플릿 목록...');
        const templatesResponse = await page.goto(`${BASE_URL}/api/templates.php`);
        if (templatesResponse.status() === 200) {
            successes.push('API: 템플릿 목록 조회 성공');
        }

        // 13. 인수증 생성 기능 테스트 (receipts_bulk.php)
        console.log('13. 인수증 생성 API 테스트...');
        await page.goto(`${BASE_URL}/receipt_create.php`);
        await page.waitForLoadState('networkidle');

        // 인수증 생성 폼이 있는지 확인
        const createReceiptBtn = await page.$('button:has-text("생성"), button:has-text("인수증")');
        if (createReceiptBtn) {
            successes.push('인수증 생성 버튼 존재');
        }

        // 14. 버튼 클릭 테스트
        console.log('14. 페이지 버튼 테스트...');
        await page.goto(`${BASE_URL}/index.php`);
        await page.waitForLoadState('networkidle');

        const buttons = await page.$$('button, a.btn, [role="button"]');
        successes.push(`발견된 버튼 수: ${buttons.length}`);

    } catch (e) {
        errors.push(`[테스트 실행 오류] ${e.message}`);
    }

    await browser.close();

    // 결과 출력
    console.log('\n=== 테스트 결과 ===\n');

    console.log('성공 (' + successes.length + '):');
    successes.forEach(s => console.log('  ✓ ' + s));

    console.log('\n오류 (' + errors.length + '):');
    if (errors.length === 0) {
        console.log('  오류 없음!');
    } else {
        errors.forEach(e => console.log('  ✗ ' + e));
    }

    console.log('\n=== 테스트 완료 ===');

    return { successes, errors };
}

runTests().catch(console.error);
