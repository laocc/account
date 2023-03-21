<?php

namespace laocc\account;

class Server extends Base
{

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