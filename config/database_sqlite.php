<?php
/**
 * SQLite 데이터베이스 연결 설정 (MySQL 대체)
 */

class Database {
    private $dbFile;
    private $conn;

    public function __construct() {
        $dbDir = __DIR__ . '/../data';
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        $this->dbFile = $dbDir . '/flower_order_system.db';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "sqlite:" . $this->dbFile,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // 외래 키 제약 조건 활성화
            $this->conn->exec("PRAGMA foreign_keys = ON");
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            throw new Exception("데이터베이스 연결에 실패했습니다: " . $e->getMessage());
        }

        return $this->conn;
    }
}
