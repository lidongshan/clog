<?php
require_once(realpath(dirname(__FILE__)) . "/../vendor/autoload.php");
require_once(realpath(dirname(__FILE__)) . "/../src/Clog.php");

class Logger {
    public function info() {

    }
}

class ClogClientTest extends PHPUnit\Framework\TestCase {
    public function test_demo() {
        $env = "test";
        $caller = "test_demo";
        $clog = \Clog\Clog::ins($env, $caller);

        // 添加日志记录
        $testdata = [
            "msg" => "错误",
            "err_code" => 10000,
            "time_stamp" => time(),
            "extra" => ""
        ];
        $r = $clog->Log($testdata, "ERROR");
        $this->assertTrue($r);
    }
}