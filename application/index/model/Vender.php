<?php
namespace app\index\model;
use think\Model;
use think\Db;
class Vender extends Model{
    protected $field=true;
    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * 保存来源
     * @param $data
     * @param bool $multi  ,是否保存多条数据
     * @param bool $type  是否返回新增的id号
     * @return bool|false|int
     */
    public function addVender($data,$multi=false,$type=false){
        if(empty($data))
            return false;
        try{
            if(empty($multi)){
                $result=$this->allowField(true)->save($data);
            }else{
                if($type){
                    $this->allowField(true)->insertAll($data);
                    $result=$this->getLastInsID();
                }else{
                    $result=$this->allowField(true)->saveAll($data);
                }
            }
        }catch(\Error $e){
            return false;
        }catch(\Exception $e){
            return false;
        }
        return $result;
    }
    /**
     * 获取来源信息
     */
    public function listVender($where){
        $result=$this->where($where)->order(['v_sort'])->select();
        $result=collection($result)->toArray();
        return $result;
    }

    /**
     * 批量更新产品来源信息
     * @param $data 二维数据组，用于存放产品信息源
     * @return bool
     */
    public function editVender($data){
        try{
            $this->isUpdate()->saveAll($data);
        }catch(\Error $e){
            return false;
        }catch(\Exception $e){
            return false;
        }
        return true;
    }

    /**
     * 批量删除产品源
     */
    public function deleteVender($vids){
        try{
            $pro_id=$this->where(['v_id'=>['eq',end($vids)]])->value('pro_id');
            $total=$this->where(['pro_id'=>['eq',$pro_id]])->count('*');
            if(count($vids) < $total){
                $result=$this->where(['v_id'=>['in',$vids]])->delete();
            }else{
                return ['status'=>2,'msg'=>'产品源至少保留一个'];
            }
        }catch(\Error $e){
            return ['status'=>2,'msg'=>$e->getMessage()];
        }catch(\Exception $e){
            return ['status'=>2,'msg'=>$e->getMessage()];
        }
        if(empty($result))
            return ['status'=>1,'msg'=>'删除成功'];
        return ['status'=>2,'msg'=>'删除失败'];
    }
}