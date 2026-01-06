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
    
    /**
     * 트랜잭션 시작 (Supabase는 자동 커밋이므로 빈 구현)
     */
    public function beginTransaction() {
        return true;
    }
    
    /**
     * 트랜잭션 커밋 (Supabase는 자동 커밋이므로 빈 구현)
     */
    public function commit() {
        return true;
    }
    
    /**
     * 트랜잭션 롤백 (Supabase는 자동 커밋이므로 빈 구현)
     */
    public function rollBack() {
        return true;
    }
    
    /**
     * 마지막 삽입 ID 가져오기 (Supabase REST API는 삽입 후 응답에 id 포함)
     */
    public function lastInsertId($name = null) {
        // SupabaseQuery에서 마지막 삽입 ID를 저장하도록 수정 필요
        return $GLOBALS['_last_insert_id'] ?? null;
    }
    
    /**
     * PDO 호환: 속성 가져오기
     */
    public function getAttribute($attribute) {
        switch ($attribute) {
            case PDO::ATTR_DRIVER_NAME:
                return 'supabase'; // Supabase 사용 표시
            case PDO::ATTR_SERVER_INFO:
                return 'Supabase PostgreSQL';
            default:
                return null;
        }
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
        
        // INSERT, UPDATE, DELETE 처리
        if (isset($parsed['operation'])) {
            return $this->executeMutation($parsed);
        }
        
        // SELECT 처리
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
        
        // cURL 또는 file_get_contents 사용
        $fullUrl = $url . (!empty($queryParams) ? '?' . http_build_query($queryParams) : '');
        
        if (function_exists('curl_init')) {
            // cURL 사용 (성능 최적화: 타임아웃 설정)
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5, // 5초 타임아웃
                CURLOPT_CONNECTTIMEOUT => 3, // 연결 타임아웃 3초
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
        } else {
            // file_get_contents 사용 (cURL이 없는 경우, 타임아웃 설정)
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 5, // 5초 타임아웃
                    'header' => [
                        'apikey: ' . $this->supabaseKey,
                        'Authorization: Bearer ' . $this->supabaseKey,
                        'Content-Type: application/json',
                        'Prefer: return=representation'
                    ],
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($fullUrl, false, $context);
            $httpCode = 200; // file_get_contents는 HTTP 코드를 직접 반환하지 않으므로 기본값 사용
            if ($response === false) {
                $httpCode = 500;
            }
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            return $data ?: [];
        }
        
        error_log("Supabase API Error: HTTP $httpCode - $response");
        return [];
    }
    
    /**
     * INSERT, UPDATE, DELETE 실행 (Supabase REST API POST/PATCH/DELETE)
     */
    private function executeMutation($parsed) {
        $url = $this->supabaseUrl . '/rest/v1/' . $parsed['table'];
        $method = 'POST';
        $data = [];
        
        // INSERT 처리
        if ($parsed['operation'] === 'INSERT') {
            // VALUES 절에서 데이터 추출
            if (isset($parsed['values']) && !empty($this->params)) {
                // 컬럼과 값 매핑
                $columns = $parsed['columns'] ?? [];
                foreach ($columns as $index => $column) {
                    if (isset($this->params[$index])) {
                        $data[$column] = $this->params[$index];
                    }
                }
            }
        }
        
        $headers = [
            'apikey: ' . $this->supabaseKey,
            'Authorization: Bearer ' . $this->supabaseKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
        
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_HTTPHEADER => $headers
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => $headers,
                    'content' => json_encode($data),
                    'timeout' => 5,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            $httpCode = 200;
            if ($response === false) {
                $httpCode = 500;
            }
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $result = json_decode($response, true);
            // lastInsertId 저장
            if (is_array($result) && isset($result[0]['id'])) {
                $GLOBALS['_last_insert_id'] = $result[0]['id'];
            } elseif (is_array($result) && isset($result['id'])) {
                $GLOBALS['_last_insert_id'] = $result['id'];
            }
            return $result ?: [];
        }
        
        error_log("Supabase Mutation Error: HTTP $httpCode - $response");
        return [];
    }
    
    /**
     * rowCount() - INSERT/UPDATE/DELETE 후 영향받은 행 수
     */
    public function rowCount() {
        // Supabase REST API는 자동으로 영향받은 행 수를 반환하지 않으므로
        // fetchAll() 결과의 개수를 반환
        return count($this->fetchAll());
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
    
    private function parseSQL($sql) {
        $result = [];
        $sql = trim($sql);
        
        // INSERT INTO table (columns) VALUES (?)
        if (preg_match('/INSERT\s+INTO\s+(\w+)\s*\((.+?)\)\s*VALUES\s*\((.+?)\)/i', $sql, $matches)) {
            $result['operation'] = 'INSERT';
            $result['table'] = trim($matches[1]);
            $columns = array_map('trim', explode(',', $matches[2]));
            $result['columns'] = $columns;
            $values = array_map('trim', explode(',', $matches[3]));
            $result['values'] = $values;
            return $result;
        }
        
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
        
        // ORDER BY (복잡한 ORDER BY 지원: display_order ASC, name ASC)
        if (preg_match('/ORDER\s+BY\s+(.+?)(?:\s+LIMIT|\s+OFFSET|$)/i', $sql, $matches)) {
            $orderClause = trim($matches[1]);
            // 여러 컬럼을 PostgREST 형식으로 변환 (display_order.asc,name.asc)
            $orderParts = [];
            if (preg_match_all('/(\w+)\s*(ASC|DESC)?/i', $orderClause, $orderMatches, PREG_SET_ORDER)) {
                foreach ($orderMatches as $orderMatch) {
                    $col = trim($orderMatch[1]);
                    $dir = strtolower(trim($orderMatch[2] ?? 'asc'));
                    $orderParts[] = $col . '.' . $dir;
                }
            }
            if (!empty($orderParts)) {
                $result['order'] = implode(',', $orderParts);
            }
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
        
        // ? 플레이스홀더가 있는 경우 params에서 가져오기
        if (strpos($whereClause, '?') !== false && !empty($this->params)) {
            // field = ? 형식 파싱
            if (preg_match('/(\w+)\s*=\s*\?/', $whereClause, $match)) {
                $field = trim($match[1]);
                $value = $this->params[0] ?? null;
                if ($value !== null) {
                    $conditions[$field] = $value;
                }
            }
        } else {
            // 간단한 등호 조건 파싱 (field = value)
            if (preg_match_all('/(\w+)\s*=\s*([^\s]+)/', $whereClause, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $field = trim($match[1]);
                    $value = trim($match[2], "'\"");
                    $conditions[$field] = $value;
                }
            }
        }
        
        return $conditions;
    }
}
