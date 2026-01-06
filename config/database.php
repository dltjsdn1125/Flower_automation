<?php
/**
 * 데이터베이스 연결 설정
 * MySQL이 없으면 SQLite로 자동 전환
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'flower_order_system';
    private $username = 'root';
    private $password = '';
    private static $conn = null; // 연결 캐싱
    private static $mysqlAvailable = null; // MySQL 사용 가능 여부 캐싱

    public function getConnection() {
        // 이미 연결이 있으면 재사용
        if (self::$conn !== null) {
            return self::$conn;
        }

        // MySQL 사용 가능 여부 확인 (한 번만 체크)
        if (self::$mysqlAvailable === null) {
            self::$mysqlAvailable = $this->tryMySQL();
        }

        if (self::$mysqlAvailable === false) {
            // SQLite 사용
            self::$conn = $this->getSQLiteConnection();
        } else {
            self::$conn = $this->conn;
        }

        return self::$conn;
    }

    private function tryMySQL() {
        try {
            // 연결 타임아웃을 0.1초로 단축 (더 빠른 실패)
            $conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 0.1
                ]
            );
            // 연결 테스트 (빠른 실패)
            $conn->query("SELECT 1");
            $this->conn = $conn;
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }

    private function getSQLiteConnection() {
        try {
            $dbDir = __DIR__ . '/../data';
            if (!is_dir($dbDir)) {
                @mkdir($dbDir, 0755, true);
            }
            $dbFile = $dbDir . '/flower_order_system.db';
            
            $this->conn = new PDO(
                "sqlite:" . $dbFile,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // SQLite 성능 최적화 설정 (한 번만 실행)
            static $sqliteOptimized = false;
            if (!$sqliteOptimized) {
                $this->conn->exec("PRAGMA foreign_keys = ON");
                $this->conn->exec("PRAGMA journal_mode = WAL");
                $this->conn->exec("PRAGMA synchronous = NORMAL");
                $this->conn->exec("PRAGMA cache_size = -64000");
                $this->conn->exec("PRAGMA temp_store = MEMORY");
                $sqliteOptimized = true;
            }
            
            return $this->conn;
        } catch(PDOException $e) {
            error_log("SQLite Connection Error: " . $e->getMessage());
            throw new Exception("데이터베이스 연결에 실패했습니다.");
        }
    }
}
