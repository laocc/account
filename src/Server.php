<?php

namespace laocc\account;

class Server extends Base
{
    public string $token;

    public function _init(string $token)
    {
        $this->token = $token;
    }

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