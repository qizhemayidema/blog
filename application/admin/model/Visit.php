<?php
namespace app\admin\model;

use think\Model;
use think\cache\driver\Redis;

class Visit extends Model
{
	public $redis_obj;

	public function initialize()
	{
		$this->redis_obj = new Redis();
	}

/*****************查询长度*************************/
	public function select_count_redis_visit_lst()
	{
		if ($count = $this->redis_obj->Hlen('visit_lst')) {
			return $count;
		}else{
			$common_visitModel = model('common/Visit');
			$common_visitModel->redis_visit_lst();
			return $this->redis_obj->Hlen('visit_lst');
		}
	}
/****************分页算法************************/
	public function pjax_lst($input = null)
	{
		if (!$this->redis_obj->exists('visit_id_zset')) {		//visit_id_zset
			$common_visitModel = model('common/Visit');
			$common_visitModel->redis_visit_id_zset();
		}
		if (!$input) {
			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrevrange('visit_id_zset',$start,$end);
		}else{
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$start = $input['page'] *10 -10 ;
			$end = $start+9;
			if (isset($input['sort'])) {
				if ($input['sort'] == 'order') {
					$res = $this->redis_obj->Zrange('visit_id_zset',$start,$end);
				}elseif ($input['sort'] == 'sort') {
					$res = $this->redis_obj->Zrevrange('visit_id_zset',$start,$end);
				}else{
					$res = $this->redis_obj->Zrevrange('visit_id_zset',$start,$end);
				}
			}else{
				$res = $this->redis_obj->Zrevrange('visit_id_zset',$start,$end);
			}
		}

		if (!$res) {
			$res = $this->redis_obj->Zrevrange('visit_id_zset',0,9);
		}
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('visit_lst',$res);
	}
}