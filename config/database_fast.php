<?php
/**
 * 빠른 데이터베이스 연결 (SQLite 전용)
 * MySQL 연결 시도를 건너뛰어 성능 향상
 */

class Database {
    private static $conn = null;

    public function getConnection() {
        // 이미 연결이 있으면 재사용
        if (self::$conn !== null) {
            return self::$conn;
        }

        try {
            $dbDir = __DIR__ . '/../data';
            if (!is_dir($dbDir)) {
                @mkdir($dbDir, 0755, true);
            }
            $dbFile = $dbDir . '/flower_order_system.db';
            
            self::$conn = new PDO(
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
                self::$conn->exec("PRAGMA foreign_keys = ON");
                self::$conn->exec("PRAGMA journal_mode = WAL");
                self::$conn->exec("PRAGMA synchronous = NORMAL");
                self::$conn->exec("PRAGMA cache_size = -64000");
                self::$conn->exec("PRAGMA temp_store = MEMORY");
                $sqliteOptimized = true;
            }
            
            return self::$conn;
        } catch(PDOException $e) {
            error_log("SQLite Connection Error: " . $e->getMessage());
            throw new Exception("데이터베이스 연결에 실패했습니다.");
        }
    }
}
