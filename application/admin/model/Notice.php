<?php 
namespace app\admin\model;

use think\Model;
use think\cache\driver\Redis;

class Notice extends Model
{
	public $redis_obj;

	public function initialize()
	{
		$this->redis_obj = new Redis();
	}
/****************************查询数据*********************************/
	//查询所有notice_lst_all数据或者长度
	public function redis_select_notice_lst_all($count = false)		//如果为true则返回所有数据长度
	{
		if ($c = $this->redis_obj->Hlen('notice_lst_all')) {
			if ($count) {
				return $c;
			}else{
				return $this->redis_obj->Hgetall('noticle_lst_all');
			}
		}else{
			$res = $this->redis_notice_lst_all(true);
			if ($count) {
				return count($res);
			}else{
				return $res;
			}
		}
	}
	//查询单条数据 在 notice_lst_all中
	public function redis_select_notice_lst_all_one($notice_id)
	{
		if ($this->redis_obj->exists('notice_lst_all')) {
			return $this->redis_obj->Hget('notice_lst_all',$notice_id);
		}else{
			$this->redis_notice_lst_all(true);
			return $this->redis_obj->Hget('notice_lst_all',$notice_id);
		}
	}
	//查询notice_id_zset的长度
	public function redis_select_notice_id_zset_count()
	{
		if ($count = $this->redis_obj->Zcard('notice_id_zset')) {
			return $count;
		}else{
			return count($this->redis_notice_id_zset(true));
		}
	}
	//查询notice_show_id_zset的长度
	public function redis_select_notice_show_id_zset_count()
	{
		if ($count = $this->redis_obj->Zcard('notice_show_id_zset')) {
			return $count;
		}else{
			return count($this->redis_notice_show_id_zset(true));
		}
	}
	//查询notice_top_id_zset的长度
	public function redis_select_notice_top_id_zset_count()
	{
		if ($count = $this->redis_obj->Zcard('notice_top_id_zset')) {
			return $count;
		}else{
			return count($this->redis_notice_top_id_zset(true));
		}
	}
/****************************逻辑入口*********************************/
	//新增一条数据
	public function insert_one($data)		//将要新增的数据
	{
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		if (!isset($data['show'])) {
			$data['show'] = 0;
		}
		if (!isset($data['top'])) {
			$data['top'] = 0;
		}
		$data['time'] = time();

		if ($this->save($data)) {
			$list = $this->where(['id'=>$this->id])->find();

			$this->redis_notice_lst_one($this->id,$list);
			$this->redis_notice_lst_all_one($this->id,$list);
			$this->redis_notice_id_zset_one($this->id);
			if ($list['show'] == 1) {
				$this->redis_notice_show_id_zset_one($this->id);
			}
			if ($list['top'] == 1) {
				$this->redis_notice_top_id_zset_one($this->id);
			}else{
				if ($list['show'] == 1) {
					$this->redis_notice_no_top_show_id_zset_one($this->id);
				}
			}
		}else{
			return '新增失败，请刷新后重新尝试';
		}
	}

