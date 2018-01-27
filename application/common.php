<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * 导出数据为excel表格
 *@param $data    一个二维数组,结构如同从数据库查出来的数组
 *@param $title   excel的第一行标题,一个数组,如果为空则没有标题
 *@param $filename 下载的文件名
 *@examlpe
$stu = M ('User');
$arr = $stu -> select();
exportexcel($arr,array('id','账户','密码','昵称'),'文件名!');
 */
function exportexcel($data=array(),$title=array(),$filename='report'){
    header("Content-type:application/octet-stream");
    header("Accept-Ranges:bytes");
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:attachment;filename=".$filename.".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    //导出xls 开始
    if (!empty($title)){
        foreach ($title as $k => $v) {
            $title[$k]=iconv("UTF-8", "GB2312",$v);
        }
        $title= implode("\t", $title);
        echo "$title\n";
    }
    if (!empty($data)){
        foreach($data as $key=>$val){
            foreach ($val as $ck => $cv) {
                $data[$key][$ck]=iconv("UTF-8", "GB2312", $cv);
            }
            $data[$key]=implode("\t", $data[$key]);
        }
        echo implode("\n",$data);
    }
}

function p($arr){
    echo '<pre/>';
    print_r($arr);
    die;
}
/**
 * 将数组中的指定列做为子元素列出来
 * @param $arr  需要查找的数组
 * @param $filed
 * @param $seperator 分隔符
 * @param $sort boolean  子数组是否排序
 * @param $onlychild boolean 是否只拿子数组
 */
function getChild($arr,$filed,$seperator,$sort=true,$multichild=false){
    if(empty($arr)or empty($filed)or empty($seperator))
        return [];
    $source=[];
    $result=[];
    foreach($arr as $k => $v){
        foreach($filed as $flag)
        {
            $source[$k][]=explode($seperator,$v[$flag]);
            unset($arr[$k][$flag]);
        }
    }
    foreach($source as $k => $v){
        $tmp=[];
        foreach ($v[0] as $kk => $vv) {
            $tmp[] = array_combine($filed, array_column($source[$k], $kk));
        }
        if(!empty($sort)){
            array_multisort(array_column($tmp,'v_sort'),$tmp);
        }
        if(isset($arr[$k]['source']) && $multichild){
            array_push($arr[$k]['source'],end($tmp));
        }else{
            $arr[$k]['source']=$tmp;
        }
    }
    return $arr;
}

/**
 * API打印函数
 * @param $code   状态码：   -2  ：失败 ，-1 ：  超时 ，1   ： 成功 , -3 没权限
 * @param $msg
 * @param $count
 * @param $data
 */
function outputJson($code,$msg,$count=0,$data='')
{
    exit(json_encode(array('code'=>$code,'msg'=>$msg,'count'=>$count,'data'=>$data)));
}

function getmultiarray($arr=[],$filed=[]){
    if(empty($arr) or empty($filed))
        return false;
    $tmp=[];
    $source=[];
    foreach($arr as $k => $v){
        if(in_array($k,$filed)){
            $source[$k]=$v;
        }
    }
    foreach($source[end($filed)] as $k=>$v){
        $tmp[]=array_merge( array_combine($filed, array_column($source,$k)),['v_edittime'=>time()]);
    }
    return $tmp;
}
/**
 * 模板输出数据
 */
function outputvars($var){
    if(isset($var) || !empty($var)){
        return $var;
    }
    return '';
}

/**
 * 上传文件函数
 * @param string $filename  上传文件名称
 * @return array|string 上传成功后的信息，只有失败才会返回 msg参数
 * @type  array 允许上传的文件类型
 */
function uploadFile($filename='',$path='',$type=[]){
    //默认只支持上传表格和图片类型的文件
    if(empty($type))
        $type=['xls','xlsx','jpeg','png','jpg','gif'];
    //默认上传到图片的的文件夹中去
    if(empty($path))
        $path=ROOT_PATH . 'public' . DS . 'uploads'.DS;
    if(empty($filename))
        return ['msg'=>'文件名不能为空'];
    $file = request()->file($filename);
    // 移动到框架应用根目录/public/uploads/ 目录下
    if($file){
        $info = $file->move($path);
        if(!in_array($info->getExtension(),$type)) {
            //判断上传的文件类型
            unlink($path . $info->getSaveName());
            return ['msg' => '文件类型有误'];
        }elseif($info){
            // 成功上传后 获取上传信息
            return ['ext'=>$info->getExtension(),'path'=>$path.$info->getSaveName(),'filename'=>$info->getFilename()];
        }else{
            // 上传失败获取错误信息
            return  ['msg'=>$file->getError()];
        }
    }
}