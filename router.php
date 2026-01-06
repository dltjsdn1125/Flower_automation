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

// 루트 경로 처리
if ($requestPath === '/' || $requestPath === '') {
    $requestPath = '/index.php';
}

// 정적 파일 처리 (CSS, JS, 이미지 등)
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
$extension = pathinfo($requestPath, PATHINFO_EXTENSION);
if (in_array(strtolower($extension), $staticExtensions)) {
    $file = __DIR__ . $requestPath;
    if (file_exists($file) && is_file($file)) {
        return false; // PHP가 직접 처리
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
        return false; // PHP가 직접 처리
    }
}

// 디렉토리 요청 시 index.php로 리다이렉트
$fullPath = __DIR__ . $requestPath;
if (is_dir($fullPath) && $requestPath !== '/') {
    $indexFile = $fullPath . '/index.php';
    if (file_exists($indexFile)) {
        $_SERVER['SCRIPT_NAME'] = $requestPath . '/index.php';
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
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>404 - Not Found</title></head><body><h1>404 - File not found</h1><p>The requested file was not found on this server.</p></body></html>";
return true;
