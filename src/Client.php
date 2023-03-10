<?php

namespace laocc\account;

use esp\http\Http;
use esp\session\Session;

class Client extends Base
{
    private string $key;
    private string $api;
    private string $sessKey = 'admin';
    private int $active = 60;//秒
    private Session $session;

    public function _init(array $conf)
    {
        $this->sessKey = trim($conf['session'] ?? 'admin');
        $this->key = trim($conf['key']);
        $this->token = trim($conf['token']);
        $this->active = intval($conf['active'] ?? 60);
        $this->api = trim($conf['api'], '/');
        $this->session =& $this->_controller->_dispatcher->_session;
    }

    /**
     * 账户系统跳入
     *
     * @param string $sign
     * @param string $time
     * @param string $data
     * @return bool
     */
    public function jump(string $sign, string $time, string $data)
    {
        $admin = $this->parse_jump_url($sign, $time, $data);
        if (is_string($admin)) return $admin;

        $admin['sign'] = $this->sign($admin);
        $this->loginSave($admin);
        return true;
    }

    /**
     * 用户主动退出
     * @return bool|string
     */
    public function logout()
    {
        $admin = $this->session->get($this->sessKey);
        $this->loginSave([]);

        $data = $this->post('/site/logout', $admin);
        if (is_string($data)) return $data;

        return true;
    }


    /**
     * 控制中心请求删除本端某个session
     *
     * @return void
     */
    public function kill()
    {
        $json = file_get_contents("php://input");
        $post = json_decode($json, true);

        return $post;
    }

    /**
     * 加载本站所有管理账号
     *
     * @param int $role
     * @return mixed|string
     */
    public function load(int $role = 0)
    {
        $param = [];
        $param['role'] = $role;

        $admin = $this->post('/site/load', $param);
        if (is_string($admin)) return $admin;

        return $admin['admin'];
    }

    public function auth()
    {

    }

    /**
     * 请求微信授权登录的跳转URL
     *
     * @param bool $getJson 是否返回json，否则返回的是url
     * @return mixed|string
     */
    public function weixin(bool $getJson = false)
    {
        $param = [];
        $param['session'] = session_id();//当前用户的sessionID
        $param['get_json'] = intval($getJson);

        $admin = $this->post('/site/weixin', $param);
        if (is_string($admin)) return $admin;

        return $admin['redirect'];
    }

    /**
     * 客户端账密登录
     *
     * @param string $user
     * @param string $pwd
     * @return bool|string
     */
    public function login(string $user, string $pwd)
    {
        if (!$user or !$pwd) return '请填写账号密码';
//        if (!is_mob($user)) return '请填写正确的账号密码';

        $param = [];
        $param['user'] = $user;
        $param['password'] = $this->build_password($pwd);
        $param['session'] = session_id();//当前用户的sessionID

        $data = $this->post('/site/login', $param);
        if (is_string($data)) return $data;

        $admin = $data['admin'];
        $admin['sign'] = $this->sign($admin);
        $this->loginSave($admin);
        $this->session->set("active{$admin['salt']}", time());

        return true;
    }

    private function loginSave(array $info)
    {
        return $this->session->set($this->sessKey, $info);
    }

    private function signCheck(array $admin): bool
    {
        return $admin['sign'] === $this->sign($admin);
    }

    /**
     * 请求进入控制中心
     *
     * @return string|void
     */
    public function center()
    {
        $session = $this->session();
        if (is_string($session)) return $session;

        $data = $this->post('/site/center', $session);
        if (is_string($data)) return $data;

        return $data;
    }

    /**
     * 读取session
     *
     * @return array|string
     */
    public function session(bool $active = true)
    {
        $admin = $this->session->get($this->sessKey);
        if (!$admin or !($admin['id'] ?? 0)) return 'empty';
        if ($this->signCheck($admin) !== true) return 'error';//修改账号状态、权限、密码、登录盐值，都会强制账号重新登录
        unset($admin['sign']);
        if (!$active) return $admin;

        $lastTime = $this->session->get("active{$admin['salt']}");
        if ($this->_controller->_request->isGet() and (intval($lastTime) + $this->active) < time()) {
            $this->shutdown(function (array $admin) {
                $param = [];
                $param['id'] = $admin['id'];
                $param['log'] = $admin['log'];
                $param['salt'] = $admin['salt'];
                $param['session'] = session_id();//当前用户的sessionID
                $data = $this->post('/site/active', $param);
                if (is_string($data)) return $data;
                if (($data['action'] ?? '') === 'exit') $this->loginSave([]);
                return true;
            }, $admin);
            $this->session->set("active{$admin['salt']}", time());
        }

        return $admin;
    }

    private function post(string $uri, array $param)
    {
        $param['rand'] = microtime(true);
        $param['ip'] = _CIP;

        $http = new Http();
        $data = $http->encode('json')
            ->decode('json')
            ->headers('key', $this->key)
            ->data($param)
            ->sign($this->token)
            ->post("{$this->api}{$uri}");
        if ($e = $data->error()) return $e;

        return $data->data('data');
    }

}