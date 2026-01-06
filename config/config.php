<?php
/**
 * 시스템 설정 파일
 */

// 에러 리포팅 설정 (성능 최적화: 디렉토리 체크 최소화)
error_reporting(E_ALL);
ini_set('display_errors', 0); // 프로덕션에서는 에러 표시 비활성화
ini_set('log_errors', 1);
static $logDirInitialized = false;
if (!$logDirInitialized) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    ini_set('error_log', $logDir . '/error.log');
    $logDirInitialized = true;
}

// 세션 설정
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// 타임존 설정
date_default_timezone_set('Asia/Seoul');

// 기본 경로 설정 (캐싱으로 성능 개선)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('BASE_URL')) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
    $baseUrl = dirname($scriptName);
    if ($baseUrl === '/' || $baseUrl === '\\') {
        $baseUrl = '';
    }
    define('BASE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000') . $baseUrl);
}

// 출력 버퍼링 활성화 (성능 개선)
if (!ob_get_level()) {
    ob_start();
}

// 세션 시작 (성능 최적화: 한 번만 실행)
if (session_status() === PHP_SESSION_NONE) {
    // 세션 쿠키 설정 최적화
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.gc_maxlifetime', 3600);
    session_start();
}

// 자동 로드 설정 (Supabase 사용)
require_once BASE_PATH . '/config/database_supabase.php';
require_once BASE_PATH . '/includes/functions.php';
