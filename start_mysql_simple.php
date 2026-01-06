<?php
/**
 * MySQL 시작 시도 및 데이터베이스 생성
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='utf-8'><title>MySQL 시작 및 데이터베이스 설정</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;padding:10px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:10px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:10px 0;}";
echo ".info{color:#0c5460;padding:10px;background:#d1ecf1;border:1px solid #bee5eb;border-radius:5px;margin:10px 0;}";
echo ".warning{color:#856404;padding:10px;background:#fff3cd;border:1px solid #ffeaa7;border-radius:5px;margin:10px 0;}";
echo "pre{background:#f4f4f4;padding:15px;border-radius:5px;overflow-x:auto;}</style></head><body>";
echo "<h1>MySQL 시작 및 데이터베이스 설정</h1>";

// MySQL 연결 정보
$host = 'localhost';
$username = 'root';
$password = '';

echo "<h2>1단계: MySQL 서버 연결 확인</h2>";

// 여러 포트 시도
$ports = [3306, 3307, 3308];
$connected = false;
$conn = null;

foreach ($ports as $port) {
    try {
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $conn = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2
        ]);
        echo "<div class='success'>✅ MySQL 서버 연결 성공! (포트: $port)</div>";
        $connected = true;
        break;
    } catch (PDOException $e) {
        // 연결 실패는 계속 시도
        continue;
    }
}

if (!$connected) {
    echo "<div class='error'>❌ MySQL 서버에 연결할 수 없습니다.</div>";
    echo "<div class='warning'><strong>MySQL을 시작하는 방법:</strong><br>";
    echo "<ol>";
    echo "<li><strong>XAMPP 사용:</strong><br>";
    echo "   - XAMPP Control Panel 실행 (C:\\xampp\\xampp-control.exe)<br>";
    echo "   - MySQL 옆의 'Start' 버튼 클릭</li>";
    echo "<li><strong>Windows 서비스:</strong><br>";
    echo "   - PowerShell을 관리자 권한으로 실행<br>";
    echo "   - <code>Get-Service | Where-Object {\$_.Name -like '*mysql*'}</code> 실행<br>";
    echo "   - <code>Start-Service -Name 'MySQL80'</code> 실행 (서비스 이름은 다를 수 있음)</li>";
    echo "<li><strong>수동 시작:</strong><br>";
    echo "   - MySQL 설치 경로에서 mysqld.exe 실행</li>";
    echo "</ol>";
    echo "</div>";
    echo "<div class='info'><strong>참고:</strong> MySQL이 시작되면 이 페이지를 새로고침하세요.</div>";
    echo "</body></html>";
    exit;
}

// 2. 데이터베이스 생성
$dbName = 'flower_order_system';
echo "<h2>2단계: 데이터베이스 생성</h2>";

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

echo "<h2>3단계: 테이블 생성</h2>";

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
    echo "<div class='warning'>⚠️ 일부 오류 발생: $errorCount개 (이미 존재하는 항목일 수 있음)</div>";
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
echo "<h2>관리자 계정:</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM admins");
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        echo "<div class='success'>✅ 관리자 계정이 존재합니다.</div>";
        echo "<div class='info'><strong>기본 로그인 정보:</strong><br>";
        echo "사용자명: <strong>admin</strong><br>";
        echo "비밀번호: <strong>password</strong></div>";
    } else {
        echo "<div class='warning'>⚠️ 관리자 계정이 없습니다. 생성 중...</div>";
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admins (username, password, name, email) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $hashedPassword, '관리자', 'admin@flower.com']);
        echo "<div class='success'>✅ 관리자 계정 생성 완료!</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ 관리자 테이블 확인 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<p><a href='login.php' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;display:inline-block;margin:10px 5px;'>로그인 페이지로 이동</a></p>";
echo "<p><a href='setup_database.php' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;display:inline-block;margin:10px 5px;'>다시 확인</a></p>";
echo "</body></html>";
