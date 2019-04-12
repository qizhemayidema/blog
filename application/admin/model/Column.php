<?php 
namespace app\admin\model;

use think\Model;
use think\cache\driver\Redis;

class Column extends Model
{
	public $redis_obj;

	public function initialize()
	{
		$this->redis_obj = new Redis();
	}
/*****************逻辑入口***************************/

	//栏目下加一文章		+1动作
	public function article_count_up_one($column_id)
	{
		$list = $this->where(['id'=>$column_id])->field('id,article_count')->find();
		$this->update(['article_count'=>$list['article_count']+1],['id'=>$list['id']]);
		$this->redis_column_lst_all_one($column_id);
	}
	//栏目下减一文章		-1动作
	public function article_count_down_one($column_id)
	{
		$list = $this->where(['id'=>$column_id])->field('id,article_count')->find();
		$this->update(['article_count'=>$list['article_count']-1],['id'=>$list['id']]);
		$this->redis_column_lst_all_one($column_id);
	}
	//新增一条数据
	public function insert_one($data)		//将要新增的数据
	{
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		if (!isset($data['show'])) {
			$data['show'] = 0;
		}
		$data['time'] = time();
		$data['article_count'] = 0;
		if ($this->save($data)) {
			$data['id'] = $this->id;
			$this->redis_column_lst_one($this->id,$data);
			$this->redis_column_lst_all_one($this->id,$data);
			$this->redis_column_id_zset_one($this->id);
			if ($data['show'] == '1') {
				$this->redis_column_show_id_zset_one($this->id);
			}
		}else{
			return '添加失败，请刷新后重试';
		}
	}
	//修改一条数据
	public function update_one($data)		//将要修改的数据
	{
		$old_data = $this->where(['id'=>$data['id']])->find();
		$articleModel = model('Article');
		if (!isset($data['show'])) {
			$data['show'] = 0;
		}
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		if ($data['name'] != $old_data['name'] || $data['show'] != $old_data['show']) {
			if ($this->update($data)) {
				if ($old_data['show'] == '0' && $data['show'] == '1') {
					$this->redis_column_show_id_zset_one($data['id']);
				}elseif ($old_data['show'] == '1' && $data['show'] == '0') {
					$this->redis_del_column_show_id_zset_one($data['id']);
				}
				$new_data = $this->where(['id'=>$data['id']])->find();
				$this->redis_column_lst_one($data['id'],$new_data);
				$this->redis_column_lst_all_one($data['id'],$new_data);
				if ($data['name'] != $old_data['name']) {		//因为此处文章里的redis表只有栏目名字是要同步的，所以加这个判断
					$articleModel->update_more_column_article($data['id']);
				}
			}else{
				return '修改失败，请刷新后重新尝试';
			}
		}else{
			return '数据无变动';
		}
	}
	//删除一个栏目
	public function delete_one($column_id,$user_article_d)		//将要删除的栏目id,当前用户文章模块删除权限
	{
		$articleModel = model('admin/Article');
		$res = $articleModel->where(['column_id'=>$column_id])->field('id')->select();
		if ($res) {
			if ($user_article_d == '0') {
				return '您没有权限删除栏目下的所属文章！';
			}
		}
		if ($this->destroy($column_id)) {
			$this->redis_del_column_lst_one($column_id);
			$this->redis_del_column_lst_all_one($column_id);
			$this->redis_del_column_id_zset_one($column_id);
			$this->redis_del_column_show_id_zset_one($column_id);
			if ($res) {		//这里查文章id
				$list = [];
				foreach ($res as $key) {
					$list[] = $key['id'];
				}
				if ($res = $articleModel->delete_data($list,'column')) {
					return $res;
				}
			}
		}else{
			return '删除栏目失败，请刷新后重新尝试';
		}
	}
/*****************查询数据***************************/
	//查询redis中column_lst_all中一条数据
	public function redis_select_column_lst_all_one($column_id)		//id
	{
		if ($this->redis_obj->exists('column_lst_all')) {
			return $this->redis_obj->Hget('column_lst_all',$column_id);
		}else{
			$this->redis_column_lst_all();
			return $this->redis_obj->Hget('column_lst_all',$column_id);
		}
	}

	//查询redis中column_lst所有数据
	public function redis_select_column_lst()
	{
		if ($this->redis_obj->exists('column_lst')) {
			return $this->redis_obj->Hgetall('column_lst');
		}else{
			return $this->redis_column_lst(true);
		}
	}

	//查询redis中column_lst一条数据
	public function redis_select_column_lst_one($column_id)		//栏目id
	{
		if ($this->redis_obj->exists('column_lst')) {
			return $this->redis_obj->Hget('column_lst',$column_id);
		}else{
			$this->redis_column_lst();
			return $this->redis_obj->Hget('column_lst',$column_id);
		}
	}
	//查询redis中column_lst_all所有数据
	public function redis_select_column_lst_all($count = false)	//如果为true则返回所有数据长度
	{
		if ($count) {
			if ($res = $this->redis_obj->Hlen('column_lst_all')) {
				return $res;
			}else{
				return count($this->redis_column_lst_all(true));
			}
		}else{
			if ($this->redis_obj->exists('column_lst_all')) {
				return $this->redis_obj->Hgetall('column_lst_all');
			}else{
				return $this->redis_column_lst_all(true);
			}
		}
	}	
/****************redis刷新/修改一条*******************/
	//刷新redis中column_lst一条数据
	private function redis_column_lst_one($column_id,$data = null)	//将要刷新的栏目id | 将要刷新的数据可有可无，如果没有再从数据库中查
	{
		if ($data) {
			$list['id'] = $data['id'];
			$list['name'] = $data['name'];
		}else{
			$list = $this->where(['id'=>$column_id])->field('id,name')->find();
		}
		if ($this->redis_obj->exists('column_lst')) {
			$this->redis_obj->Hset('column_lst',$column_id,$list);
		}else{
			$this->redis_column_lst();
		}
	}

