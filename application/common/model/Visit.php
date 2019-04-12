<?php
namespace app\common\model;

use think\Model;
use think\cache\driver\Redis;

class Visit extends Model
{
	public $redis_obj;

	public function initialize()
	{
		$this->redis_obj = new Redis();
	}

	//刷新一条数据 visit_lst
	public function redis_visit_lst_one($id,$data = null)	//visit表id，data可有可无，如果有一定要对应字段的数组
	{
		if (!$data) {	
			$data = $this->where(['id'=>$id])->find();
		}
		if ($this->redis_obj->exists('visit_lst')) {
			$this->redis_obj->Hset('visit_lst',$id,$data);
		}else{
			redis_visit_lst();
		}
	}

	//刷新一条数据 visit_id_zset
	public function redis_visit_id_zset_one($id)	//visit表id
	{
		if ($this->redis_obj->exists('visit_id_zset')) {
			$this->redis_obj->Zadd('visit_id_zset',$id,$id);
		}else{
			redis_visit_id_zset();
		}
	}

	//刷新全部数据 visit_lst
	public function redis_visit_lst()
	{
		$res = $this->select();
		$list = [];
		foreach ($res as $key) {
			$list[$key['id']] = json_encode($key,true);
		}
		$this->redis_obj->Hmset('visit_lst',$list);
	}

	//刷新全部数据 visit_id_zset
	public function redis_visit_id_zset()
	{
		$res = $this->field('id')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('visit_id_zset',$key['id'],$key['id']);
		}
	}
}