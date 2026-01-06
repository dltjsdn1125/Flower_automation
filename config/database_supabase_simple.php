<?php
/**
 * Supabase 데이터베이스 연결 설정 (REST API 직접 사용)
 * PDO 호환성을 위한 래퍼 클래스
 */

class Database {
    private static $supabaseUrl = 'https://jnpxwcmshukhkxdzicwv.supabase.co';
    private static $supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImpucHh3Y21zaHVraGt4ZHppY3d2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjcyMTQ0NzEsImV4cCI6MjA4Mjc5MDQ3MX0.C7ZXSR7t15qGShP8FhHlw0r7pLMYSDrmrR7ubb7ofOA';
    private static $conn = null;

    public function getConnection() {
        // Supabase REST API를 사용하는 PDO 호환 래퍼 반환
        if (self::$conn === null) {
            self::$conn = new SupabasePDOWrapper();
        }
        return self::$conn;
    }
}

/**
 * PDO 호환 래퍼 클래스 (Supabase REST API 사용)
 */
class SupabasePDOWrapper {
    private $supabaseUrl = 'https://jnpxwcmshukhkxdzicwv.supabase.co';
    private $supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImpucHh3Y21zaHVraGt4ZHppY3d2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjcyMTQ0NzEsImV4cCI6MjA4Mjc5MDQ3MX0.C7ZXSR7t15qGShP8FhHlw0r7pLMYSDrmrR7ubb7ofOA';
    
    public function query($sql) {
        // SQL을 Supabase REST API 호출로 변환
        return new SupabaseStatement($this->supabaseUrl, $this->supabaseKey, $sql);
    }
    
    public function prepare($sql) {
        return new SupabaseStatement($this->supabaseUrl, $this->supabaseKey, $sql);
    }
    
    public function exec($sql) {
        // DDL 실행
        return $this->query($sql)->execute();
    }
}

class SupabaseStatement {
    private $supabaseUrl;
    private $supabaseKey;
    private $sql;
    private $params = [];
    
    public function __construct($url, $key, $sql) {
        $this->supabaseUrl = $url;
        $this->supabaseKey = $key;
        $this->sql = $sql;
    }
    
    public function execute($params = []) {
        $this->params = $params;
        return $this;
    }
    
    public function fetchAll($mode = PDO::FETCH_ASSOC) {
        // SQL을 REST API 호출로 변환
        $parsed = $this->parseSQL($this->sql);
        
        $url = $this->supabaseUrl . '/rest/v1/' . $parsed['table'];
        
        // 필터링 및 정렬 파라미터 추가
        $queryParams = [];
        if (isset($parsed['where'])) {
            $queryParams = array_merge($queryParams, $parsed['where']);
        }
        if (isset($parsed['order'])) {
            $queryParams['order'] = $parsed['order'];
        }
        if (isset($parsed['limit'])) {
            $queryParams['limit'] = $parsed['limit'];
        }
        if (isset($parsed['offset'])) {
            $queryParams['offset'] = $parsed['offset'];
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url . (!empty($queryParams) ? '?' . http_build_query($queryParams) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->supabaseKey,
                'Authorization: Bearer ' . $this->supabaseKey,
                'Content-Type: application/json',
                'Prefer: return=representation'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true) ?: [];
        }
        
        return [];
    }
    
    public function fetch($mode = PDO::FETCH_ASSOC) {
        $results = $this->fetchAll($mode);
        return $results[0] ?? false;
    }
    
    public function fetchColumn($column = 0) {
        $row = $this->fetch();
        if ($row === false) return false;
        $values = array_values($row);
        return $values[$column] ?? false;
    }
    
    public function rowCount() {
        // Supabase는 count를 별도로 조회해야 함
        return count($this->fetchAll());
    }
    
    private function parseSQL($sql) {
        // 간단한 SQL 파싱 (실제로는 더 복잡한 파서 필요)
        $result = [];
        
        // SELECT ... FROM table
        if (preg_match('/FROM\s+(\w+)/i', $sql, $matches)) {
            $result['table'] = $matches[1];
        }
        
        // WHERE 조건
        if (preg_match('/WHERE\s+(.+?)(?:\s+ORDER|\s+LIMIT|$)/i', $sql, $matches)) {
            $result['where'] = $this->parseWhere($matches[1]);
        }
        
        // ORDER BY
        if (preg_match('/ORDER\s+BY\s+([^\s]+(?:\s+ASC|\s+DESC)?)/i', $sql, $matches)) {
            $result['order'] = trim($matches[1]);
        }
        
        // LIMIT
        if (preg_match('/LIMIT\s+(\d+)/i', $sql, $matches)) {
            $result['limit'] = (int)$matches[1];
        }
        
        // OFFSET
        if (preg_match('/OFFSET\s+(\d+)/i', $sql, $matches)) {
            $result['offset'] = (int)$matches[1];
        }
        
        return $result;
    }
    
    private function parseWhere($whereClause) {
        // 간단한 WHERE 파싱
        $conditions = [];
        // 실제 구현은 더 복잡해야 함
        return $conditions;
    }
}
