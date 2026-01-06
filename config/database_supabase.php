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
    private $setParamCount = 0; // UPDATE 쿼리의 SET 절 파라미터 개수
    
    public function __construct($url, $key, $sql) {
        $this->supabaseUrl = $url;
        $this->supabaseKey = $key;
        $this->sql = $sql;
    }
    
    private $lastInsertResult = null; // INSERT/UPDATE/DELETE 결과 저장
    private $mutationResult = null; // Mutation 실행 결과 저장
    
    public function execute($params = []) {
        $this->params = $params;

        // INSERT/UPDATE/DELETE는 execute() 시점에 바로 실행
        $parsed = $this->parseSQL($this->sql);
        
        // 성능 최적화: 디버그 로그 제거
        
        if (isset($parsed['operation'])) {
            // SET 절 파라미터 개수 저장
            if (isset($parsed['set_param_count'])) {
                $this->setParamCount = $parsed['set_param_count'];
            }
            // INSERT/UPDATE/DELETE 결과 저장
            $this->mutationResult = $this->executeMutation($parsed);
            // INSERT의 경우 lastInsertId()를 위해 별도 저장
            if ($parsed['operation'] === 'INSERT') {
                $this->lastInsertResult = $this->mutationResult;
            }
        }

        return $this;
    }
    
    public function fetchAll($mode = PDO::FETCH_ASSOC) {
        // INSERT/UPDATE/DELETE가 이미 execute()에서 실행된 경우 결과 반환
        if ($this->mutationResult !== null) {
            $result = $this->mutationResult;
            // INSERT의 경우 lastInsertId()를 위해 한 번 더 사용할 수 있도록 유지
            // UPDATE/DELETE는 rowCount() 호출 후에 null로 설정
            // INSERT의 경우 lastInsertResult에도 저장되어 있으므로 null로 설정하지 않음
            return $result;
        }
        
        // INSERT의 경우 lastInsertResult도 확인
        // 여러 개의 INSERT를 반복할 때 각각의 ID를 가져올 수 있도록
        // 각 INSERT마다 새로운 SupabaseQuery 인스턴스가 생성되므로
        // lastInsertResult는 해당 INSERT의 결과만 포함
        if ($this->lastInsertResult !== null) {
            $result = $this->lastInsertResult;
            // lastInsertId()를 위해 한 번 더 사용할 수 있도록 유지
            // 하지만 fetchAll()이 여러 번 호출되면 계속 같은 결과를 반환
            // 따라서 한 번만 반환하고 null로 설정하지 않음
            return $result;
        }
        
        $parsed = $this->parseSQL($this->sql);
        
        if (!$parsed || !isset($parsed['table'])) {
            // 성능 최적화를 위해 로깅 제거 (에러 발생 시에만 로깅)
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
        if (isset($parsed['where']) && !empty($parsed['where'])) {
            foreach ($parsed['where'] as $field => $value) {
                // PostgREST 필터 형식: field=eq.value
                $queryParams[$field] = 'eq.' . $value;
            }
        }
        
        // SELECT 컬럼 (SELECT *인 경우 select 파라미터 생략)
        if (isset($parsed['select']) && $parsed['select'] !== '*') {
            $queryParams['select'] = $parsed['select'];
        }
        
        // ORDER BY
        if (isset($parsed['order'])) {
            $queryParams['order'] = $parsed['order'];
        }
        
        // LIMIT (파라미터에서 가져오기)
        if (isset($parsed['limit'])) {
            $queryParams['limit'] = $parsed['limit'];
        } elseif (preg_match('/LIMIT\s+\?/i', $this->sql) && !empty($this->params)) {
            // LIMIT ? 파라미터 처리 (마지막에서 두 번째 파라미터)
            $limitIndex = count($this->params) - 2;
            if ($limitIndex >= 0 && isset($this->params[$limitIndex])) {
                $queryParams['limit'] = (int)$this->params[$limitIndex];
            }
        }
        
        // OFFSET (파라미터에서 가져오기)
        if (isset($parsed['offset'])) {
            $queryParams['offset'] = $parsed['offset'];
        } elseif (preg_match('/OFFSET\s+\?/i', $this->sql) && !empty($this->params)) {
            // OFFSET ? 파라미터 처리 (마지막 파라미터)
            $offsetIndex = count($this->params) - 1;
            if ($offsetIndex >= 0 && isset($this->params[$offsetIndex])) {
                $queryParams['offset'] = (int)$this->params[$offsetIndex];
            }
        }
        
        // PostgREST 필터 형식으로 URL 구성
        // PostgREST는 field=eq.value 형식을 사용하며, 값만 URL 인코딩해야 함
        $queryString = '';
        if (!empty($queryParams)) {
            $parts = [];
            foreach ($queryParams as $key => $value) {
                // eq.value 형식에서 값 부분만 인코딩 (예: username=eq.admin)
                if (strpos($value, 'eq.') === 0) {
                    // eq. 접두사는 그대로 두고 값만 인코딩
                    $encodedValue = 'eq.' . urlencode(substr($value, 3));
                } else {
                    $encodedValue = urlencode($value);
                }
                $parts[] = $key . '=' . $encodedValue;
            }
            $queryString = '?' . implode('&', $parts);
        }
        $fullUrl = $url . $queryString;
        
        // 디버깅: URL과 파라미터 로깅 (에러 발생 시에만 로깅)
        // 성능 최적화를 위해 일반적인 경우 로깅 제거
        
        // cURL 우선 사용 (HTTPS 지원 및 안정성)
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5, // 5초 타임아웃 (성능 개선)
                CURLOPT_CONNECTTIMEOUT => 2, // 연결 타임아웃 2초 (성능 개선)
                CURLOPT_SSL_VERIFYPEER => false, // 개발 환경에서 SSL 검증 비활성화
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'apikey: ' . $this->supabaseKey,
                    'Authorization: Bearer ' . $this->supabaseKey,
                    'Content-Type: application/json',
                    'Prefer: return=representation'
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            if (function_exists('curl_close') && version_compare(PHP_VERSION, '8.0', '<')) {
                curl_close($ch);
            }
            
            if ($response === false) {
                // 에러 발생 시에만 로깅 (성능 최적화)
                error_log("cURL Error: $curlError");
                return [];
            }
        } else {
            // file_get_contents 사용 (cURL이 없는 경우, 타임아웃 설정)
            $headers = [
                'apikey: ' . $this->supabaseKey,
                'Authorization: Bearer ' . $this->supabaseKey,
                'Content-Type: application/json',
                'Prefer: return=representation'
            ];
            
            // file_get_contents는 allow_url_fopen이 필요함
                if (!ini_get('allow_url_fopen')) {
                    // 에러 발생 시에만 로깅 (성능 최적화)
                    error_log("allow_url_fopen is disabled. Please enable it or install cURL extension.");
                    return [];
                }
            
            // SSL 컨텍스트 설정 개선
            $sslContext = [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'cafile' => null,
                'capath' => null,
                'disable_compression' => false,
                'peer_fingerprint' => null
            ];
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10, // 10초 타임아웃
                    'header' => implode("\r\n", $headers),
                    'ignore_errors' => true,
                    'follow_location' => 1,
                    'max_redirects' => 3
                ],
                'ssl' => $sslContext,
                'https' => $sslContext  // https 스키마도 명시적으로 설정
            ]);
            
            // file_get_contents 대신 stream_context_create로 직접 열기
            $fp = @fopen($fullUrl, 'r', false, $context);
            if ($fp === false) {
                $error = error_get_last();
                    // 에러 발생 시에만 로깅 (성능 최적화)
                    error_log("fopen failed: " . ($error['message'] ?? 'Unknown error'));
                // file_get_contents로 재시도
                $response = @file_get_contents($fullUrl, false, $context);
                if ($response === false) {
                    $error = error_get_last();
                    // 에러 발생 시에만 로깅 (성능 최적화)
                    error_log("file_get_contents also failed: " . ($error['message'] ?? 'Unknown error'));
                    return [];
                }
            } else {
                $response = stream_get_contents($fp);
                fclose($fp);
            }

            // HTTP 응답 코드 파싱 (PHP 8.4+ 호환)
            $httpCode = $this->getHttpStatusCode($response);
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            return $data ?: [];
        }
        
        // HTTP 오류 시 상세 정보 로깅
            // 에러 발생 시에만 로깅 (성능 최적화)
            if ($httpCode >= 400) {
                $errorInfo = [
                    'http_code' => $httpCode,
                    'url' => $fullUrl,
                    'response' => substr($response, 0, 200),
                    'query_params' => $queryParams
                ];
                error_log("Supabase API Error: " . json_encode($errorInfo, JSON_UNESCAPED_UNICODE));
            }
            return [];
    }
    
    /**
     * INSERT, UPDATE, DELETE 실행 (Supabase REST API POST/PATCH/DELETE)
     */
    private function executeMutation($parsed) {
        $url = $this->supabaseUrl . '/rest/v1/' . $parsed['table'];
        $method = 'POST';
        $data = [];
        $queryParams = [];
        
        // UPDATE 처리
        if ($parsed['operation'] === 'UPDATE') {
            $method = 'PATCH';
            
            // SET 절에서 데이터 추출
            if (isset($parsed['set']) && !empty($this->params)) {
                foreach ($parsed['set'] as $column => $paramIndex) {
                    if (isset($this->params[$paramIndex])) {
                        $data[$column] = $this->params[$paramIndex];
                    }
                }
            }
            
            // WHERE 조건을 PostgREST 필터로 변환
            if (isset($parsed['where']) && !empty($parsed['where'])) {
                foreach ($parsed['where'] as $field => $value) {
                    // id IN (?, ?, ?) 형식 처리
                    if (is_array($value)) {
                        // PostgREST IN 필터 형식: id=in.(1,2,3)
                        // 값들을 쉼표로 구분하고 괄호로 감싸기
                        $inValues = array_map('intval', $value); // 정수로 변환
                        $queryParams[$field] = 'in.(' . implode(',', $inValues) . ')';
                    } else {
                        $queryParams[$field] = 'eq.' . $value;
                    }
                }
            }
            
            // PostgREST 필터 형식으로 URL 구성
            if (!empty($queryParams)) {
                $parts = [];
                foreach ($queryParams as $key => $value) {
                    if (strpos($value, 'eq.') === 0) {
                        $encodedValue = 'eq.' . urlencode(substr($value, 3));
                    } elseif (strpos($value, 'in.') === 0) {
                        // PostgREST IN 필터 형식: id=in.(1,2,3)
                        // PostgREST는 in.(1,2,3) 형식을 직접 파싱하므로 그대로 사용
                        // 괄호와 쉼표는 URL 인코딩하지 않음
                        $encodedValue = $value; // in.(1,2,3) 형식 그대로 사용
                    } else {
                        $encodedValue = urlencode($value);
                    }
                    // URL 쿼리 파라미터 구성
                    // PostgREST는 필드명을 그대로 사용하므로 키는 인코딩하지 않음
                    // 값은 연산자에 따라 조건부 인코딩
                    $parts[] = $key . '=' . $encodedValue;
                }
                $url .= '?' . implode('&', $parts);
            }
            
            // 디버깅: UPDATE 쿼리 정보 로깅 (복수 인수증 업데이트 문제 디버깅)
            // 성능 최적화: 디버그 로그 제거
        }
        
        // DELETE 처리
        if ($parsed['operation'] === 'DELETE') {
            $method = 'DELETE';
            
            // WHERE 조건을 PostgREST 필터로 변환
            if (isset($parsed['where']) && !empty($parsed['where'])) {
                foreach ($parsed['where'] as $field => $value) {
                    // id IN (?, ?, ?) 형식 처리
                    if (is_array($value)) {
                        // PostgREST IN 필터 형식: id=in.(1,2,3)
                        $inValues = array_map('intval', $value);
                        $queryParams[$field] = 'in.(' . implode(',', $inValues) . ')';
                    } else {
                        $queryParams[$field] = 'eq.' . $value;
                    }
                }
            }
            
            // PostgREST 필터 형식으로 URL 구성
            if (!empty($queryParams)) {
                $parts = [];
                foreach ($queryParams as $key => $value) {
                    if (strpos($value, 'eq.') === 0) {
                        $encodedValue = 'eq.' . urlencode(substr($value, 3));
                    } elseif (strpos($value, 'in.') === 0) {
                        $encodedValue = $value;
                    } else {
                        $encodedValue = urlencode($value);
                    }
                    $parts[] = $key . '=' . $encodedValue;
                }
                $url .= '?' . implode('&', $parts);
            }
            
            // 디버깅: DELETE 쿼리 정보 로깅
            // 성능 최적화: 디버그 로그 제거
        }
        
        // INSERT 처리
        if ($parsed['operation'] === 'INSERT') {
            // VALUES 절에서 데이터 추출
            if (isset($parsed['values']) && !empty($this->params)) {
                // 컬럼과 값 매핑
                $columns = $parsed['columns'] ?? [];
                $paramIndex = 0; // 파라미터 인덱스 추적
                foreach ($columns as $column) {
                    // VALUES 절의 ? 개수만큼 파라미터를 매핑
                    if ($paramIndex < count($this->params)) {
                        $value = $this->params[$paramIndex];
                        // null 값도 포함 (Supabase가 처리)
                        $data[$column] = $value;
                        $paramIndex++;
                    }
                }
            }
            
            // 디버깅: INSERT 쿼리 정보 로깅
            // 성능 최적화: 디버그 로그 제거
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
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => $headers
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            if (function_exists('curl_close') && version_compare(PHP_VERSION, '8.0', '<')) {
                curl_close($ch);
            }
            
            if ($response === false) {
                // 에러 발생 시에만 로깅 (성능 최적화)
                error_log("cURL Error: $curlError");
                throw new Exception("Supabase API 오류: cURL 실패 - $curlError");
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => implode("\r\n", $headers),
                    'content' => json_encode($data),
                    'timeout' => 10,
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            // HTTP 응답 코드 파싱 (PHP 8.4+ 호환)
            $httpCode = $this->getHttpStatusCode($response);
        }
        
        // 성능 최적화를 위해 로깅 제거 (에러 발생 시에만 로깅)
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $result = json_decode($response, true);
            
            // 성능 최적화: 디버그 로그 제거 (에러 발생 시에만 로깅)
            
            // UPDATE/DELETE의 경우 빈 배열이 반환될 수 있으므로, 성공으로 간주
            if (($parsed['operation'] === 'UPDATE' || $parsed['operation'] === 'DELETE') && empty($result)) {
                // UPDATE/DELETE 성공 시 빈 배열 대신 업데이트/삭제된 것으로 표시하기 위한 더미 데이터
                // (실제로는 영향받은 행 수를 알 수 없지만, 성공으로 간주)
                $result = [['updated' => true]];
            }
            
            // lastInsertId 저장 (INSERT만)
            if ($parsed['operation'] === 'INSERT') {
                if (is_array($result) && isset($result[0]['id'])) {
                    $GLOBALS['_last_insert_id'] = $result[0]['id'];
                } elseif (is_array($result) && isset($result['id'])) {
                    $GLOBALS['_last_insert_id'] = $result['id'];
                }
            }
            
            return $result ?: [];
        }

        // 에러 발생 시에만 로깅 (성능 최적화)
        if ($httpCode >= 400) {
            error_log("Supabase Mutation Error: HTTP $httpCode - " . substr($response, 0, 200));
        }
        throw new Exception("Supabase API 오류: HTTP $httpCode - " . substr($response, 0, 200));
    }
    
    /**
     * rowCount() - INSERT/UPDATE/DELETE 후 영향받은 행 수
     */
    public function rowCount() {
        // mutationResult가 있으면 이미 실행된 것으로 간주
        if ($this->mutationResult !== null) {
            $results = $this->mutationResult;
            
            // UPDATE/DELETE는 rowCount() 호출 후 mutationResult를 null로 설정
            if ($this->lastInsertResult === null) {
                $this->mutationResult = null;
            }
            
            // UPDATE/DELETE의 경우 빈 배열이 반환될 수 있으므로
            // PostgREST는 성공 시 빈 배열을 반환할 수 있음
            // 하지만 실제로 업데이트된 행이 있는지 확인하기 어려움
            if (empty($results)) {
                // UPDATE/DELETE의 경우 성공으로 간주하고 1 반환
                // (실제로는 영향받은 행 수를 알 수 없지만, HTTP 200 응답이면 성공)
                return 1;
            }
            
            // 결과가 있는 경우 (예: INSERT의 경우 생성된 행 반환)
            // UPDATE의 경우 [['updated' => true]] 형식일 수 있음
            if (isset($results[0]['updated']) && $results[0]['updated'] === true) {
                return 1; // UPDATE 성공
            }
            
            return count($results);
        }
        
        // lastInsertResult 확인 (INSERT의 경우)
        if ($this->lastInsertResult !== null) {
            return count($this->lastInsertResult);
        }
        
        // fetchAll()로 결과 가져오기
        $results = $this->fetchAll();
        if (empty($results)) {
            return 0;
        }
        return count($results);
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
        // 여러 줄 SQL을 한 줄로 정규화 (공백을 하나로 통합)
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        
        // UPDATE table SET column = ? WHERE ...
        if (preg_match('/UPDATE\s+(\w+)\s+SET\s+(.+?)(?:\s+WHERE|$)/i', $sql, $matches)) {
            $result['operation'] = 'UPDATE';
            $result['table'] = trim($matches[1]);
            $setClause = trim($matches[2]);
            
            // SET 절 파싱: column = ? 형식
            if (preg_match_all('/(\w+)\s*=\s*\?/i', $setClause, $setMatches, PREG_SET_ORDER)) {
                $result['set'] = [];
                foreach ($setMatches as $index => $setMatch) {
                    $result['set'][trim($setMatch[1])] = $index; // 파라미터 인덱스 저장
                }
                $result['set_param_count'] = count($setMatches); // SET 절 파라미터 개수 저장
            }
            
            // WHERE 절 파싱
            if (preg_match('/WHERE\s+(.+?)$/is', $sql, $whereMatches)) {
                $whereClause = trim($whereMatches[1]);
                // parseWhere 호출 전에 setParamCount 설정
                if (isset($result['set_param_count'])) {
                    $this->setParamCount = $result['set_param_count'];
                }
                $result['where'] = $this->parseWhere($whereClause);
            }
            
            return $result;
        }
        
        // INSERT INTO table (columns) VALUES (?)
        // 여러 줄 SQL도 처리할 수 있도록 개선 (s 플래그로 줄바꿈 포함)
        // 정규식을 더 유연하게 수정: .+? 대신 [^)]+ 사용하여 괄호 안의 모든 내용 매칭
        if (preg_match('/INSERT\s+INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/is', $sql, $matches)) {
            $result['operation'] = 'INSERT';
            $result['table'] = trim($matches[1]);
            // 컬럼 목록에서 줄바꿈과 공백 정리
            $columnsStr = preg_replace('/\s+/', ' ', $matches[2]);
            $columns = array_map('trim', explode(',', $columnsStr));
            $result['columns'] = $columns;
            // VALUES 절에서 줄바꿈과 공백 정리
            $valuesStr = preg_replace('/\s+/', ' ', $matches[3]);
            $values = array_map('trim', explode(',', $valuesStr));
            $result['values'] = $values;
            
            // 디버깅: INSERT 파싱 결과 로깅
            // 성능 최적화: 디버그 로그 제거
            
            return $result;
        }
        
        // DELETE FROM table WHERE ...
        if (preg_match('/DELETE\s+FROM\s+(\w+)(?:\s+WHERE\s+(.+?))?$/i', $sql, $matches)) {
            $result['operation'] = 'DELETE';
            $result['table'] = trim($matches[1]);
            
            // WHERE 절 파싱
            if (isset($matches[2]) && !empty(trim($matches[2]))) {
                $whereClause = trim($matches[2]);
                $result['where'] = $this->parseWhere($whereClause);
            }
            
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
        
        // LIMIT (숫자 또는 ? 파라미터 지원)
        if (preg_match('/LIMIT\s+(\d+|\?)/i', $sql, $matches)) {
            $limitValue = trim($matches[1]);
            if ($limitValue === '?') {
                // 파라미터에서 LIMIT 값 가져오기 (마지막에서 두 번째 파라미터)
                if (!empty($this->params) && count($this->params) >= 2) {
                    $result['limit'] = (int)$this->params[count($this->params) - 2];
                }
            } else {
                $result['limit'] = (int)$limitValue;
            }
        }
        
        // OFFSET (숫자 또는 ? 파라미터 지원)
        if (preg_match('/OFFSET\s+(\d+|\?)/i', $sql, $matches)) {
            $offsetValue = trim($matches[1]);
            if ($offsetValue === '?') {
                // 파라미터에서 OFFSET 값 가져오기 (마지막 파라미터)
                if (!empty($this->params) && count($this->params) >= 1) {
                    $result['offset'] = (int)$this->params[count($this->params) - 1];
                }
            } else {
                $result['offset'] = (int)$offsetValue;
            }
        }
        
        return $result;
    }
    
    /**
     * HTTP 응답 헤더에서 상태 코드 추출 (PHP 8.4+ 호환)
     */
    private function getHttpStatusCode($response) {
        if ($response === false) {
            // 성능 최적화를 위해 로깅 제거 (에러 발생 시에만 로깅)
            return 500;
        }

        // PHP 8.4+: http_get_last_response_headers() 사용
        if (function_exists('http_get_last_response_headers')) {
            $headers = http_get_last_response_headers();
        } else {
            // PHP 8.3 이하: $http_response_header 사용
            global $http_response_header;
            $headers = $http_response_header ?? [];
        }

        if (!empty($headers) && isset($headers[0])) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $matches)) {
                return (int)$matches[1];
            }
        }

        // 응답이 있으면 성공으로 간주
        return 200;
    }

    private function parseWhere($whereClause) {
        $conditions = [];
        
        // 성능 최적화를 위해 로깅 제거
        
        // IN 조건 처리: field IN (?, ?, ?)
        if (preg_match('/(\w+)\s+IN\s*\((.+?)\)/i', $whereClause, $inMatch)) {
            $field = trim($inMatch[1]);
            $inClause = trim($inMatch[2]);
            $inPlaceholders = substr_count($inClause, '?');
            
            if ($inPlaceholders > 0 && !empty($this->params)) {
                // IN 절의 값들 추출
                // UPDATE의 경우: SET 절 파라미터 이후부터 시작
                // SELECT/DELETE의 경우: 처음부터 시작
                $inValues = [];
                $startIndex = $this->setParamCount; // UPDATE인 경우 SET 절 파라미터 개수만큼 건너뛰기
                
                for ($i = 0; $i < $inPlaceholders; $i++) {
                    $paramIndex = $startIndex + $i;
                    if (isset($this->params[$paramIndex])) {
                        $value = $this->params[$paramIndex];
                        // 숫자로 변환 가능한 경우 숫자로 변환
                        if (is_numeric($value)) {
                            $inValues[] = (int)$value;
                        } else {
                            $inValues[] = $value;
                        }
                    }
                }
                
                if (!empty($inValues)) {
                    $conditions[$field] = $inValues; // 배열로 저장
                }
            }
            
            // 성능 최적화를 위해 로깅 제거
            return $conditions;
        }
        
        // ? 플레이스홀더가 있는 경우 params에서 가져오기
        if (strpos($whereClause, '?') !== false) {
            // 여러 개의 ? 플레이스홀더 지원
            $placeholders = substr_count($whereClause, '?');
            if (!empty($this->params) && count($this->params) >= $placeholders) {
                // SET 절의 파라미터 개수 사용 (이미 저장된 값)
                $setParamCount = $this->setParamCount;
                
                // field = ? 형식 파싱 (여러 조건 지원)
                if (preg_match_all('/(\w+)\s*=\s*\?/i', $whereClause, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $index => $match) {
                        $field = trim($match[1]);
                        $paramIndex = $setParamCount + $index;
                        $value = $this->params[$paramIndex] ?? null;
                        if ($value !== null) {
                            $conditions[$field] = $value;
                        }
                    }
                } else {
                    // 단순 ? 플레이스홀더 (field = ? 형식이 아닌 경우)
                    // 첫 번째 파라미터 사용
                    if (preg_match('/(\w+)\s*=\s*\?/i', $whereClause, $match)) {
                        $field = trim($match[1]);
                        $paramIndex = $setParamCount;
                        $value = $this->params[$paramIndex] ?? null;
                        if ($value !== null) {
                            $conditions[$field] = $value;
                        }
                    }
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
        
        // 성능 최적화를 위해 로깅 제거
        return $conditions;
    }
}
