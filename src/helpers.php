<?php

namespace Ledc\Template;

use support\Redis;
use think\exception\ValidateException;
use think\Validate;

/**
 * Redis的setNx指令，支持同时设置ttl
 * - 使用lua脚本实现
 * @param string $key 缓存的key
 * @param string $value 缓存的value
 * @param int $ttl 存活的ttl，单位秒
 * @return bool
 */
function redis_set_nx(string $key, string $value, int $ttl = 10): bool
{
    static $scriptSha = null;
    if (!$scriptSha) {
        $script = <<<luascript
local result = redis.call('SETNX', KEYS[1], ARGV[1]);
if result == 1 then
    return redis.call('expire', KEYS[1], ARGV[2])
else
    return 0
end
luascript;
        $scriptSha = Redis::script('load', $script);
    }
    return (bool)Redis::rawCommand('evalsha', $scriptSha, 1, $key, $value, $ttl);
}

/**
 * Redis的incr指令，支持设置ttl
 * - 使用lua脚本实现
 * @param string $key 缓存的key
 * @param int $ttl 存活的ttl，单位秒
 * @return int
 */
function redis_incr(string $key, int $ttl = 10): int
{
    static $scriptSha = null;
    if (!$scriptSha) {
        $script = <<<luascript
if redis.call('set', KEYS[1], ARGV[1], "EX", ARGV[2], "NX") then
    return ARGV[1]
else
    return redis.call('incr', KEYS[1])
end
luascript;
        $scriptSha = Redis::script('load', $script);
    }
    return (int)Redis::rawCommand('evalsha', $scriptSha, 1, $key, 1, $ttl);
}

/**
 * 用Redis限流
 * - 使用lua脚本实现
 * @param string $key 限制资源：KEY
 * @param int $limit 限制规则：次数
 * @param int $window_time 窗口时间，单位：秒
 * @return int
 */
function redis_rate_limiter(string $key, int $limit, int $window_time = 10): int
{
    static $scriptSha = null;
    if (!$scriptSha) {
        $script = <<<luascript
if redis.call('set', KEYS[1], 1, "EX", ARGV[2], "NX") then
    return 1
else
    if tonumber(redis.call("GET", KEYS[1])) >= tonumber(ARGV[1]) then
        return 0
    else
        return redis.call("INCR", KEYS[1])
    end
end
luascript;
        $scriptSha = Redis::script('load', $script);
    }
    return (int)Redis::rawCommand('evalsha', $scriptSha, 1, $key, $limit, $window_time);
}

/**
 * thinkPHP验证器助手函数
 * @param array $data 待验证的数据
 * @param array|string $validate 验证器类名或者验证规则数组
 * @param array $message 错误提示信息
 * @param bool $batch 是否批量验证
 * @return bool|true
 * @throws ValidateException
 */
function validate(array $data, array|string $validate, array $message = [], bool $batch = false): bool
{
    if (is_array($validate)) {
        $v = new Validate();
        $v->rule($validate);
    } else {
        if (strpos($validate, '.')) {
            // 支持场景
            [$validate, $scene] = explode('.', $validate);
        }
        if (!class_exists($validate)) {
            throw new ValidateException('验证类不存在:' . $validate);
        }
        /** @var Validate $v */
        $v = new $validate();
        if (!empty($scene)) {
            $v->scene($scene);
        }
    }

    return $v->message($message)->batch($batch)->failException(true)->check($data);
}

/**
 * 简单的POST请求
 * - 用curl实现.
 *
 * @param string $url 请求地址
 * @param array|object $data 数据包
 * @param bool $isJsonRequest 是否Json请求
 * @param null|int $responseCode 最后的响应代码
 * @param int $curlErrorCode 返回错误代码或在没有错误发生时返回 0 (零)
 * @param string $curlErrorMessage 返回错误信息，或者如果没有任何错误发生就返回 '' (空字符串)
 */
function http_post_request_curl(string $url, array|object $data = [], bool $isJsonRequest = true, int &$responseCode = null, int &$curlErrorCode = 0, string &$curlErrorMessage = ''): bool|string
{
    if ($isJsonRequest) {
        $header = ['Content-Type: application/json; charset=UTF-8'];
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
    } else {
        $header = ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8'];
        $data = http_build_query($data);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    if (0 === stripos($url, 'https://')) {
        // false 禁止 cURL 验证对等证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 0 时不检查名称（SSL 对等证书中的公用名称字段或主题备用名称（Subject Alternate Name，简称 SNA）字段是否与提供的主机名匹配）
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    // 自动跳转，跟随请求Location
    curl_setopt($ch, CURLOPT_MAXREDIRS, 2);         // 递归次数
    // 代理配置
    if (getenv('CURLOPT_PROXY')) {
        curl_setopt($ch, CURLOPT_PROXY, getenv('CURLOPT_PROXY'));
    }
    $response = curl_exec($ch);
    $responseCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlErrorCode = curl_errno($ch);
    $curlErrorMessage = curl_error($ch);
    curl_close($ch);

    return $response;
}

/**
 * 获取当前版本commit.
 */
function current_git_commit(string $branch = 'master', bool $short = true): string
{
    $filename = sprintf(base_path() . '/.git/refs/heads/%s', $branch);
    clearstatcache();
    if (is_file($filename)) {
        $hash = file_get_contents($filename);
        $hash = trim($hash);
        return $short ? substr($hash, 0, 7) : $hash;
    }
    return '';
}

/**
 * 获取当前版本时间.
 */
function current_git_filemtime(string $branch = 'master', string $format = 'Y-m-d H:i:s'): string
{
    $filename = sprintf(base_path() . '/.git/refs/heads/%s', $branch);
    clearstatcache();
    if (is_file($filename)) {
        $time = filemtime($filename);
        return date($format, $time);
    }
    return '';
}
