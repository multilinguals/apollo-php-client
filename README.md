# apollo-php-client
携程Apollo配置中心php SDK

## install
```bash
$ composer require multilinguals/apollo-client
```
php version >= 5.4 required

## Features
- 支持apollo配置变更的适时获取
- 支持拉取配置后自定义的回调处理

## Usage
客户端以cli的方式后台启动执行，支持apollo配置的适时获取，并将配置保存在指定的目录供应用程序读取解析

### 客户端示例代码
```php
#!/usr/bin/env php
<?php
require 'vender/autoload.php'; // autoload
use Org\Multilinguals\Apollo\Client\ApolloClient;

//specify address of apollo server
$server = getenv('CONFIG_SERVER'); // get server address from env

//specify your appid at apollo config server
$appid = getenv('APPID'); // get appid from env

//specify namespaces of appid at apollo config server
$namespaces = getenv('NAMESPACE'); // get namespaces from env
$namespaces = explode(',', $namespaces);

$apollo = new ApolloClient($server, $appid, $namespaces);

if ($clientIp = getenv('CLIENTIP')) {
    $apollo->setClientIp($clientIp);
}

ini_set('memory_limit','128M');
$pid = getmypid();
echo "start [$pid]\n";
$restart = true; //auto start if failed
do {
    $error = $apollo->start();
    if ($error) echo('error:'.$error."\n");
}while($error && $restart);
```

### 配置管理

拉取的配置默认保存在脚本所在目录，每个namespace的配置以`apolloConfig.{$namespaceName}.php`的方式命名保存

### Docker环境客户端自启动

在docker的启动脚本中加入的启动代码，一般的php容器启动脚本是docker-php-entrypoint
```bash
if [ -f "/path/to/start.php" ]; then
    apollo_ps=$(ps -aux | grep -c "php /path/to/start.php")
    if [ $apollo_ps -eq 1 ]; then
        php /path/to/start.php &
    fi
fi
```
