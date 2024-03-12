<?php

namespace laocc\account;

use esp\core\Library;

abstract class Base extends Library
{
    protected string $token;

    public function encode_url(array $data): string
    {
        return urlencode(base64_encode(json_encode($data)));
    }

    public function build_password(string $password): string
    {
        return base64_encode(serialize(gzcompress($password)));
    }

    public function parse_password(string $pass): string
    {
        return gzuncompress(unserialize(base64_decode($pass)));
    }

    /**
     * 组合跳入子站的URL
     *
     * @param array $data
     * @param string $token
     * @param string $domain
     * @return string
     */
    public function build_jump_url(array $data, string $token, string $domain): string
    {
        $time = time();
        $url = urlencode(base64_encode(json_encode($data)));
        $sign = md5("{$url}.{$time}.{$token}");
        $this->debug(['string' => "{$url}.{$time}.{$token}"]);

        $domain = trim($domain, '/');
        $len = explode('/', $domain);
        if (count($len) <= 3) $domain = "{$domain}/account/jump";

        return "{$domain}/{$sign}/{$time}/{$url}/jump.do";
    }

    /**
     * 跳入站时检查URL数据是否合法，并返回相关数据
     *
     * @param string $sign
     * @param string $time
     * @param string $data
     * @return array|string
     */
    public function parse_jump_url(string $sign, string $time, string $data): array|string
    {
        if (abs(time() - intval($time)) > 3) return '链接已失效';
        $this->debug(['string' => "{$data}.{$time}.{$this->token}"]);
        if (md5("{$data}.{$time}.{$this->token}") !== $sign) return '非法请求';
        return json_decode(base64_decode(urldecode($data)), true);
    }

    protected function sign(string $post, string $token): string
    {
        return md5($post . $token);
    }

}