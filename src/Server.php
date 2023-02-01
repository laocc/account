<?php

namespace patch\account\src;

class Server extends Base
{

    /**
     * 返回给应用端的数据
     *
     * @param array $admin
     * @param array $auth
     * @param array $log
     * @return array
     */
    public function session(array $admin, array $auth, array $log): array
    {
        if ($auth['authRole'] & 256) {
            $power = $this->config('enum.authPower');
            $auth['authPower'] = ($auth['authPower'] | array_sum(array_keys($power)));
        }

        return [
            'id' => $admin['adminID'],
            'name' => $admin['adminName'],
            'auth' => $auth['authID'],
            'role' => $auth['authRole'],
            'power' => $auth['authPower'],
            'log' => $log['logID'],
            'salt' => $log['logSalt'],
        ];
    }

}