<?php
    // 工具类封装
    class SqliteDB
    {
        public SQLite3 $db;
        const TEXT = SQLITE3_TEXT;
        const INT = SQLITE3_INTEGER;
        const FLOAT = SQLITE3_FLOAT;
        const NULL = SQLITE3_NULL;

        /**
         * @param string $name 数据库文件路径
         */
        public function __construct(string $name){
            $this->db = new SQLite3($name);
            $this->db->busyTimeout(5000);
        }

        /**
         * 执行查询,返回关联数组列表
         * @param string $sql SQL语句
         * @return array<int, array<string, mixed>>
         */
        public function get_query(string $sql): array{
            $result_arr = [];
            $result = $this->db->query($sql);
            if ($result){
                while ($row = $result->fetchArray(SQLITE3_ASSOC)){
                    $result_arr[] = $row;
                }
            }
            return $result_arr;
        }

        /**
         * 插入数据,返回自增ID
         * @param string $sql
         * @param array<int, array{0:string, 1:mixed, 2?:int}> $binds
         * @return int
         */
        public function insert_data(string $sql, array $binds): int{
            $stmt = $this->db->prepare($sql);
            foreach ($binds as $bind){
                $type = isset($bind[2]) ? $bind[2] : $this->autoType($bind[1]);
                $stmt->bindValue($bind[0], $bind[1], $type);
            }
            $rcode = $stmt->execute();
            if (!$rcode){
                $msg = $this->db->lastErrorMsg();
                $stmt->close();
                throw new Exception("Database error: " . $msg);
            }
            $id = (int)$this->db->lastInsertRowID();
            $stmt->close();
            return $id;
        }

        /**
         * 带参数查询
         * @param string $sql
         * @param array<int, array{0:string, 1:mixed, 2?:int}> $binds
         * @return array<int, array<string, mixed>>
         */
        public function query_search(string $sql, array $binds): array{
            $result_arr = [];
            $stmt = $this->db->prepare($sql);
            foreach ($binds as $bind){
                $type = isset($bind[2]) ? $bind[2] : $this->autoType($bind[1]);
                $stmt->bindValue($bind[0], $bind[1], $type);
            }
            $rcode = $stmt->execute();
            if ($rcode){
                while ($row = $rcode->fetchArray(SQLITE3_ASSOC)){
                    $result_arr[] = $row;
                }
            }
            $stmt->close();
            return $result_arr;
        }

        /**
         * 执行 UPDATE/DELETE,返回受影响行数
         * @param string $sql
         * @param array<int, array{0:string, 1:mixed, 2?:int}> $binds
         * @return int
         */
        public function run(string $sql, array $binds = []): int{
            $stmt = $this->db->prepare($sql);
            foreach ($binds as $bind){
                $type = isset($bind[2]) ? $bind[2] : $this->autoType($bind[1]);
                $stmt->bindValue($bind[0], $bind[1], $type);
            }
            $rcode = $stmt->execute();
            if (!$rcode){
                $msg = $this->db->lastErrorMsg();
                $stmt->close();
                throw new Exception("Database error: " . $msg);
            }
            $count = (int)$this->db->changes();
            $stmt->close();
            return $count;
        }

        /**
         * 自动推断 SQLite 类型
         * @param mixed $val
         * @return int
         */
        private function autoType($val): int{
            if (is_int($val))    return SQLITE3_INTEGER;
            if (is_float($val))  return SQLITE3_FLOAT;
            if (is_null($val))   return SQLITE3_NULL;
            return SQLITE3_TEXT;
        }

        public function close(): void{
            $this->db->close();
        }

        public function __destruct(){
            if ($this->db) $this->close();
        }
    }

    class Kit
    {
        /**
         * 获取用户IP
         * @return string
         */
        public static function getUserIp() {
            $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            if (!empty($forwarded)) {
                $ip = trim(explode(',', $forwarded)[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
            $real = $_SERVER['HTTP_X_REAL_IP'] ?? '';
            if (!empty($real) && filter_var($real, FILTER_VALIDATE_IP)) {
                return $real;
            }
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        }

        /**
         * 校验验证码(需提前 session_start)
         * @param mixed $uin 用户输入
         * @return bool
         */
        public static function checkCaptcha($uin): bool{
            if (!isset($_SESSION['captcha']) || $uin === null) return false;
            if (is_string($_SESSION['captcha']) && is_string($uin) && hash_equals($_SESSION['captcha'], $uin)){
                unset($_SESSION['captcha']);
                return true;
            }
            return false;
        }

        /**
         * @return string
         */
        public static function getStrTime(): string{
            return date("Y-m-d H:i:s");
        }

        /**
         * 获取 URL 参数
         * @param string $p
         * @return mixed
         */
        public static function get_para(string $p){
            return isset($_GET[$p]) ? $_GET[$p] : false;
        }

        /**
         * 获取整数参数
         * @param string $p
         * @param int $default
         * @return int
         */
        public static function get_para_int(string $p, int $default = 0): int{
            if (isset($_GET[$p]) && ctype_digit(strval($_GET[$p]))){
                return (int)$_GET[$p];
            }
            return $default;
        }

        /**
         * @param string $tableName
         * @return bool
         */
        public static function validateTableName(string $tableName): bool{
            return (bool)preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName);
        }

        /**
         * @param mixed $text
         * @return bool
         */
        public static function is_pure_number($text): bool{
            return ctype_digit(strval($text));
        }

        public static function CORSHeader(): void{
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Max-Age: 86400');
        }

        /**
         * 判断是否校园网
         * @param string $group
         * @param string $debug
         * @return false|string
         */
        public static function in_campus_network(string $group = "local", string $debug = ""){
            $serverGetIp = $debug ?: (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "1.1.1.1");
            switch (Kit::getIPVersion($serverGetIp)) {
                case 4:
                    $ipData = [
                        "local"    => ["192.168.0.0", 16],
                        "tju_wjl"  => ["202.113.0.0", 20],
                        "tju_byy"  => ["202.113.176.0", 20],
                        "nku_blt"  => ["202.113.19.0", 20],
                        "nku_jn"   => ["202.113.224.0", 20]
                    ];
                    if (!array_key_exists($group, $ipData)) {
                        throw new Exception("Group name must in ipData");
                    }
                    $networkBase = $ipData[$group][0];
                    $ipMask = ((1 << $ipData[$group][1]) - 1) << (32 - $ipData[$group][1]);
                    if ((ip2long($networkBase) & $ipMask) == (ip2long($serverGetIp) & $ipMask) || $serverGetIp == "127.0.0.1"){
                        return $group;
                    }
                    return false;
                case 6:
                    $ipData = [
                        "local"   => "::1",
                        "tju_wjl" => "2403ac00",
                        "tju_byy" => "2403ac00",
                        "nku_blt" => "20010250"
                    ];
                    if (!array_key_exists($group, $ipData)) {
                        return false;
                    }
                    if ($group == "local") {
                        return ($serverGetIp == $ipData["local"]) ? "local" : false;
                    } else {
                        $sub = substr(bin2hex(inet_pton($serverGetIp)), 0, strlen($ipData[$group]));
                        return ($sub == $ipData[$group]) ? $group : false;
                    }
                default:
                    return false;
            }
        }

        /**
         * @param string $ip
         * @return int
         */
        public static function getIPVersion(string $ip): int{
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return 4;
            } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return 6;
            }
            return 0;
        }
    }

    class Response
    {
        private $response_data = [
            "code" => 0,
            "msg" => "",
            "data" => []
        ];

        const OK        = 0;
        const ERR_AUTH  = 401;
        const ERR_BAD_REQ = 400;
        const ERR_NOT_FOUND = 404;
        const ERR_SERVER = 500;

        /**
         * @param int $code
         * @param string $message
         */
        public function __construct(int $code = 0, string $message = ""){
            $this->apply($code, $message);
        }

        /**
         * @param int $code
         * @param string $message
         * @return self
         */
        public function apply(int $code, string $message): self{
            $this->response_data['code'] = $code;
            $this->response_data['msg'] = $message;
            return $this;
        }

        /**
         * @param mixed $data
         * @return self
         */
        public function add_data($data): self{
            $this->response_data["data"] = $data;
            return $this;
        }

        /**
         * 成功响应
         * @param mixed $data
         * @return self
         */
        public function ok($data = []): self{
            return $this->apply(self::OK, "success")->add_data($data);
        }

        /**
         * 失败响应
         * @param string $msg
         * @param int $code
         * @return self
         */
        public function fail(string $msg, int $code = self::ERR_SERVER): self{
            return $this->apply($code, $msg);
        }

        /**
         * 输出 JSON(或JSONP padding)
         * @param int $debug 1=带JSONP前缀
         */
        public function text(int $debug = 0): void{
            header('Content-Type: application/json');
            $json = json_encode($this->response_data, JSON_UNESCAPED_UNICODE);
            if ($json === false){
                $this->response_data['code'] = self::ERR_SERVER;
                $this->response_data['msg'] = 'JSON encode error';
                $json = json_encode($this->response_data);
            }
            if ($debug == 1){
                echo "api_response=" . $json;
            } else {
                echo $json;
            }
            die();
        }

        public function js_text(): void{
            header('Content-Type: application/json');
            echo "api_response=" . json_encode($this->response_data, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    // 强制全局开启
    // Kit::CORSHeader();
?>
