<?php
namespace app\admin\model;

use think\cache\driver\Redis;
use think\Model;

class Click extends Model
{
	public $redis_obj;

	public function initialize()
	{
		$this->redis_obj = new Redis();
	}

	//查询click_lst的长度
	public function redis_select_count_click_lst()
	{
		if (!$this->redis_obj->exists('click_lst')) {
			$index_clickModel = model('index/Click');
			$index_clickModel->redis_click_lst();
		}
		return $this->redis_obj->Hlen('click_lst'); 
	} 

	//分页算法
	public function pjax_lst($input = null)
	{
		if (!$this->redis_obj->exists('click_id_zset')) {
			$index_clickModel = model('index/Click');
			$index_clickModel->redis_click_id_zset();
		}
		if (!$input) {
			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrevrange('click_id_zset',$start,$end);
		}else{
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$start = $input['page'] *10 -10;
			$end = $start + 9;
			if (isset($input['sort'])) {
				if ($input['sort'] == 'order') {
					$res = $this->redis_obj->Zrange('click_id_zset',$start,$end);
				}elseif ($input['sort'] == 'sort') {
					$res = $this->redis_obj->Zrevrange('click_id_zset',$start,$end);
				}else{
					$res = $this->redis_obj->Zrevrange('click_id_zset',$start,$end);
				}
			}else{
				$res = $this->redis_obj->Zrevrange('click_id_zset',$start,$end);
			}
		}
		if (!$res) {
			$res = $this->redis_obj->Zrevrange('click_id_zset',0,9);
		}
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('click_lst',$res);
	}
}