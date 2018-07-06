#!/usr/bin/env php
<?php
require 'vendor/autoload.php';
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
$restart = false; //失败自动重启
do {
    $error = $apollo->start();
    if ($error) echo('error:'.$error."\n");
}while($error && $restart);