	//修改一条数据
	public function update_one($data)		//将要修改成的数据
	{
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		if (!isset($data['show'])) {
			$data['show'] = 0;
		}
		if (!isset($data['top'])) {
			$data['top'] = 0;
		}
		if (!$old_data = $this->where(['id'=>$data['id']])->find()) {			//修改前数据
			return '操作失误,请刷新后重新尝试';
		}
		if ($this->update($data)) {
			$new_data = $this->where(['id'=>$data['id']])->find();				//修改后数据
			$this->redis_notice_lst_one($new_data['id'],$new_data);
			$this->redis_notice_lst_all_one($new_data['id'],$new_data);
			if ($old_data['show'] == '0' && $new_data['show'] == '1') {			//表明之前没有展示 修改之后变成展示

				if ($old_data['top'] == '0' && $new_data['top'] == '1') {
					$this->redis_notice_top_id_zset_one($new_data['id']);
				}elseif($old_data['top'] == '1' && $new_data['top'] == '0'){
					$this->redis_del_notice_no_top_show_id_zset_one($new_data['id']);
				}
				$this->redis_notice_show_id_zset_one($new_data['id']);

			}elseif($old_data['show'] == '1' && $new_data['show'] == '1'){		//表示之前有展示，修改之后依旧展示
				
				if ($old_data['top'] == '0' && $new_data['top'] == '1') {
					$this->redis_notice_top_id_zset_one($new_data['id']);
				}elseif($old_data['top'] == '1' && $new_data['top'] == '0'){
					$this->redis_del_notice_top_id_zset_one($new_data['id']);
					$this->redis_notice_no_top_show_id_zset_one($new_data['id']);
				}

			}elseif($old_data['show'] == '1' && $new_data['show'] == '0'){		//表示之前展示，修改之后不展示了。
				
				if ($old_data['top'] == '1') {
					$this->redis_del_notice_top_id_zset_one($new_data['id']);
				}else{
					$this->redis_del_notice_no_top_show_id_zset_one($new_data['id']);
				}
				$this->redis_del_notice_show_id_zset_one($new_data['id']);
			}

		}else{
			return '操作失误,请刷新后重新尝试';
		}
	}

	//删除一条数据or多条数据
	public function delete_more($data)		//一维数组 value为公告id
	{
		if ($this->destroy($data)) {
			foreach ($data as $key => $value) {
				$this->redis_del_notice_lst_one($value);
				$this->redis_del_notice_lst_all_one($value);
				$this->redis_del_notice_id_zset_one($value);
				$this->redis_del_notice_show_id_zset_one($value);
				$this->redis_del_notice_top_id_zset_one($value);
				$this->redis_del_notice_no_top_show_id_zset_one($value);
			}
		}else{
			return '删除失败，请刷新后重新尝试';
		}
	}
/**************************redis新增/刷新一条*************************/
	//刷新一条数据到 notice_lst中
	private function redis_notice_lst_one($notice_id,$data = null)		//notice_id:将要刷新的数据id,data:将要刷新的数据，可有和无，如果没有再从数据库中查
	{
		if ($data) {
			$list['id'] = $notice_id;
			$list['text'] = $data['text'];
			$list['time'] = $data['time'];
		}else{
			$list = $this->where(['id'=>$notice_id])->field('id,text,time')->find();
		}
		if ($this->redis_obj->exists('notice_lst')) {
			$this->redis_obj->Hset('notice_lst',$notice_id,$list);
		}else{
			$this->redis_notice_lst();
		}
	}

	//刷新一条数据到 notice_lst_all中
	private function redis_notice_lst_all_one($notice_id,$data = null)		//此处data为完整数据，可有可无
	{
		if (!$data) {
			$data = $this->where(['id'=>$notice_id])->find();
		}
		if ($this->redis_obj->exists('notice_lst_all')) {
			$this->redis_obj->Hset('notice_lst_all',$notice_id,$data);
		}else{
			$this->redis_notice_lst_all();
		}
	}

	//刷新一条数据到 notice_id_zset中
	private function redis_notice_id_zset_one($notice_id)					//notice_id:将要刷新的数据id
	{
		if ($this->redis_obj->exists('notice_id_zset')) {
			$this->redis_obj->Zadd('notice_id_zset',$notice_id,$notice_id);
		}else{
			$this->redis_notice_id_zset();
		}
	}

	//刷新一条数据到 notice_show_id_zset中
	private function redis_notice_show_id_zset_one($notice_id)				//notice_id:将要刷新的数据id
	{
		if ($this->redis_obj->exists('notice_show_id_zset')) {
			$this->redis_obj->Zadd('notice_show_id_zset',$notice_id,$notice_id);
		}else{
			$this->redis_notice_show_id_zset();
		}
	}

