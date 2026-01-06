<?php
/**
 * 데이터베이스 연결 테스트
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>데이터베이스 연결 테스트</h1>";

// PDO 확장 확인
if (!extension_loaded('pdo')) {
    echo "<p style='color:red;'>❌ PDO 확장이 설치되어 있지 않습니다.</p>";
    exit;
}

if (!extension_loaded('pdo_mysql')) {
    echo "<p style='color:red;'>❌ PDO MySQL 확장이 설치되어 있지 않습니다.</p>";
    echo "<p>php.ini에서 extension=pdo_mysql을 활성화하세요.</p>";
    exit;
}

echo "<p style='color:green;'>✅ PDO 및 PDO_MySQL 확장이 설치되어 있습니다.</p>";

// 데이터베이스 연결 테스트
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p style='color:green;'>✅ 데이터베이스 연결 성공!</p>";
    
    // 테이블 확인
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>데이터베이스 테이블:</h2>";
    if (empty($tables)) {
        echo "<p style='color:orange;'>⚠️ 테이블이 없습니다. sql/schema.sql을 실행하세요.</p>";
    } else {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ 데이터베이스 연결 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h2>해결 방법:</h2>";
    echo "<ol>";
    echo "<li>MySQL이 실행 중인지 확인하세요.</li>";
    echo "<li>config/database.php에서 데이터베이스 정보를 확인하세요.</li>";
    echo "<li>데이터베이스가 생성되었는지 확인하세요: <code>sql/schema.sql</code> 실행</li>";
    echo "</ol>";
}
