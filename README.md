Common Logger for PHP
---
## 设置私有composer镜像源
```bash
composer config repo.packagist composer http://composer.xxx.xxx
```
---
## 引入Clog composer包
### 方法1
```bash
composer require antiy/clog
```
### 方法2
```json
  "require": {
    "clog/clog": "1.*"
  }
```
---
## 用例示范
```php
public function test_demo() {
        $env = "test";
        $caller = "Clogtest";
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
```
msg需要被记录的信息，以数组传递
"INFO" 日志级别 默认INFO
（"DEBUG","INFO"，"NOTICE"，"WARNING"，"ERROR"，"CRITICAL"，"ALERT"，"EMERGENCY"）

---
