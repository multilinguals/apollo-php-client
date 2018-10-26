#!/usr/bin/env php
<?php
require 'vendor/autoload.php';
use Org\Multilinguals\Apollo\Client\ApolloClient;

define('SAVE_DIR', __DIR__); //定义apollo配置本地化存储路径

//指定env模板和文件
define('ENV_DIR', __DIR__.DIRECTORY_SEPARATOR.'env');
define('ENV_TPL', ENV_DIR.DIRECTORY_SEPARATOR.'.env_tpl.php');
define('ENV_FILE', ENV_DIR.DIRECTORY_SEPARATOR.'.env');

//定义apollo配置变更时的回调函数，动态异步更新.env
$callback = function () {
    $list = glob(SAVE_DIR.DIRECTORY_SEPARATOR.'apolloConfig.*');
    $apollo = [];
    foreach ($list as $l) {
        $config = require $l;
        if (is_array($config) && isset($config['configurations'])) {
            $apollo = array_merge($apollo, $config['configurations']);
        }
    }
    if (!$apollo) {
        throw new Exception('Load Apollo Config Failed, no config available');
    }
    ob_start();
    include ENV_TPL;
    $env_config = ob_get_contents();
    ob_end_clean();
    file_put_contents(ENV_FILE, $env_config);
};

//指定apollo的服务地址
$server = 'http://127.0.0.1:8081';

//指定appid
$appid = 'demo';

//指定要拉取哪些namespace的配置
$namespaces = ['application', 'public.mysql', 'public.redis'];

$apollo = new ApolloClient($server, $appid, $namespaces);

//如果需要灰度发布，指定clientIp
/*
 * $clientIp = '10.160.2.131';
 * if (isset($clientIp) && filter_var($clientIp, FILTER_VALIDATE_IP)) {
 *    $apollo->setClientIp($clientIp);
 * }
 */

//从apollo上拉取的配置默认保存在脚本目录，可自行设置保存目录
$apollo->save_dir = SAVE_DIR;

ini_set('memory_limit','128M');
$pid = getmypid();
echo "start [$pid]\n";
$restart = false; //失败自动重启
do {
    $error = $apollo->start($callback); //此处传入回调
    if ($error) echo('error:'.$error."\n");
}while($error && $restart);
