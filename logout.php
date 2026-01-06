<?php
/**
 * 로그아웃 처리
 */

require_once __DIR__ . '/config/config.php';

// 세션 데이터 모두 제거
$_SESSION = array();

// 세션 쿠키 삭제
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// 세션 파괴
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// 로그인 페이지로 리다이렉트
header('Location: /login.php');
exit;
