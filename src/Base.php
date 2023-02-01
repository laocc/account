<?php

namespace patch\account\src;

use esp\core\Library;

abstract class Base extends Library
{

    public function encode_url(array $data): string
    {
        return urlencode(base64_encode(json_encode($data)));
    }

    public function build_password(string $password): string
    {
        return base64_encode(serialize($password));
    }

    public function parse_password(string $pass): string
    {
        return unserialize(base64_decode($pass));
    }


    public function build_jump_url(array $data): string
    {
        $time = time();
        $url = urlencode(base64_encode(json_encode($data)));
        $sign = md5("{$url}.{$time}.accountJUMP");
        return "/account/jump/{$sign}/{$time}/{$url}";
    }

    public function parse_jump_url(string $sign, string $time, string $data)
    {
        if (abs(time() - intval($time)) > 3) return '链接已失效';
        if (md5("{$data}.{$time}.accountJUMP") !== $sign) return '非法请求';
        return json_decode(base64_decode(urldecode($data)), true);
    }


    protected function sign(array $admin): string
    {
        unset($admin['sign']);
        ksort($admin);
        $str = [];
        foreach ($admin as $k => $v) {
            if ($v === '' or is_null($v)) continue;
            if (is_bool($v)) $v = intval($v);
            else if (is_array($v)) $v = json_encode($v, 320);
            if (!is_string($v)) $v = strval($v);
            $str[] = "{$k}={$v}";
        }
        return md5(implode('&', $str));
    }

}