<?php

namespace Ledc\Template\Middleware;

use support\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * Token转换Session
 * - 从请求头获取token值，设置session_id.
 */
class SessionId implements MiddlewareInterface
{
    public function process(Request|\Webman\Http\Request $request, callable $handler): Response
    {
        $token = $request->header('token', $request->cookie('token'));
        if ($token && ctype_alnum($token) && strlen($token) <= 70) {
            $request->sessionId($token);
        }

        return $handler($request);
    }
}
