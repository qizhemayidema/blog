<?php 
namespace app\admin\model;

use think\Model;
use think\cache\driver\Redis;

class Tag extends Model
{
	public $redis_obj;

	public function initialize()
	{
		$this->redis_obj = new Redis();
	}
/********************************查找数据******************************************/
	//查找tag_lst_all的长度
	public function select_count_redis_tag_lst_all()
	{
		if ($count = $this->redis_obj->Hlen('tag_lst_all')) {
			return $count;
		}else{
			return count($this->redis_tag_lst_all());
		}
	}
	//查找tag_lst所有数据
	public function select_redis_tag_lst()
	{
		if ($this->redis_obj->exists('tag_lst')) {
			return $this->redis_obj->Hgetall('tag_lst');
		}else{
			return $this->redis_tag_lst(true);
		}
	}
	//查找tag_lst_all的一条数据
	public function select_redis_tag_lst_all_one($tag_id)
	{
		if (!$this->redis_obj->exists('tag_lst_all')) {
			return $this->redis_obj->Hget('tag_lst_all',$tag_id);
		}
		return $this->redis_obj->Hget('tag_lst_all',$tag_id);
	}
/********************************逻辑入口******************************************/
	//标签下文章+1			+1动作
	public function article_count_up_one($tag_id)
	{
		$list = $this->where(['id'=>$tag_id])->field('id,article_count')->find();
		$this->update(['article_count'=>$list['article_count']+1],['id'=>$list['id']]);
		$data = $this->where(['id'=>$tag_id])->find();
		$this->redis_tag_lst_one($tag_id,$data);
		$this->redis_tag_lst_all_one($tag_id,$data);
		$this->redis_tag_article_count_zset_one($tag_id,$list['article_count']+1);
	}
	//标签下文章-1			-1动作
	public function article_count_down_one($tag_id)
	{
		$list = $this->where(['id'=>$tag_id])->field('id,article_count')->find();
		$this->update(['article_count'=>$list['article_count']-1],['id'=>$list['id']]);
		$data = $this->where(['id'=>$tag_id])->find();
		$this->redis_tag_lst_one($tag_id,$data);
		$this->redis_tag_lst_all_one($tag_id,$data);
		$this->redis_tag_article_count_zset_one($tag_id,$list['article_count']-1);
	}
	//新增一条数据
	public function insert_one($data)			//将要新增的数据
	{
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		$data['time'] = time();
		$data['article_count'] = 0;
		if ($this->save($data)) {
			$data['id'] = $this->id;
			$this->redis_tag_lst_one($this->id,$data);
			$this->redis_tag_lst_all_one($this->id,$data);
			$this->redis_tag_id_zset_one($this->id);
			$this->redis_tag_article_count_zset_one($this->id,$data['article_count']);
		}else{
			return '添加失败，请刷新后重新尝试';
		}
	}
	//修改一条数据
	public function update_one($data)		//将要修改的数据
	{
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		$articleModel = model('Article');
		$old_data = $this->where(['id'=>$data['id']])->find();
		if ($this->update($data)) {
			$new_data = $this->where(['id'=>$data['id']])->find();
			$this->redis_tag_lst_one($data['id'],$new_data);
			$this->redis_tag_lst_all_one($data['id'],$new_data);
			if ($old_data['name'] != $new_data['name']) {
				$articleModel->update_more_tag_article($data['id']);
			}
		}
	}

