<?php 
namespace app\common\model;

use think\Model;
use think\cache\driver\Redis;

class Comment extends Model
{
	public $redis_obj;

	public function initialize()
	{
		$this->redis_obj = new Redis();
	}
/**************刷新一条数据*******************/
	//刷新comment_lst中一条数据
	public function redis_common_lst_one($comment_id,$data = null)	//评论id，此评论id的父节点必须为0（既top_id），将要刷新的数据
	{
		$list = [];
		if ($data) {
			$list['id'] 			= $data['id'];
			$list['top_id'] 		= $data['top_id'];
			$list['p_id'] 			= $data['p_id'];
			$list['article_id'] 	= $data['article_id'];
			$list['name'] 			= $data['name'];
			$list['comment'] 		= $data['comment'];
			$list['reply_name'] 	= $data['reply_name'];
			$list['time'] 			= $data['time'];
			$list['child'] 			= $data['child'];
			$list['old_comment']	= $data['old_comment'];
		}else{
			// $top_id = $this->where(['id'=>$comment_id])->value('top_id');
			$res = $this->where(['top_id'=>$comment_id])->order('id')->select();
			$res = json_encode($res,true);
			$res = json_decode($res,true);
			foreach ($res as $key => $value) {
				if ($value['p_id'] == 0) {
					$value['child'] = array();
					$list = $value;
				}
			}
			foreach ($res as $key1 => $value1) {
				if ($value1['top_id'] != $value1['id']) {
					$list['child'][] = $value1;
				}
			}
		}
		if ($this->redis_obj->exists('comment_lst')) {
			$this->redis_obj->Hset('comment_lst',$comment_id,$list);
		}else{
			$this->redis_comment_lst();
		}
	}

	//刷新comment_id_zset中一条数据
	public function redis_comment_id_zset_one($comment_id)//评论id，此评论id的父节点必须为0(既top_id)
	{
		if ($this->redis_obj->exists('comment_id_zset')) {
			$this->redis_obj->Zadd('comment_id_zset',$comment_id,$comment_id);
		}else{
			$this->redis_comment_id_zset();
		}
	}
	//刷新comment_id_zset_all中一条数据
	public function redis_comment_id_zset_all_one($comment_id)//评论id，任何评论
	{
		if ($this->redis_obj->exists('comment_id_zset_all')) {
			$this->redis_obj->Zadd('comment_id_zset_all',$comment_id,$comment_id);
		}else{
			$this->redis_comment_id_zset_all();
		}
	}

	//刷新comment_article_id_?_zset中一条数据	?为文章id
	public function redis_comment_article_id_what_zset_one($article_id,$comment_id)//文章id | 评论id，此评论id的父节点必须为0(既top_id)
	{
		if ($this->redis_obj->exists('comment_article_id_'.$article_id.'_zset')) {
			$this->redis_obj->Zadd('comment_article_id_'.$article_id.'_zset',$comment_id,$comment_id);
		}else{
			$this->redis_comment_article_id_what_zset($article_id);
		}
	}
	//demo
	public function getSubTree($data,$pid = 0) {
		$tmp = array();
		foreach ($data as $key => $value) {
			if ($value['p_id'] == 0) {
				$value['child'] = array();
				$tmp[$value['id']] = $value;
			}
		}
		foreach ($data as $key1 => $value1) {
			if ($value1['top_id'] != $value1['id']) {
				$tmp[$value1['top_id']]['child'][] = $value1;
			}
		}
		return $tmp;
	}
/**************刷新所有数据*******************/
	//comment_lst
	public function redis_comment_lst()
	{
		$data = $this->order('id')->select();
		$data = json_encode($data,true);
		$data = json_decode($data,true);
		$list = [];
		foreach ($data as $key => $value) {
			if ($value['p_id'] == 0) {
				$value['child'] = array();
				$list[$value['id']] = $value;
			}
		}
		foreach ($data as $key1 => $value1) {
			if ($value1['top_id'] != $value1['id']) {
				$list[$value1['top_id']]['child'][] = $value1;
			}
		}
		$res = [];
		foreach ($list as $key2 => $value2) {
			$res[$value2['id']] = json_encode($value2,true);
		}
		$this->redis_obj->Hmset('comment_lst',$res);
	}

	//comment_id_zset
	public function redis_comment_id_zset()
	{
		$data = $this->where(['p_id'=>0])->field('id')->select();
		foreach ($data as $key) {
			$this->redis_obj->Zadd('comment_id_zset',$key['id'],$key['id']);
		}
	}
	//comment_id_zset_all
	public function redis_comment_id_zset_all()
	{
		$data = $this->field('id')->select();
		foreach ($data as $key) {
			$this->redis_obj->Zadd('comment_id_zset_all',$key['id'],$key['id']);
		}
	}
	//comment_article_id_?_zset  刷新某个文章下的数据
	public function redis_comment_article_id_what_zset($article_id,$re = false)		//文章id
	{
		$data = $this->where(['article_id'=>$article_id,'p_id'=>0])->field('id')->select();
		if (!$data) {
			return false;
		}
		foreach ($data as $key) {
			$this->redis_obj->Zadd('comment_article_id_'.$article_id.'_zset',$key['id'],$key['id']);
		}
		if ($re === true) {
			return $data;
		}
	}
}
