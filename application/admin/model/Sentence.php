<?php
namespace app\admin\model;

use think\Model;
use think\cache\driver\Redis;

class Sentence extends Model
{
	public $redis_obj;

	public function initialize()
	{
		$this->redis_obj = new Redis();
	}

/****************************获取数据*******************************/
	//获取sentence_id_zset数据长度
	public function select_count_sentence_id_zset()
	{
		if ($count = $this->redis_obj->Zcard('sentence_id_zset')) {
			return $count;
		}else{
			return count($this->redis_sentence_id_zset(true));
		}
	}
	//获取sentence_lst所有数据
	public function select_sentence_lst($count = false)		//如果为true则返回数据长度
	{
		if ($count) {
			if ($res = $this->redis_obj->Hlen('sentence_lst')) {
				return $res;
			}else{
				return count($this->redis_sentence_lst(true));
			}
		}else{
			if ($this->redis_obj->exists('sentence_lst')) {
				return $this->redis_obj->Hgetall('sentence_lst');
			}else{
				$this->redis_sentence_lst();
				return $this->redis_obj->Hgetall('sentence_lst');
			}
		}
	}
	//获取sentence_lst一条数据
	public function selectt_sentence_lst_one($sentence_id)		//句子id
	{
		if ($this->redis_obj->exists('sentence_lst')) {
			return $this->redis_obj->Hget('sentence_lst',$sentence_id);
		}else{
			$this->redis_sentence_lst();
			return $this->redis_obj->Hget('sentence_lst',$sentence_id);
		}
	}
/***************************逻辑入口*******************************/
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
		$list = $data;
		if ($this->save($data)) {
			$list['id'] = $this->id;
			$this->redis_sentence_lst_one($list['id'],$list);
			$this->redis_sentence_id_zset_one($list['id']);
			if ($list['show'] == '1') {
				if ($old_show = $this->where('id != '.$this->id)->where(['show'=>'1'])->field('id')->find()) {
					$this->update(['id'=>$old_show['id'],'show'=>'0']);
					$this->redis_del_sentence_show_id_zset();
					$this->redis_sentence_lst_one($old_show['id']);
					$this->redis_sentence_show_id_zset_one($list['id']);
				}
			}
		}else{
			return '新增失败，请刷新后重试';
		}
	}
	//修改一条数据
	public function update_one($data)		//将要修改的数据
	{
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		if (!isset($data['show'])) {
			$data['show'] = 0;
		}
		$old = $this->where(['show'=>'1'])->field('id')->find();
		if ($this->update($data)) {
			$this->redis_sentence_lst_one($data['id']);
			$this->redis_sentence_id_zset_one($data['id']);
			if ($data['show'] == '1') {
				if ($old_show = $this->where('id != '.$data['id'])->where(['show'=>'1'])->field('id')->find()) {
					$this->update(['id'=>$old_show['id'],'show'=>'0']);
				}
				$this->redis_del_sentence_show_id_zset();
				$this->redis_sentence_lst_one($old_show['id']);
				$this->redis_sentence_show_id_zset_one($data['id']);
			}else{
				if ($data['show'] == '0') {
					if ($old['id'] == $data['id']) {
						$this->redis_del_sentence_show_id_zset();
					}
				}
			}
		}else{
			return '修改失败，请刷新后尝试';
		}
	}
	//删除一条数据 or 批量删除
	public function delete_data($data)		//可以是一个句子id or 一个一维数组，一维数组中索引数组，值为句子id
	{
		$show = $this->where(['show'=>'1'])->field('id')->find();		//展示的id
		if (is_array($data)) {
			if ($this->destroy($data)) {
				foreach ($data as $key => $value) {
					if ($value == $show['id']) {
						$this->redis_del_sentence_show_id_zset();
					}
					$this->redis_del_sentence_id_zset_one($value);
					$this->redis_del_sentence_lst_one($value);
				}
			}else{
				return '删除失败，请刷新后重试';
			}
		}else{
			if ($this->destroy($data)) {
				if ($data == $show['id']) {
					$this->redis_del_sentence_show_id_zset();
				}
				$this->redis_del_sentence_id_zset_one($data);
				$this->redis_del_sentence_lst_one($data);
			}else{
				return '删除失败，请刷新后重试';
			}
		}
	}
