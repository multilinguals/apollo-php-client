#!/usr/bin/env php
<?php
PHP_SAPI === 'cli' || exit();

//引入apollo客户端库文件
require __DIR__.'/ApolloClient.php';

//specify address of apollo server
$server = getenv('CONFIG_SERVER'); // get server address from env

//specify your appid at apollo config server
$appid = getenv('APPID'); // get appid from env

//specify namespaces of appid at apollo config server
$namespaces = getenv('NAMESPACE'); // get namespaces from env

$apollo = new ApolloClient($server, $appid, $namespaces);

if ($clientIp = getenv('CLIENTIP')) {
    $apollo->setClientIp($clientIp);
}

ini_set('memory_limit','128M');
$pid = getmypid();
echo "start [$pid]\n";
$restart = true; //失败自动重启
do {
    $error = $apollo->start();
    if ($error) echo('error:'.$error."\n");
}while($error && $restart);