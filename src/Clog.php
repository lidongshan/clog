<?php

namespace Clog;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Raven_Client;

class Clog
{
    static public $ins;

    private $lev = array(
        "DEBUG" => 100,
        "INFO" => 200,
        "NOTICE" => 250,
        "WARNING" => 300,
        "ERROR" => 400,
        "CRITICAL" => 500,
        "ALERT" => 550,
        "EMERGENCY" => 600
    );

    static public function ins($env, $caller) {
        if (self::$ins == null) {
            self::$ins = new Clog($env, $caller);
        }
        date_default_timezone_set('PRC');
        return self::$ins;
    }

    private function __construct($env, $caller) {
        if (empty($caller) || empty($env)) {
            throw new \Exception("caller and env must be set!");
        }
        self::load_request_id();
        $log_path = getenv("log_path");

        $logfile = !empty(getenv("logfile_format")) ? getenv("logfile_format") : $log_path . $env . '-' . $caller . "-" . date("Y-m-d") . '.log';
        // 默认的日期格式是 "Y-m-d H:i:s"
        $dateFormat = "Y-m-d\TH:i:sP";
        // 默认的输出格式是 "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
        $output = "[$caller][%datetime%][%level_name%][" . REQUEST_ID . "]%message%\n";
        // 最后创建一个格式化器
        $formatter = new LineFormatter($output, $dateFormat);

        // 创建一个处理器
        $stream = new StreamHandler($logfile);
        $stream->setFormatter($formatter);

        // 将其绑定到日志服务对象上
        $this->Logger = new Logger($caller);
        $this->Logger->pushHandler($stream);
    }


    /**
     * 生成 request_id
     *
     * @return void
     */
    protected function load_request_id() {
        $heads = self::getallheaders();
        $rid = !empty($heads['X-Request-Id']) ? $heads['X-Request-Id'] : md5($_SERVER['REQUEST_TIME_FLOAT']);
        define('REQUEST_ID', $rid);
    }

    /**
     * 生成日志
     *
     * @param array $msg 消息
     * @param string $level 等级
     * @return bool
     * @throws Exception
     */
    function Log($msg = array(), $level = "INFO") {
        // 创建日志频道
        if (empty($msg) || !is_array($msg)) {
            throw new \Exception("msg must be array");
        }

        if (!$this->lev[$level]) {
            throw new \Exception("level is error");
        }


        if ($this->lev[$level] >= 400 && getenv('SENTRY_DSN')) {
            self::LogSentry($msg);
        }

        try {
            $write = $this->Logger->addRecord($this->lev[$level], json_encode($msg, JSON_UNESCAPED_UNICODE));
            return $write;
        } catch (Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        return false;
    }

    /**
     * 向Sentry发送异常日志
     *
     * @param array $exception 消息
     * @return bool
     * @throws Exception
     */
    function LogSentry($exception = array()) {
        if (!$exception || !is_array($exception)) {
            throw new \Exception("exception must be array");
        }
        $dsn = getenv('SENTRY_DSN');
        if ($dsn) {
            try {
                $sentryClient = new Raven_Client($dsn);
                $send = $sentryClient->captureMessage("[" . REQUEST_ID . "]" . json_encode($exception, JSON_UNESCAPED_UNICODE));
                if ($send) {
                    return true;
                }
            } catch (Exception $exception) {
                $this->Logger->addRecord(400, $exception->getMessage());
            }
        }
        return false;
    }

    /**
     * get http header
     *
     * @return array
     * @throws Exception
     */
    function getallheaders() {
        $headers = array();
        try {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        } catch (Exception $exception) {
            self::LogSentry(array("msg" => $exception->getMessage(), "data" => json_encode($_SERVER), "err_code" => 1000));
            $this->Logger->addRecord(400, $exception->getMessage());
        }
        return $headers;
    }


}