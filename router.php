<?php
/**
 * PHP 내장 서버용 라우터
 * .htaccess 대신 사용
 */

$requestUri = $_SERVER['REQUEST_URI'];
$parsed = parse_url($requestUri);
$requestPath = isset($parsed['path']) ? $parsed['path'] : '/';

// 쿼리 문자열 유지
if (isset($parsed['query'])) {
    $_SERVER['QUERY_STRING'] = $parsed['query'];
    parse_str($parsed['query'], $_GET);
} else {
    $_SERVER['QUERY_STRING'] = '';
}

// favicon.ico 요청 처리 (파일이 없으면 빈 응답)
if ($requestPath === '/favicon.ico') {
    $faviconFile = __DIR__ . '/favicon.ico';
    if (file_exists($faviconFile)) {
        return false; // PHP가 직접 처리
    } else {
        // favicon이 없으면 204 No Content 반환
        http_response_code(204);
        return true;
    }
}

// 정적 파일 처리 (CSS, JS, 이미지 등)
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
$extension = pathinfo($requestPath, PATHINFO_EXTENSION);
if (in_array(strtolower($extension), $staticExtensions)) {
    $file = __DIR__ . $requestPath;
    if (file_exists($file) && is_file($file)) {
        return false; // PHP가 직접 처리
    }
    // 정적 파일이 없으면 404 반환하지 않고 무시 (브라우저가 자동으로 처리)
    if ($requestPath !== '/favicon.ico') {
        http_response_code(204);
        return true;
    }
}

// API 요청 처리
if (strpos($requestPath, '/api/') === 0) {
    $file = __DIR__ . $requestPath;
    if (file_exists($file) && is_file($file)) {
        return false; // PHP가 직접 처리
    }
}

// PHP 파일 직접 요청
if (preg_match('/\.php$/', $requestPath)) {
    $file = __DIR__ . $requestPath;
    if (file_exists($file) && is_file($file)) {
        $_SERVER['SCRIPT_NAME'] = $requestPath;
        return false; // PHP가 직접 처리
    }
}

// 루트 경로 또는 디렉토리 요청 처리
if ($requestPath === '/' || $requestPath === '' || (is_dir(__DIR__ . $requestPath) && $requestPath !== '/')) {
    $indexFile = __DIR__ . ($requestPath === '/' || $requestPath === '' ? '/index.php' : $requestPath . '/index.php');
    if (file_exists($indexFile)) {
        $_SERVER['SCRIPT_NAME'] = ($requestPath === '/' || $requestPath === '' ? '/index.php' : $requestPath . '/index.php');
        include $indexFile;
        return true;
    }
}

// index.php로 폴백
if (file_exists(__DIR__ . '/index.php')) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    include __DIR__ . '/index.php';
    return true;
}

// 404
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html lang='ko'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>404 - Not Found</title><style>body{font-family:sans-serif;text-align:center;padding-top:50px;}h1{font-size:50px;}p{font-size:20px;}</style></head><body><h1>404 - Not Found</h1><p>요청하신 페이지를 찾을 수 없습니다.</p><a href='/'>홈으로 돌아가기</a></body></html>";
return true;
