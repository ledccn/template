<?php

namespace Ledc\Template\Middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * 允许跨域中间件.
 */
class AllowOrigin implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // 如果是OPTIONS请求则返回一个空响应，否则继续向洋葱芯穿越，并得到一个响应
        $response = 'OPTIONS' === strtoupper($request->method()) ? response('') : $handler($request);

        // 给响应添加跨域相关的http头
        $response->withHeaders([
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Origin' => $request->header('Origin', '*'),
            'Access-Control-Allow-Methods' => '*',
            'Access-Control-Allow-Headers' => '*',
        ]);

        return $response;
    }
}