	//刷新一条数据到 notice_top_id_zset
	private function redis_notice_top_id_zset_one($notice_id)
	{
		if ($this->redis_obj->exists('notice_top_id_zset')) {
			$this->redis_obj->Zadd('notice_top_id_zset',$notice_id,$notice_id);
		}else{
			$this->redis_notice_top_id_zset();
		}
	}

	//刷新一条数据到 notice_no_top_show_id_zset
	private function redis_notice_no_top_show_id_zset_one($notice_id)
	{
		if ($this->redis_obj->exists('notice_no_top_show_id_zset')) {
			$this->redis_obj->Zadd('notice_no_top_show_id_zset',$notice_id,$notice_id);
		}else{
			$this->redis_notice_no_top_show_id_zset();
		}
	}
/**************************redis删除一条数据**************************/
	//删除redis中notice_lst中的一条数据
	private function redis_del_notice_lst_one($notice_id)			//公告id
	{
		$this->redis_obj->Hdel('notice_lst',$notice_id);
	}
	//删除redis中notice_lst_all中的一条数据
	private function redis_del_notice_lst_all_one($notice_id)		//公告id
	{
		$this->redis_obj->Hdel('notice_lst_all',$notice_id);
	}
	//删除redis中notice_id_zset的一条数据
	private function redis_del_notice_id_zset_one($notice_id)		//公告id
	{
		$this->redis_obj->Zrem('notice_id_zset',$notice_id);
	}
	//删除redis中notice_show_id_zset的一条数据
	private function redis_del_notice_show_id_zset_one($notice_id)		//公告id
	{
		$this->redis_obj->Zrem('notice_show_id_zset',$notice_id);
	}
	//删除redis中notice_top_id_zset的一条数据
	private function redis_del_notice_top_id_zset_one($notice_id)		//公告id
	{
		$this->redis_obj->Zrem('notice_top_id_zset',$notice_id);
	}
	//删除redis中notice_no_top_show_id_zset的一条数据
	private function redis_del_notice_no_top_show_id_zset_one($notice_id)		//公告id
	{
		$this->redis_obj->Zrem('notice_no_top_show_id_zset',$notice_id);
	}
/**************************redis刷新全部******************************/
	//刷新notice_lst全部数据
	public function redis_notice_lst()
	{
		$data = $this->field('id,text,time')->select();
		$list = [];
		foreach ($data as $key) {
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('notice_lst',$list);
	}

	//刷新notice_lst_all所有数据
	private function redis_notice_lst_all($re = false)
	{
		$data = $this->select();
		$list = [];
		foreach ($data as $key) {
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('notice_lst_all',$list);
		if ($re) {
			return $data;
		}
	}

	//刷新notice_id_zset所有数据
	private function redis_notice_id_zset($re = false)
	{
		$data = $this->field('id')->select();
		foreach ($data as $key) {
			$this->redis_obj->Zadd('notice_id_zset',$key['id'],$key['id']);
		}
		if ($re) {
			return $data;
		}
	}

	//刷新notice_show_id_zset所有数据
	private function redis_notice_show_id_zset($re = false)
	{
		$data = $this->where(['show'=>'1'])->select();
		foreach ($data as $key) {
			$this->redis_obj->Zadd('notice_show_id_zset',$key['id'],$key['id']);
		}
		if ($re) {
			return $data;
		}
	}

	//刷新notice_top_id_zset所有数据
	public function redis_notice_top_id_zset($re = false)
	{
		$data = $this->where(['top'=>'1'])->select();
		foreach ($data as $key) {
			$this->redis_obj->Zadd('notice_top_id_zset',$key['id'],$key['id']);
		}
		if ($re) {
			return $data;
		}
	}

	//刷新notice_no_top_show_id_zset全部数据
	public function redis_notice_no_top_show_id_zset()
	{
		$data = $this->where(['top'=>'0','show'=>'1'])->select();
		foreach ($data as $key) {
			$this->redis_obj->Zadd('notice_no_top_show_id_zset',$key['id'],$key['id']);
		}
	}
/****************************验证数据*********************************/
	//验证可供展示的公告数量与置顶数量
	public function check_notice_count($data = null)		//此传参为将要修改的数据,需要完整的数据，如果有值则表示是修改的数据验证，否则是新增数据的验证
	{
		if ($data) {		//表明是修改的数据验证
			if ($data['show'] == '1') {
				$res = $this->where(['show'=>'1'])->field('id')->select();
				if (count($res) >= '5') {
					$now_id = '';
					foreach ($res as $key) {
						if ($key['id'] == $data['id']) {
							$now_id = $data['id'];
						}
					}
					if ($now_id == '') {
						return '展示的公告最多只能有5个';
					}
				}
			}
		}else{
			$res = $this->where(['show'=>'1'])->count();
			if ($res >= 5) {
				return '展示的公告最多只能有5个';
			}
		}
	}
/****************************分页算法*********************************/
	//分页算法
	public function pjax_lst($count,$input = null)		//所有数据长度(notice_lst_all)		接收到的传参
	{	
		if (!$input) {			//表明没有条件 则默认
			$zcount = $this->redis_obj->Zcard('notice_id_zset');
			if ($zcount != $count) {
				$this->redis_notice_id_zset();
			}
			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrange('notice_id_zset',$start,$end);
		}else{
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$start = $input['page']*10-10;
			$end = $start+9;
			if (isset($input['see'])) {				//因为之前已经查过所需要的表了 所以不用再验证数据是否完整
				if ($input['see'] == 'show') {
					if (isset($input['sort'])) {
						if ($input['sort'] == 'order') {
							$res = $this->redis_obj->Zrange('notice_show_id_zset',$start,$end);
						}elseif($input['sort'] == 'sort'){
							$res = $this->redis_obj->Zrevrange('notice_show_id_zset',$start,$end);
							if (!$res) {
								$res = $this->redis_obj->Zrevrange('notice_show_id_zset',0,9);
							}
						}else{
							$res = $this->redis_obj->Zrange('notice_show_id_zset',$start,$end);
						}
					}else{
						$res = $this->redis_obj->Zrange('notice_show_id_zset',$start,$end);
					}
				}elseif ($input['see'] == 'top') {
					if (isset($input['sort'])) {
						if ($input['sort'] == 'order') {
							$res = $this->redis_obj->Zrange('notice_top_id_zset',$start,$end);
						}elseif ($input['sort'] == 'sort') {
							$res = $this->redis_obj->Zrevrange('notice_top_id_zset',$start,$end);
							if (!$res) {
								$res = $this->redis_obj->Zrevrange('notice_top_id_zset',0,9);
							}
						}else{
							$res = $this->redis_obj->Zrange('notice_top_id_zset',$start,$end);
						}
					}else{
						$res = $this->redis_obj->Zrange('notice_top_id_zset',$start,$end);
					}
				}else{
					if (isset($input['sort'])) {
						if ($input['sort'] == 'order') {
							$res = $this->redis_obj->Zrange('notice_id_zset',$start,$end);
						}elseif ($input['sort'] == 'sort') {
							$res = $this->redis_obj->Zrevrange('notice_id_zset',$start,$end);
						}else{
							$res = $this->redis_obj->Zrange('notice_id_zset',$start,$end);
						}
					}else{
						$res = $this->redis_obj->Zrange('notice_id_zset',$start,$end);
					}
				}
			}else{
				if (isset($input['sort'])) {
					if ($input['sort'] == 'order') {
						$res = $this->redis_obj->Zrange('notice_id_zset',$start,$end);
					}elseif ($input['sort'] == 'sort') {
						$res = $this->redis_obj->Zrevrange('notice_id_zset',$start,$end);
					}else{
						$res = $this->redis_obj->Zrange('notice_id_zset',$start,$end);
					}
				}else{
					$res = $this->redis_obj->Zrange('notice_id_zset',$start,$end);
				}
			}
		}
		$list = $this->redis_obj->Hmget('notice_lst_all',$res);
		return $list;
	}
}