	//删除一个 or 多个 tag
	public function delete_data($tag)		//可能是字符串（一个数据），可能是一维索引数组（多个数据）
	{
		$list = [];
		if (!is_array($tag)) {
			$list[0] = $tag;
		}else{
			$list = $tag;
		}
		$articleModel = model('admin/Article');
		foreach ($list as $key => $value) {
			if ($articleModel->redis_select_article_tag_what_id_zset($value)) {
				return '您所删除的TAG中有文章正在使用，无法删除！';
			}
		}
		if ($this->destroy($list)) {
			foreach ($list as $key1 => $value1) {
				$this->redis_del_tag_lst_one($value1);
				$this->redis_del_tag_lst_all_one($value1);
				$this->redis_del_tag_id_zset_one($value1);
				$this->redis_del_tag_article_count_zset_one($value1);
			}
		}else{
			return '删除失败，请刷新后重新尝试';
		}
	}
/********************************redis刷新一条数据************************************/
	//刷新tag_lst中一条数据
	public function redis_tag_lst_one($tag_id,$data = null)		//tag_id:将要刷新的tagid | data:可有可无 如果有则直接刷新，否则再从数据库中
	{
		if ($data) {
			$list['id'] = $data['id'];
			$list['name'] = $data['name'];
			$list['article_count'] = $data['article_count'];
		}else{
			$list = $this->where(['id'=>$tag_id])->field('id,name,article_count')->find();
		}
		if ($this->redis_obj->exists('tag_lst')) {
			$this->redis_obj->Hset('tag_lst',$tag_id,$list);
		}else{
			$this->redis_tag_lst();
		}
	}
	//刷新tag_lst_all中一条数据
	public function redis_tag_lst_all_one($tag_id,$data = null)		//tag_id:将要刷新的tagid | data:可有可无 如果有则直接刷新，否则再从数据库中，此data数据必须完整
	{
		if (!$data) {
			$data = $this->where(['id'=>$tag_id])->find();
		}
		if ($this->redis_obj->exists('tag_lst_all')) {
			$this->redis_obj->Hset('tag_lst_all',$tag_id,$data);
		}else{
			$this->redis_tag_lst_all();
		}
	}
	//刷新tag_id_zset中的一条数据
	public function redis_tag_id_zset_one($tag_id)				//tag_id:将要刷新的tagid
	{
		if ($this->redis_obj->exists('tag_id_zset')) {
			$this->redis_obj->Zadd('tag_id_zset',$tag_id,$tag_id);
		}else{
			$this->redis_tag_id_zset();
		}
	}
	//刷新tag_article_count_zset中的一条数据
	public function redis_tag_article_count_zset_one($tag_id,$article_count)	//tag_id:将要刷新的tagid    |  article_count：文章数量
	{
		if ($this->redis_obj->exists('tag_article_count_zset')) {
			$this->redis_obj->Zadd('tag_article_count_zset',$article_count+1,$tag_id);
		}else{
			$this->redis_tag_article_count_zset();
		}
	}
/********************************redis刷新全部数据************************************/
	//刷新tag_lst的全部数据
	public function redis_tag_lst($re = false)		//如果为true则返回所有数据
	{
		$data = $this->field('id,name,article_count')->order('id')->select();
		$list = [];
		foreach ($data as $key) {
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('tag_lst',$list);
		if ($re === true) {
			return $data;
		}
	}
	//刷新tag_lst_all的全部数据
	public function redis_tag_lst_all($re = false)		//如果为true则返回全部数据
	{
		$data = $this->select();
		$list = [];
		foreach ($data as $key) {
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('tag_lst_all',$list);
		if ($re === true) {
			return $data;
		}
	}
	//刷新tag_id_zset所有数据
	public function redis_tag_id_zset()
	{
		$data = $this->field('id')->select();
		foreach ($data as $key) {
			$this->redis_obj->Zadd('tag_id_zset',$key['id'],$key['id']);
		}
	}
	//刷新tag_article_count_zset全部数据
	public function redis_tag_article_count_zset()
	{
		$data = $this->field('id,article_count')->select();
		foreach ($data as $key) {
			$this->redis_obj->Zadd('tag_article_count_zset',$key['article_count']+1,$key['id']);
		}
	}
/********************************redis删除一条数据************************************/
	//tag_lst
	public function redis_del_tag_lst_one($tag_id)		//tag的id
	{
		if ($this->redis_obj->exists('tag_lst')) {
			$this->redis_obj->Hdel('tag_lst',$tag_id);
		}
	}
	//tag_lst_all
	public function redis_del_tag_lst_all_one($tag_id)		//tag的id
	{
		if ($this->redis_obj->exists('tag_lst_all')) {
			$this->redis_obj->Hdel('tag_lst_all',$tag_id);
		}
	}
	//tag_id_zset
	public function redis_del_tag_id_zset_one($tag_id)
	{
		if ($this->redis_obj->exists('tag_id_zset')) {
			$this->redis_obj->Zrem('tag_id_zset',$tag_id);
		}
	}
	//tag_article_count_zset
	public function redis_del_tag_article_count_zset_one($tag_id)
	{
		if ($this->redis_obj->exists('tag_article_count_zset')) {
			$this->redis_obj->Zrem('tag_article_count_zset',$tag_id);
		}
	}
/********************************分页算法*********************************************/
	//tag展示页查找符合条件的数据
	public function pjax_lst($count,$input = null)		//count:tag_lst_all数据长度，input：控制器接收到的get传参
	{
		$zcount = $this->redis_obj->Zcard('tag_id_zset');
		if ($zcount != $count || !$zcount) {
			$this->redis_tag_id_zset();
		}
		if (!$input) {
			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrange('tag_id_zset',$start,$end);
		}else{
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$start = $input['page']*10-10;
			$end = $start+9;
			if (isset($input['sort'])) {
				if (!$this->redis_obj->exists('tag_article_count_zset')) {
					$this->redis_tag_article_count_zset();	
				}
				if ($input['sort'] == 'order') {
					$res = $this->redis_obj->Zrange('tag_id_zset',$start,$end);
				}elseif($input['sort'] == 'sort'){
					$res = $this->redis_obj->Zrevrange('tag_id_zset',$start,$end);
				}elseif ($input['sort'] == 'art_count_order') {
					$res = $this->redis_obj->Zrange('tag_article_count_zset',$start,$end);
				}elseif ($input['sort'] == 'art_count_sort') {
					$res = $this->redis_obj->Zrevrange('tag_article_count_zset',$start,$end);
				}else{
					$res = $this->redis_obj->Zrange('tag_id_zset',$start,$end);
				}
			}else{
				$res = $this->redis_obj->Zrange('tag_id_zset',$start,$end);
			}
		}
		$list = $this->redis_obj->Hmget('tag_lst_all',$res);
		return $list;
	}

	//tag下文章查找符合条件的数据
	public function tag_article_lst($tag_id,$input = null)		//接收到的传参
	{
		$articleModel =  model('admin/Article');
		if (!$this->redis_obj->exists('article_lst')) {
			$articleModel->redis_article_lst();
		}
		if (!$input) {
			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrevrange('article_tag_'.$tag_id.'_id_zset',$start,$end);
		}else{
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$start = $input['page'] *10 -10;
			$end = $start + 9;
			$res = $this->redis_obj->Zrevrange('article_tag_'.$tag_id.'_id_zset',$start,$end);
		}
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('article_lst',$res);
	}
}