	//刷新redis中column_lst_all中的一条数据
	private function redis_column_lst_all_one($column_id,$data = null)	//将要刷新的栏目id |  将要刷新的数据可有可无，但必须完整 如果没有再从数据库中查
	{
		if (!$data) {
			$data = $this->where(['id'=>$column_id])->find();
		}
		if ($this->redis_obj->exists('column_lst_all')) {
			$this->redis_obj->Hset('column_lst_all',$column_id,$data);
		}else{
			$this->redis_column_lst_all();
		}
	}

	//刷新redis中column_id_zset中的一条数据
	private function redis_column_id_zset_one($column_id)
	{
		if ($this->redis_obj->exists('column_id_zset')) {
			$this->redis_obj->Zadd('column_id_zset',$column_id,$column_id);
		}else{
			$this->redis_column_id_zset();
		}
	}

	//刷新column_show_id_zset中的一条数据
	private function redis_column_show_id_zset_one($column_id)
	{
		if ($this->redis_obj->exists('column_show_id_zset')) {
			$this->redis_obj->Zadd('column_show_id_zset',$column_id,$column_id);
		}else{
			$this->redis_column_show_id_zset();
		}
	}
/****************redis刷新全部数据*******************/
	//刷新redis中column_lst全部数据
	public function redis_column_lst($re = false)			//如果为true则返回所有数据
	{
		$data = $this->field('id,name')->select();
		$list = [];
		foreach ($data as $key) {
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('column_lst',$list);
		if ($re === true) {
			return $data;
		}
	}
	//刷新redis中column_lst_all的全部数据
	private function redis_column_lst_all($re = false)		//如果为true则返回所有数据
	{
		$data = $this->select();
		$list = [];
		foreach ($data as $key) {
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('column_lst_all',$list);
		if ($re) {
			return $data;
		}
	}
	//刷新redis中column_id_zset的全部数据
	private function redis_column_id_zset()
	{
		$data = $this->field('id')->select();
		foreach ($data as $key) {
			$this->redis_obj->Zadd('column_id_zset',$key['id'],$key['id']);
		}
	}

	//刷新column_show_id_zset的所有数据
	public function redis_column_show_id_zset()
	{
		$data = $this->where(['show'=>'1'])->field('id')->select();
		foreach ($data as $key) {
			$this->redis_obj->Zadd('column_show_id_zset',$key['id'],$key['id']);
		}
	}
/****************redis删除一条数据*******************/
	//column_lst  
	public function redis_del_column_lst_one($column_id)		//栏目id
	{
		if ($this->redis_obj->exists('column_lst')) {
			$this->redis_obj->Hdel('column_lst',$column_id);
		}
	}
	//column_lst_all
	public function redis_del_column_lst_all_one($column_id)		//栏目id
	{
		if ($this->redis_obj->exists('column_lst_all')) {
			$this->redis_obj->Hdel('column_lst_all',$column_id);
		}
	}
	//column_id_zset
	public function redis_del_column_id_zset_one($column_id)		//栏目id
	{
		if ($this->redis_obj->exists('column_id_zset')) {
			$this->redis_obj->Zrem('column_id_zset',$column_id);
		}
	}
	//column_show_id_zset
	public function redis_del_column_show_id_zset_one($column_id)		//栏目id
	{
		if ($this->redis_obj->exists('column_show_id_zset')) {
			$this->redis_obj->Zrem('column_show_id_zset',$column_id);
		}
	}
/**********************分页算法**************************/
	//分页算法
	public function pjax_lst($count,$input = null)	//column_lst的长度，接收到的get数据
	{
		$zcount = $this->redis_obj->Zcard('column_id_zset');
		if ($count != $zcount) {
			$this->redis_column_id_zset();
		}
		if (!$input) {
			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrange('column_id_zset',$start,$end);
		}else{
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$start = $input['page']*10-10;
			$end = $start+9;
			if (isset($input['sort'])) {
				if ($input['sort'] == 'order') {
					$res = $this->redis_obj->Zrange('column_id_zset',$start,$end);
					if (!$res) {
						$res =  $this->redis_obj->Zrange('column_id_zset',0,9);
					}
				}elseif($input['sort'] == 'sort'){
					$res = $this->redis_obj->Zrevrange('column_id_zset',$start,$end);
					if (!$res) {
						$res = $this->redis_obj->Zrevrange('column_id_zset',0,9);
					}
				}else{
					$res = $this->redis_obj->Zrange('column_id_zset',0,9);
				}
			}else{
				$res = $this->redis_obj->Zrange('column_id_zset',$start,$end);
			}
		}
		$list = $this->redis_obj->Hmget('column_lst_all',$res);
		return $list;
	}
}