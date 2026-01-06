<?php
/**
 * SQLite 데이터베이스 자동 설정 스크립트
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='utf-8'><title>데이터베이스 설정 (SQLite)</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;padding:10px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:10px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:10px 0;}";
echo ".info{color:#0c5460;padding:10px;background:#d1ecf1;border:1px solid #bee5eb;border-radius:5px;margin:10px 0;}";
echo "pre{background:#f4f4f4;padding:15px;border-radius:5px;overflow-x:auto;}</style></head><body>";
echo "<h1>데이터베이스 자동 설정 (SQLite)</h1>";

// SQLite 데이터베이스 파일 경로
$dbDir = __DIR__ . '/data';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
    echo "<div class='success'>✅ 데이터 디렉토리 생성: $dbDir</div>";
}

$dbFile = $dbDir . '/flower_order_system.db';

// 1. SQLite 연결
try {
    $conn = new PDO("sqlite:" . $dbFile, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $conn->exec("PRAGMA foreign_keys = ON");
    echo "<div class='success'>✅ SQLite 데이터베이스 연결 성공!</div>";
    echo "<div class='info'>데이터베이스 파일: $dbFile</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ SQLite 연결 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</body></html>";
    exit;
}

// 2. SQL 파일 읽기 및 실행
$sqlFile = __DIR__ . '/sql/schema_sqlite.sql';
if (!file_exists($sqlFile)) {
    echo "<div class='error'>❌ SQL 파일을 찾을 수 없습니다: $sqlFile</div>";
    echo "</body></html>";
    exit;
}

$sql = file_get_contents($sqlFile);

// 주석 제거
$sql = preg_replace('/--.*$/m', '', $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

// 여러 쿼리로 분리 (세미콜론 기준)
$queries = [];
$currentQuery = '';
$lines = explode("\n", $sql);

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || preg_match('/^--/', $line)) {
        continue;
    }
    $currentQuery .= $line . "\n";
    if (substr(rtrim($line), -1) === ';') {
        $query = trim($currentQuery);
        if (!empty($query) && strlen($query) > 10) {
            $queries[] = $query;
        }
        $currentQuery = '';
    }
}

$successCount = 0;
$errorCount = 0;

echo "<h2>테이블 생성 중...</h2>";

// CREATE TABLE 쿼리만 먼저 실행
foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query) || strlen($query) < 10) {
        continue;
    }
    
    // CREATE TABLE만 먼저 실행
    if (preg_match('/CREATE\s+TABLE/i', $query)) {
        try {
            $conn->exec($query);
            if (preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?(\w+)`?/i', $query, $matches)) {
                $tableName = $matches[1];
            } elseif (preg_match('/CREATE\s+TABLE\s+`?(\w+)`?/i', $query, $matches)) {
                $tableName = $matches[1];
            } else {
                $tableName = 'unknown';
            }
            echo "<div class='success'>✅ 테이블 '$tableName' 생성 완료</div>";
            $successCount++;
        } catch (PDOException $e) {
            $errorCount++;
            if (preg_match('/already exists/i', $e->getMessage()) || preg_match('/duplicate/i', $e->getMessage())) {
                echo "<div class='info'>ℹ️ 테이블이 이미 존재합니다 (무시됨)</div>";
            } else {
                echo "<div class='error'>❌ 테이블 생성 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
                echo "<div class='info'><pre>" . htmlspecialchars(substr($query, 0, 300)) . "...</pre></div>";
            }
        }
    }
}

// CREATE INDEX 실행
foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query) || preg_match('/^--/', $query) || preg_match('/^\/\*/', $query)) {
        continue;
    }
    
    if (preg_match('/CREATE INDEX/i', $query)) {
        try {
            $conn->exec($query);
            if (preg_match('/CREATE INDEX.*?IF NOT EXISTS.*?`?(\w+)`?/i', $query, $matches)) {
                $indexName = $matches[1];
            } elseif (preg_match('/CREATE INDEX.*?`?(\w+)`?/i', $query, $matches)) {
                $indexName = $matches[1];
            } else {
                $indexName = 'unknown';
            }
            echo "<div class='info'>📝 인덱스 '$indexName' 생성</div>";
            $successCount++;
        } catch (PDOException $e) {
            if (preg_match('/already exists/i', $e->getMessage()) || preg_match('/duplicate/i', $e->getMessage())) {
                // 인덱스는 이미 존재해도 무시
            } else {
                echo "<div class='error'>❌ 인덱스 생성 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}

// INSERT 쿼리 실행
foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query) || preg_match('/^--/', $query) || preg_match('/^\/\*/', $query)) {
        continue;
    }
    
    if (preg_match('/INSERT/i', $query)) {
        try {
            $conn->exec($query);
            if (preg_match('/INSERT.*?INTO.*?`?(\w+)`?/i', $query, $matches)) {
                $tableName = $matches[1];
                echo "<div class='info'>📝 '$tableName' 테이블에 기본 데이터 삽입</div>";
                $successCount++;
            }
        } catch (PDOException $e) {
            $errorCount++;
            if (preg_match('/UNIQUE constraint failed/i', $e->getMessage()) || preg_match('/duplicate/i', $e->getMessage())) {
                echo "<div class='info'>ℹ️ 데이터가 이미 존재합니다 (무시됨)</div>";
            } else {
                echo "<div class='error'>❌ 데이터 삽입 오류: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}

echo "<h2>설정 완료!</h2>";
echo "<div class='success'>✅ 성공: $successCount개 작업 완료</div>";
if ($errorCount > 0) {
    echo "<div class='info'>ℹ️ 일부 항목은 이미 존재했습니다: $errorCount개</div>";
}

// 3. 테이블 목록 확인
echo "<h2>생성된 테이블:</h2>";
$stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
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

// 4. 관리자 계정 확인
echo "<h2>관리자 계정 확인:</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM admins");
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        echo "<div class='success'>✅ 관리자 계정이 존재합니다.</div>";
        echo "<div class='info'><strong>기본 로그인 정보:</strong><br>";
        echo "사용자명: <strong>admin</strong><br>";
        echo "비밀번호: <strong>password</strong></div>";
    } else {
        echo "<div class='info'>관리자 계정 생성 중...</div>";
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
echo "<p><a href='setup_database.php' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;display:inline-block;margin:10px 5px;'>MySQL 버전 시도</a></p>";
echo "</body></html>";
