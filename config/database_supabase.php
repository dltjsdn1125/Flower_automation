<?php
/**
 * Supabase 데이터베이스 연결 설정
 * REST API를 직접 사용하여 PDO 호환 인터페이스 제공
 */

class Database {
    private static $supabaseUrl = 'https://jnpxwcmshukhkxdzicwv.supabase.co';
    private static $supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImpucHh3Y21zaHVraGt4ZHppY3d2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjcyMTQ0NzEsImV4cCI6MjA4Mjc5MDQ3MX0.C7ZXSR7t15qGShP8FhHlw0r7pLMYSDrmrR7ubb7ofOA';
    private static $conn = null;

    public function getConnection() {
        if (self::$conn === null) {
            self::$conn = new SupabaseConnection(self::$supabaseUrl, self::$supabaseKey);
        }
        return self::$conn;
    }
}

/**
 * Supabase REST API를 사용하는 PDO 호환 클래스
 */
class SupabaseConnection {
    private $supabaseUrl;
    private $supabaseKey;
    
    public function __construct($url, $key) {
        $this->supabaseUrl = $url;
        $this->supabaseKey = $key;
    }
    
    /**
     * SELECT 쿼리 실행
     */
    public function query($sql) {
        return new SupabaseQuery($this->supabaseUrl, $this->supabaseKey, $sql);
    }
    
    /**
     * 준비된 쿼리
     */
    public function prepare($sql) {
        return new SupabaseQuery($this->supabaseUrl, $this->supabaseKey, $sql);
    }
    
    /**
     * DDL 실행 (마이그레이션 등)
     */
    public function exec($sql) {
        // Supabase는 REST API를 통해 DDL을 직접 실행할 수 없으므로
        // MCP를 통해 실행해야 함
        // 여기서는 빈 결과 반환
        return 0;
    }
}

/**
 * Supabase 쿼리 실행 클래스
 */
class SupabaseQuery {
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
        $parsed = $this->parseSQL($this->sql);
        
        if (!$parsed || !isset($parsed['table'])) {
            return [];
        }
        
        $url = $this->supabaseUrl . '/rest/v1/' . $parsed['table'];
        $queryParams = [];
        
        // WHERE 조건을 PostgREST 필터로 변환
        if (isset($parsed['where'])) {
            foreach ($parsed['where'] as $field => $value) {
                $queryParams[$field] = 'eq.' . $value;
            }
        }
        
        // SELECT 컬럼
        if (isset($parsed['select'])) {
            $queryParams['select'] = $parsed['select'];
        }
        
        // ORDER BY
        if (isset($parsed['order'])) {
            $queryParams['order'] = $parsed['order'];
        }
        
        // LIMIT
        if (isset($parsed['limit'])) {
            $queryParams['limit'] = $parsed['limit'];
        }
        
        // OFFSET
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
            $data = json_decode($response, true);
            return $data ?: [];
        }
        
        error_log("Supabase API Error: HTTP $httpCode - $response");
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
        return count($this->fetchAll());
    }
    
    private function parseSQL($sql) {
        $result = [];
        $sql = trim($sql);
        
        // SELECT ... FROM table
        if (preg_match('/SELECT\s+(.+?)\s+FROM\s+(\w+)/i', $sql, $matches)) {
            $result['select'] = trim($matches[1]);
            $result['table'] = trim($matches[2]);
        } elseif (preg_match('/FROM\s+(\w+)/i', $sql, $matches)) {
            $result['table'] = trim($matches[1]);
        }
        
        // WHERE 조건 (간단한 파싱)
        if (preg_match('/WHERE\s+(.+?)(?:\s+ORDER|\s+GROUP|\s+LIMIT|$)/is', $sql, $matches)) {
            $whereClause = trim($matches[1]);
            $result['where'] = $this->parseWhere($whereClause);
        }
        
        // ORDER BY
        if (preg_match('/ORDER\s+BY\s+([^\s]+(?:\s+(?:ASC|DESC))?)/i', $sql, $matches)) {
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
        $conditions = [];
        
        // 간단한 등호 조건 파싱 (field = value)
        if (preg_match_all('/(\w+)\s*=\s*([^\s]+)/', $whereClause, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $field = trim($match[1]);
                $value = trim($match[2], "'\"");
                $conditions[$field] = $value;
            }
        }
        
        return $conditions;
    }
}
