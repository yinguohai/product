<?php
namespace app\index\model;
use think\Model;
class Adminlog extends Model{
    protected $autoWriteTimestamp = 'datetime';
    protected $updateTime = false;
}