<?php
namespace app\index\common\lib;
use app\index\model\Adminlog;
class Alog{
    public static function login($adminId = 0,$type = 'success',$msg = ''){
        $type = strtolower($type);
        $type = ($type=='success') ? 1 : ($type=='fail'?2:0);
        $msg = ($type == 1) ? '登录成功' : ($type==2?'登陆失败':'未知错误');
        self::write($adminId,$msg,1,$type);
    }

    public static function action($adminId = 0,$msg = '操作记录'){
        self::write($adminId,$msg,2,0);
    }

    public static function write($userId,$msg,$setType,$setStatus){
        $data = [
            'admin_id'=>$userId,
            'ip'=>request()->ip(),
            'set_type'=>$setType,
            'set_status'=>$setStatus,
            'desc'=>$msg
        ];
        $adminlog = new AdminLog();
        $adminlog->isUpdate(false)->save($data);
    }
}