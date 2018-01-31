<?php

namespace app\index\controller;
use think\Controller;

class Mycontroller extends Controller{

    public function _initialize(){
        parent::_initialize();
        self::isLogin();
    }

    private function isLogin(){
        $admin_info = session('admin_info');
        if(!$admin_info){
            $this->redirect('/index/Login/index');
        }
        $this->assign('admin_info',$admin_info);
    }

    /**
     * 空操作
     * @param mixed
     * Author: huxupan <787122933@qq.com>
     */
    public function _empty($name)
    {
        $this->error('你访问的地址不存在');
    }
}