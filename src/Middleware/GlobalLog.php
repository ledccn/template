<?php

namespace Ledc\Template\Middleware;

use support\Context;
use support\Log;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * 全局日志中间件.
 */
class GlobalLog implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $start = microtime(true);
        // 获取请求信息
        $data = [
            'ip' => $this->getIp($request),
            'uri' => $request->uri(),
            'method' => $request->method(),
            'appid' => '', // TODO 业务数据，如果项目中可直接获取到appid，记录在此处
            'trace_id' => $request->header('trace_id', md5(microtime())),
            'referer' => $request->header('referer'),
            'user_agent' => $request->header('user-agent'),
            'query' => $request->all(),
            'cookie' => $request->cookie(),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // 记录全局trace_id
        Context::set('trace_id', $data['trace_id']);

        /** @var Response $response */
        $response = $handler($request);

        $err = $response->exception();
        $res = [];
        if ($err instanceof \Exception) {
            // 这个统一异常时的接口响应
            $trace = [$err->getMessage(), $err->getFile(), $err->getLine(), $err->getTraceAsString()];
            $data['exception'] = json_encode($trace, JSON_UNESCAPED_UNICODE);
            Log::error('server error', $trace);
            if (env('APP_DEBUG')) {
                $res = ['msg' => '服务异常', 'trace' => $trace];
            } else {
                $res = ['msg' => '服务异常'];
            }
        }

        $data['errcode'] = $response->getStatusCode();
        $data['response'] = $res ? json_encode($res, JSON_UNESCAPED_UNICODE) : $response->rawBody();
        $end = microtime(true);
        $exec_time = round(($end - $start) * 1000, 2);
        $data['exec_time'] = $exec_time;
        // 投递到异步队列
        Redis::send('global-log', $data);

        return $response;
    }

    /**
     * @return false|string
     */
    private function getIp(Request $request): bool|string
    {
        $forward_ip = $request->header('X-Forwarded-For');
        $ip1 = $request->header('x-real-ip');
        $ip2 = $request->header('remote_addr');
        if (!$ip1 && !$ip2 && !$forward_ip) {
            return false;
        }
        $request_ips = [];
        if ($forward_ip) {
            $request_ips[] = $forward_ip;
        }
        if ($ip1) {
            $request_ips[] = $ip1;
        }
        if ($ip2) {
            $request_ips[] = $ip2;
        }

        return implode(',', $request_ips);
    }
}
