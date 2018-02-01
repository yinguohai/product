<?php
namespace app\index\validate;
use \think\Validate;
class ProductValidate extends Validate
{
    //验证规则
    protected $rule=[
        'encode'=>'require|max:30',
        'pro_name'=>'require|max:120',
        'pro_enname'=>'max:120',
        'zone'=>'max:20',
        'img'=>'max:60',
        'company'=>'max:20',

        'pro_id'=>'require',
        'v_num'=>'max:30',
        'source_href'=>'max:120',
        'v_name'=>'max:50'
    ];
    //提示信息
    protected $msg=[
        'encode.require'=>'编码不能为空',
        'encode.max'=>'编码不能超过50个字符',
        'pro_name.max'=>'产品名称不能超过120个字符',
        'pro_enname.max'=>'产品英文名称不能超过120个字符',
        'zone.max'=>'区域不能超过20个字符',
        'img.max'=>'图片链接不能超过60个字符',
        'company.max'=>'公司名称不能超过20个字符',

        'pro_id.require'=>'产品id不能为空',
        'v_num.max'=>'货号不能超过30个字符',
        'source_href'=>'货源链接不能超过120个字符',
        'v_name.max'=>'厂家名称不能超过50个字符'
    ];
    protected $scene=[
        'saveProduct'=>['encode','pro_name','pro_enname','zone','img','company'],
        'saveVender'=>['pro_id','v_num','source_href','v_name']
    ];

}