/**********************redis刷新一条数据***************************/
	//sentence_lst  
	public function redis_sentence_lst_one($sentence_id,$data = null)		//句子id，将要更新的数据，可有可无
	{
		if ($this->redis_obj->exists('sentence_lst')) {
			if ($data) {
				$list['id']						= $data['id'];
				$list['sentence'] 				= $data['sentence'];
				$list['other_sentence'] 		= $data['other_sentence'];
				$list['show']					= $data['show'];
				$list['time']					= $data['time'];
			}else{
				$list = $this->where(['id'=>$sentence_id])->find();
			}	
			$this->redis_obj->Hset('sentence_lst',$sentence_id,$list);
		}else{
			$this->redis_sentence_lst();
		}
	}

	//sentence_id_zset
	public function redis_sentence_id_zset_one($sentence_id)		//句子id
	{
		if ($this->redis_obj->exists('sentence_id_zset')) {
			$this->redis_obj->Zadd('sentence_id_zset',$sentence_id,$sentence_id);
		}else{
			$this->redis_sentence_id_zset();
		}
	}
	//sentence_show_id_zset   只有显示的句子
	public function redis_sentence_show_id_zset_one($sentence_id)	//句子id
	{
		if ($this->redis_obj->exists('sentence_show_id_zset')) {
			$this->redis_obj->Zadd('sentence_show_id_zset',$sentence_id,$sentence_id);
		}else{
			$this->redis_sentence_show_id_zset();
		}
	}
/**********************redis刷新全部数据***************************/
	//sentence_lst
	public function redis_sentence_lst($re = false)		//如果为true则返回所有数据
	{
		$list = [];
		$res = $this->select();
		foreach ($res as $key) {
			$list[$key['id']] = json_encode($key,true);
		}
		$this->redis_obj->Hmset('sentence_lst',$list);
		if ($re === true) {
			return $res;
		}
	}
	//sentence_id_zset
	public function redis_sentence_id_zset($re = false)	//如果为true则返回所有数据
	{
		$res = $this->field('id')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('sentence_id_zset',$key['id'],$key['id']);
		}
		if ($re === true) {
			return $res;
		}
	}
	//sentence_show_id_zset
	public function redis_sentence_show_id_zset()
	{
		$res = $this->where(['show'=>'1'])->field('id')->find();
		$this->redis_obj->Zadd('sentence_show_id_zset',$res['id'],$res['id']);
	}
/**********************redis删除所有数据***************************/
	//sentence_show_id_zset  删除这张表，这表里如果代码正确永远只有一条数据
	public function redis_del_sentence_show_id_zset()
	{
		if ($this->redis_obj->exists('sentence_show_id_zset')) {
			$this->redis_obj->rm('sentence_show_id_zset');
		}
	}
/*************************redis删除一条数据*******************************/
	//sentence_id_zset
	public function redis_del_sentence_id_zset_one($sentence_id)		//句子id
	{
		if ($this->redis_obj->exists('sentence_id_zset')) {
			$this->redis_obj->Zrem('sentence_id_zset',$sentence_id);
		}
	}
	//sentence_lst
	public function redis_del_sentence_lst_one($sentence_id)				//删除一条数据
	{
		if ($this->redis_obj->exists('sentence_lst')) {
			$this->redis_obj->Hdel('sentence_lst',$sentence_id);
		}
	}
/*************************分页算法*****************************/
	public function pjax_lst($count,$input = null)		//sentence_lst的长度，get接收到的参数
	{
		$zcount = $this->select_count_sentence_id_zset();
		if ($zcount != $count) {
			$this->redis_sentence_id_zset();
		}
		if (!$input) {
			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrevrange('sentence_id_zset',$start,$end);
		}else{
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$start = $input['page']*10-10;
			$end = $start+9;
			if (isset($input['sort'])) {
				if ($input['sort'] == 'order') {
					$res = $this->redis_obj->Zrange('sentence_id_zset',$start,$end);
				}elseif ($input['sort'] == 'sort') {
					$res = $this->redis_obj->Zrevrange('sentence_id_zset',$start,$end);
				}else{
					$res = $this->redis_obj->Zrevrange('sentence_id_zset',$start,$end);
				}
			}else{
				$res = $this->redis_obj->Zrevrange('sentence_id_zset',$start,$end);
			}
			if (!$res) {
				$res = $this->redis_obj->Zrevrange('sentence_id_zset',0,9);
			}
		}
		return $this->redis_obj->Hmget('sentence_lst',$res);
	}
}