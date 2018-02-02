<?php
namespace app\index\logical;
class IndexLogical
{
    function __construct(Request $request = null)
    {

    }

    /**
     * 产品搜索条件整合
     * @param array $where
     * @return array|string
     */
    public function getProductWhere($condition=[])
    {

        if (empty($condition) or !is_array($condition))
            return '';
        $venderField = ['v_name', 'v_num', 'source_href'];
        $where=[];
        foreach ($condition as $k => $v) {
            if(empty($v))
                continue;
            if(strcasecmp($k,'page')===0)
                continue;

            if (in_array($k, $venderField)) {
                $where['v.' . $k] = ['like', '%' . $v . '%'];
            }elseif(strcasecmp($k,'starttime')===0) {
                $where['p.addtime'] = ['egt', $v];
            }elseif(strcasecmp($k,'endtime')===0){
                $where['p.addtime']=['elt',$v];
            }elseif(strcasecmp($k,'pro_id')===0){
                $where['p.pro_id']=['in',explode(',',$v)];
            }else{
                $where['p.'.$k]=['like','%'.$v.'%'];
            }
        }
        return $where;
    }

    public function getHandleWhere($where=[]){
        if(empty($where))
            return '';
        $condition=[];
        //替key中的点，以及 value中的%
        foreach($where as $k => $v){
            if(strpos($k,'p.')!==false){
                $k=str_replace('p.','',$k);
            }elseif(strpos($k,'v.')){
                $k=str_replace('v.','',$k);
            }
            $condition[$k]= is_array($v) ? json_encode($v) : trim(end($v),'%');
        }
        return $condition;
    }
}



