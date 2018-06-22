<?php

namespace Org\Multilinguals\Apollo\Client;

class ApolloClient
{
    protected $configServer; //apollo服务端地址
    protected $appId; //apollo配置项目的appid
    protected $cluster = 'default';
    protected $clientIp = '127.0.0.1'; //绑定IP做灰度发布用
    protected $notifications = [];
    public $save_dir; //配置保存目录

    /**
     * ApolloClient constructor.
     * @param string $configServer apollo服务端地址
     * @param string $appId apollo配置项目的appid
     * @param array $namespaces apollo配置项目的namespace
     */
    public function __construct($configServer, $appId, array $namespaces)
    {
        $this->configServer = $configServer;
        $this->appId = $appId;
        foreach ($namespaces as $namespace) {
            $this->notifications[$namespace] = ['namespaceName' => $namespace, 'notificationId' => -1];
        }
        $this->save_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    }

    public function setCluster($cluster)
    {
        $this->cluster = $cluster;
    }

    public function setClientIp($ip)
    {
        $this->clientIp = $ip;
    }

    //获取配置-无缓存的方式
    public function pull_config($namespaceName)
    {
        $base_api = rtrim($this->configServer, '/') . '/configs/' . $this->appId . '/' . $this->cluster . '/';
        $api = $base_api . $namespaceName;
        $args = [];
        $args['releaseKey'] = '';
        $args['ip'] = $this->clientIp;

        $config_file = $this->save_dir . DIRECTORY_SEPARATOR . 'apolloConfig.' . $namespaceName . '.php';
        if (file_exists($config_file)) {
            $last_config = require $config_file;
            is_array($last_config) && isset($last_config['releaseKey']) && $args['releaseKey'] = $last_config['releaseKey'];
        }

        $api .= '?' . http_build_query($args);

        $ch = curl_init($api);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            throw new \Exception($error);
        }

        if ($httpCode != 200 && $httpCode != 304) {
            throw new \Exception($body);
        }

        if ($httpCode == 200) {
            $result = json_decode($body, true);
            $content = '<?php return ' . var_export($result, true) . ';';
            file_put_contents($config_file, $content);
        }
    }

    /**
     * @param $callback 监听到配置变更时的回调处理
     * @return mixed
     */
    public function start($callback = null)
    {
        $base_url = rtrim($this->configServer, '/') . '/notifications/v2?';
        $params = [];
        $params['appId'] = $this->appId;
        $params['cluster'] = $this->cluster;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        try {
            do {
                $params['notifications'] = json_encode(array_values($this->notifications));
                $query = http_build_query($params);
                curl_setopt($ch, CURLOPT_URL, $base_url . $query);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                if ($response === false) {
                    throw new \Exception($error);
                }

                if ($httpCode != 200 && $httpCode != 304) {
                    throw new \Exception($response);
                }

                if ($httpCode == 200) {//如果配置有更新
                    $res = json_decode($response, true);
                    foreach ($res as $r) {
                        if ($r['notificationId'] != $this->notifications[$r['namespaceName']]['notificationId']) {
                            //拉取新配置
                            try {
                                $this->pull_config($r['namespaceName']);
                                //配置变更-更新notificationId
                                $this->notifications[$r['namespaceName']]['notificationId'] = $r['notificationId'];
                            } catch (\Exception $e) {
                                //某个namespace拉取配置失败的时候，不影响其它namespace
                                echo $e->getMessage() . "\n";
                            }
                        }
                    }
                    //如果定义了配置变更的回调，比如重新整合配置，则执行回调
                    ($callback instanceof \Closure) && call_user_func($callback);
                }
            } while (1);
        } catch (\Exception $e) {
            curl_close($ch);
            return $e->getMessage();
        }
    }
}