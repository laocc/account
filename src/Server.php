<?php

namespace laocc\account;

use esp\weiXin\items\Auth;

class Server extends Base
{
    public string $token;

    public function _init(string $token)
    {
        $this->token = $token;
    }

    /**
     * 受理应用端发来的数据，验签
     *
     * @param bool $checkSign
     * @return array|string
     */
    public function post(bool $checkSign = true): array|string
    {
        $post = file_get_contents('php://input');
        if ($checkSign) {
            $sign = $this->sign($post, $this->token);
            if (getenv('HTTP_SIGN') !== $sign) return 'sign error';
        }
        return json_decode($post, true);
    }

    /**
     * 组合微信登录URL
     *
     * @return string|void
     */
    public function auth(array $open)
    {
//        $open = $this->config('setup.wxweb');
        if (empty($open)) return null;
        extract($open + ['appid' => '', 'secret' => '', 'domain' => '', 'back' => '', 'redirect' => '']);
        $auth = new Auth($appid, $secret);
        if (!$domain) $domain = (_HTTPS ? 'https:' : 'http:') . '//' . _DOMAIN;//当前域名
        if (!$redirect) $redirect = urlencode($domain . '/login/auth/');//微信跳回验证之后，再次跳转，这只是下面back的一部分
        if (!$back) $back = "/login/redirect/login/{$redirect}";//微信跳回的URL
        return $auth->redirect("{$domain}{$back}", 3);//微信跳回
    }

    /**
     * 返回给应用端的数据
     *
     * @param array $admin
     * @param array $site
     * @param array $auth
     * @param array $log
     * @param array $powers
     * @return array
     */
    public function session(array $admin, array $site, array $auth, array $log, array $powers): array
    {
        if ($auth['authRole'] & 256) {
            $auth['authPower'] = ($auth['authPower'] | array_sum(array_keys($powers)));
        }

        return [
            'id' => $admin['adminID'],
            'user' => $admin['adminUser'],
            'name' => $admin['adminName'],
            'auth' => $auth['authID'],
            'role' => $auth['authRole'],
            'power' => $auth['authPower'],
            'log' => $log['logID'],
            'salt' => $log['logSalt'],
        ];
    }

}