<?php
/**
 * 데이터베이스 자동 설정 스크립트
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='utf-8'><title>데이터베이스 설정</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;padding:10px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:10px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:10px 0;}";
echo ".info{color:#0c5460;padding:10px;background:#d1ecf1;border:1px solid #bee5eb;border-radius:5px;margin:10px 0;}";
echo "pre{background:#f4f4f4;padding:15px;border-radius:5px;overflow-x:auto;}</style></head><body>";
echo "<h1>데이터베이스 자동 설정</h1>";

// MySQL 연결 정보
$host = 'localhost';
$username = 'root';
$password = '';

// 1. MySQL 서버 연결 (데이터베이스 없이)
try {
    $conn = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<div class='success'>✅ MySQL 서버 연결 성공!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ MySQL 서버 연결 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'><strong>해결 방법:</strong><br>";
    echo "1. XAMPP Control Panel에서 MySQL을 시작하세요<br>";
    echo "2. 또는 MySQL 서비스를 시작하세요<br>";
    echo "3. config/database.php에서 비밀번호가 있다면 설정하세요</div>";
    echo "</body></html>";
    exit;
}

// 2. 데이터베이스 생성
$dbName = 'flower_order_system';
try {
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='success'>✅ 데이터베이스 '$dbName' 생성 완료!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ 데이터베이스 생성 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</body></html>";
    exit;
}

// 3. 데이터베이스 선택
$conn->exec("USE `$dbName`");
echo "<div class='success'>✅ 데이터베이스 '$dbName' 선택 완료!</div>";

// 4. SQL 파일 읽기 및 실행
$sqlFile = __DIR__ . '/sql/schema.sql';
if (!file_exists($sqlFile)) {
    echo "<div class='error'>❌ SQL 파일을 찾을 수 없습니다: $sqlFile</div>";
    echo "</body></html>";
    exit;
}

$sql = file_get_contents($sqlFile);

// USE 문 제거 (이미 선택했으므로)
$sql = preg_replace('/USE\s+[^;]+;/i', '', $sql);

// 여러 쿼리로 분리
$queries = array_filter(array_map('trim', explode(';', $sql)));

$successCount = 0;
$errorCount = 0;

echo "<h2>테이블 생성 중...</h2>";

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query) || preg_match('/^--/', $query) || preg_match('/^\/\*/', $query)) {
        continue;
    }
    
    try {
        $conn->exec($query);
        if (preg_match('/CREATE TABLE/i', $query)) {
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $query, $matches)) {
                $tableName = $matches[1];
                echo "<div class='success'>✅ 테이블 '$tableName' 생성 완료</div>";
                $successCount++;
            }
        } elseif (preg_match('/INSERT INTO/i', $query)) {
            if (preg_match('/INSERT INTO.*?`?(\w+)`?/i', $query, $matches)) {
                $tableName = $matches[1];
                echo "<div class='info'>📝 '$tableName' 테이블에 기본 데이터 삽입</div>";
                $successCount++;
            }
        }
    } catch (PDOException $e) {
        $errorCount++;
        if (preg_match('/already exists/i', $e->getMessage())) {
            echo "<div class='info'>ℹ️ 테이블이 이미 존재합니다 (무시됨)</div>";
        } else {
            echo "<div class='error'>❌ 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

echo "<h2>설정 완료!</h2>";
echo "<div class='success'>✅ 성공: $successCount개 작업 완료</div>";
if ($errorCount > 0) {
    echo "<div class='error'>❌ 오류: $errorCount개</div>";
}

// 5. 테이블 목록 확인
echo "<h2>생성된 테이블:</h2>";
$stmt = $conn->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    echo "<div class='error'>❌ 테이블이 없습니다.</div>";
} else {
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
}

// 6. 관리자 계정 확인
echo "<h2>관리자 계정 확인:</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM admins");
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        echo "<div class='success'>✅ 관리자 계정이 존재합니다.</div>";
        echo "<div class='info'>기본 로그인 정보:<br>";
        echo "사용자명: <strong>admin</strong><br>";
        echo "비밀번호: <strong>password</strong></div>";
    } else {
        echo "<div class='error'>❌ 관리자 계정이 없습니다. sql/init_password.sql을 실행하세요.</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ 관리자 테이블 확인 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<p><a href='login.php' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>로그인 페이지로 이동</a></p>";
echo "</body></html>";
