<?php

namespace app\index\controller;
use app\index\model\Admin;
use think\cache\driver\Memcached;
use think\Controller;
use app\index\common\lib\Alog;
use think\session\driver\Redis;

class Login extends Controller{
    public function index(){
        return $this->fetch();
    }

    public function checkLogin(){
        $post = input('post.');
        $__token__ = session('__token__');
        try{
            $admin_info = Admin::where(['username'=>$post['username']])->field('admin_id,username,password,email,phone,lock,login_count,is_super')->find();
        }catch(\Error $e){
            outputJson(5,'网络连接失败1');
        }catch(\Exception $e){
            outputJson(6,'网络连接失败2');
        }

        //用户名正确，但是密码输入错误5次之后账号会锁定，解锁之后才能重新使用
        if($admin_info){
            if($admin_info->lock == 2){
                Alog::login($admin_info->admin_id,'fail','账号锁定'.$post['username']);
                outputJson(3,'账号锁定');
            }
            $login_count = $admin_info->login_count+1;
            $loginData = [
                'admin_id'    => $admin_info->admin_id,
                'last_time'   => date('Y-m-d H:i:s'),
                'last_ip'     => request()->ip(),
                'login_count' => $login_count
            ];
            if($login_count>4){
                $loginData['lock'] = 2;
            }
            (new Admin())->isUpdate(true)->save($loginData);
            if($admin_info->password != $post['password']){
                Alog::login(0,'fail','账号或密码错误'.$post['username']);
                outputJson(2,'账号或密码错误');
            }
        }

        if(!$admin_info){
            Alog::login(0,'fail','账号或密码错误'.$post['username']);
            outputJson(2,'账号或密码错误');
        }

        $str = $admin_info->password.$__token__;
        $pwd = md5($str);

        if($pwd != $post['md_password']){
            Alog::login($admin_info->admin_id,'fail','令牌失效'.$post['username']);
            outputJson(4,'令牌失效');
        }
        $loginData = [
            'admin_id'    => $admin_info->admin_id,
            'last_time'   => date('Y-m-d H:i:s'),
            'last_ip'     => request()->ip(),
            'lock'        => 1,
            'login_count' => 0
        ];
        $loginInfo = [
            'admin_id' => $admin_info->admin_id,
            'username' => $admin_info->username,
            'email'    => $admin_info->email,
            'phone'    => $admin_info->phone,
            'lock'     => $admin_info->lock,
            'is_super' => $admin_info->is_super
        ];
        session('admin_info',$loginInfo);
        (new Admin())->isUpdate(true)->save($loginData);
        ALog::login($admin_info->admin_id, 'success');
        outputJson(1,'登录成功');


    }

    public function loginOut(){
        session('','admin_info');
        $this->redirect('/index/Login/index');
    }

}

