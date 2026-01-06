<?php
/**
 * 공통 함수
 */

/**
 * 데이터베이스 연결 (캐싱으로 성능 개선)
 */
$GLOBALS['_db_connection'] = null;
function getDB() {
    if ($GLOBALS['_db_connection'] === null) {
        $database = new Database();
        $GLOBALS['_db_connection'] = $database->getConnection();
    }
    return $GLOBALS['_db_connection'];
}

/**
 * 관리자 로그인 확인
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * 로그인 필요 체크
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * XSS 방지
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * JSON 응답
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 성공 응답
 */
function successResponse($message, $data = null) {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * 실패 응답
 */
function errorResponse($message, $statusCode = 400) {
    jsonResponse([
        'success' => false,
        'message' => $message
    ], $statusCode);
}

/**
 * 주문 번호 생성
 */
function generateOrderNumber() {
    return 'ORD-' . date('Y-m-d') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * 인수증 번호 생성
 */
function generateReceiptNumber() {
    return 'RCP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * 날짜 포맷
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * 금액 포맷
 */
function formatCurrency($amount) {
    return number_format($amount) . '원';
}
