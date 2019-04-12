<?php 
namespace app\admin\model;

use think\Model;
use think\cache\driver\Redis;

class Link extends Model
{
	public $redis_obj;

	protected function initialize()
	{
		$this->redis_obj = new Redis();
	}
/***********************************逻辑入口****************************************/
	//新增一条数据
	public function insert_one($data,$img = null)		//要增加的数据，图片数组
	{	
		if ($img) {		//表明有图片上传
			if ($path = $this->upload_ico($img)) {
				$data['link_ico'] = $path;
			}else{
				return '图片上传失败，请刷新后重新尝试';
			}
		}
		if (!isset($data['link_time'])) {
			$data['link_time'] = time();
		}
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		if ($this->save($data)) {
			$res = $this->where(['id'=>$this->id])->find();		//刚新增完的数据
			$this->redis_link_lst_one($this->id,$res);
			$this->redis_link_id_zset_one($this->id);
		}else{
			if (isset($data['link_ico'])) {
				if (file_exists(ROOT_PATH.'public'.$data['link_ico']) && $data['link_ico'] != '') {
					unlink(ROOT_PATH.'public'.$data['link_ico']);	//如果有图片上传上来了 再删除掉之前的
				}
			}
			return '新增失败，请刷新后重新尝试';
		}
	}

	//上传图片
	public function upload_ico($img)		//$_FILES中的图片一维数组
	{	
		$houzhui = strrchr($img['name'],'.');//.xxx
		$ico_name = uniqid().$houzhui;
		$path = DS.'static'.DS.'index'.DS.'link_ico'.DS.$ico_name;
		if (move_uploaded_file($img['tmp_name'],ROOT_PATH.'public'.$path)) {
			return $path;
		}else{
			return false;
		}
	}

	//修改一条数据
	public function update_one($data,$img = null)		//将要修改成的数据,图片文件
	{
		if ($img != '') {		//表明有图片上传
			if ($path = $this->upload_ico($img)) {
				$data['link_ico'] = $path;
			}else{
				return '图片上传失败，请刷新后重新尝试';
			}
		}else{
			unset($data['link_ico']);
		}
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		if (!$old_data = $this->where(['id'=>$data['id']])->find()) {	//未修改前图片路径
			return '操作失误,请刷新后重新尝试';
		}
		if ($this->update($data)) {
			$this->redis_link_lst_one($data['id']);
			if (file_exists(ROOT_PATH.'public'.$old_data['link_ico']) && $old_data['link_ico'] != '' && isset($data['link_ico'])) {
				unlink(ROOT_PATH.'public'.$old_data['link_ico']);	//如果之前有图片则删除掉
			}
		}else{
			if (isset($data['link_ico'])) {
				if (file_exists(ROOT_PATH.'public'.$data['link_ico']) && $data['link_ico'] != '') {
					unlink(ROOT_PATH.'public'.$data['link_ico']);	//如果有图片上传上来了 再删除掉之前的
				}
			}
			return '新增失败，请刷新后重新尝试';
		}
	}

	//批量删除数据or删除一条数据
	public function delete_data($data)		//一维数组 value为要删除的友链id
	{	
		$list = [];
		foreach ($data as $key => $value) {
			$list[$key] = $this->select_lst_one($value);
		}
		if ($this->destroy($data)) {
			foreach ($data as $key => $value) {
				$this->redis_del_link_lst_one($value);
				$this->redis_del_link_id_zset($value);
				if (file_exists(ROOT_PATH.'public'.$list[$key]['link_ico']) && $list[$key]['link_ico'] != '') {
					unlink(ROOT_PATH.'public'.$list[$key]['link_ico']);
				}
			}
		}else{
			return '操作失误，请刷新后重新尝试';
		}
	}
/***********************************查询数据***************************************/
	//查询所有数据
	public function select_lst_all($count = false)		//如果count为true则返回全部数据的长度
	{
		if ($res = $this->redis_obj->Hlen('link_lst')) {
			if ($count === true) {
				return $res;
			}else{
				return $this->redis_obj->Hgetall('link_lst');
			}
		}else{
			$data = $this->redis_link_lst(true);
			if ($count === true) {
				return count($data);
			}else{
				return $data;
			}
		}
	}

	//查询单条数据 redis中 link_lst
	public function select_lst_one($link_id)
	{
		if ($this->redis_obj->exists('link_lst')) {
			return $this->redis_obj->Hget('link_lst',$link_id);
		}else{
			$this->redis_link_lst();
			return $this->redis_obj->Hget('link_lst',$link_id);
		}
	}
/**************************redis新增/修改/删除/刷新全部*********************************/
	//刷新一条数据到redis中 link_lst
	public function redis_link_lst_one($link_id,$data = null)		//link_id：友链id,data：要新增的数据，可有可无，如果没有再从数据库中查
	{
		if ($this->redis_obj->exists('link_lst')) {
			if ($data) {
				$this->redis_obj->Hset('link_lst',$link_id,$data);
			}else{
				$data = $this->where(['id'=>$link_id])->find();
				$this->redis_obj->Hset('link_lst',$link_id,$data);
			}
		}else{
			$this->redis_link_lst();
		}
	}

	//刷新一条数据到redis中 link_id_zset
	public function redis_link_id_zset_one($link_id)	//友链id
	{
		if ($this->redis_obj->exists('link_id_zset')) {
			$this->redis_obj->Zadd('link_id_zset',$link_id,$link_id);
		}else{
			$this->redis_link_id_zset();
		}
	}

	//从redis中  link_lst    删除一条数据
	public function redis_del_link_lst_one($link_id)
	{
		$this->redis_obj->Hdel('link_lst',$link_id);
	}

	//从redis中 link_id_zset中删除一条数据
	public function redis_del_link_id_zset($link_id)
	{
		$this->redis_obj->Zrem('link_id_zset',$link_id);
	}

	//刷新所有数据到redis中 link_lst
	public function redis_link_lst($re = false)			//如果re为true则返回所有数据
	{
		$data = $this->all();
		$list = [];
		foreach ($data as $key) {
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('link_lst',$list);
		if ($re === true) {
			return $data;
		}
	}

	//刷新所有数据到redis中 link_id_zset
	public function redis_link_id_zset()
	{
		$data = $this->field('id')->select();
		foreach ($data as $key) {
			$this->redis_obj->Zadd('link_id_zset',$key['id'],$key['id']);
		}
	}
/***********************************分页算法****************************************/

	//查询符合展示页条件的数据
	public function pjax_lst($count,$data = null)		//所有数据长度，条件数组
	{
		$zcount = $this->redis_obj->Zcard('link_id_zset');
		if ($zcount != $count) {
			$this->redis_link_id_zset();
		}
		if ($data == null) {
			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrange('link_id_zset',$start,$end);
		}else{
			if (!isset($data['page'])) {
				$data['page'] = 1;
			}
			$start = $data['page']*10-10;
			$end = $start+9;
			if (isset($data['sort'])) {
				if ($data['sort'] == 'order') {			//正序排列
					$res = $this->redis_obj->Zrange('link_id_zset',$start,$end);
				}else if($data['sort'] == 'sort'){		//倒序排列
					$res = $this->redis_obj->Zrevrange('link_id_zset',$start,$end);
				}else{
					$res = $this->redis_obj->Zrange('link_id_zset',$start,$end);
				}
			}else{			//如果没有排序
				$res = $this->redis_obj->Zrange('link_id_zset',$start,$end);
			}
		}
		$list = $this->redis_obj->Hmget('link_lst',$res);
		if (!$list) {
			return [];
		}
		return $list;
	}
}