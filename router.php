<?php
/**
 * PHP 내장 서버용 라우터
 * .htaccess 대신 사용
 */

$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// API 요청 처리
if (strpos($requestPath, '/api/') === 0) {
    $file = __DIR__ . $requestPath;
    if (file_exists($file)) {
        return false; // PHP가 직접 처리
    }
}

// 디렉토리 요청 시 index.php로 리다이렉트
if (is_dir(__DIR__ . $requestPath) && $requestPath !== '/') {
    $requestPath = rtrim($requestPath, '/') . '/index.php';
}

// 파일 경로 생성
$file = __DIR__ . $requestPath;

// 파일이 존재하면 서빙
if (file_exists($file) && is_file($file)) {
    return false; // PHP가 직접 처리
}

// index.php로 폴백
if (file_exists(__DIR__ . '/index.php')) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    include __DIR__ . '/index.php';
    return true;
}

// 404
http_response_code(404);
echo "404 - File not found";
return true;
